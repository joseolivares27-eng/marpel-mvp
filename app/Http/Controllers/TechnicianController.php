<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Notice;
use App\Models\Review;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TechnicianController extends Controller
{
    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $notices = Notice::with(['customer', 'installation', 'equipment'])
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['completed', 'resolved', 'cancelled'])
            ->orderByRaw("case when priority = 'urgent' then 0 else 1 end")
            ->orderBy('scheduled_at')
            ->get();

        $reviews = Review::with(['customer', 'installation', 'equipment'])
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['scheduled', 'assigned', 'in_progress'])
            ->orderBy('scheduled_at')
            ->get();

        $workOrders = WorkOrder::with(['installation', 'equipment', 'notice', 'review'])
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['new', 'open', 'in_progress'])
            ->latest('started_at')
            ->get();

        return view('technician.dashboard', compact('notices', 'reviews', 'workOrders'));
    }

    public function showNotice(Request $request, Notice $notice): View
    {
        $this->ensureTechnicianCanSee($request, $notice->assigned_user_id);

        $notice->load([
            'customer',
            'installation',
            'equipment.reviews' => fn ($query) => $query->latest('performed_at')->limit(5),
            'equipment.workOrders' => fn ($query) => $query->latest('finished_at')->limit(5),
        ]);

        return view('technician.notice', compact('notice'));
    }

    public function startNotice(Request $request, Notice $notice, WorkOrderService $service): RedirectResponse
    {
        $this->ensureTechnicianCanSee($request, $notice->assigned_user_id);

        $workOrder = $service->startFromNotice($notice, $request->user());

        return redirect()->route('technician.work-orders.show', $workOrder);
    }

    public function startReview(Request $request, Review $review, WorkOrderService $service): RedirectResponse
    {
        $this->ensureTechnicianCanSee($request, $review->assigned_user_id);

        $workOrder = $service->startFromReview($review, $request->user());

        return redirect()->route('technician.work-orders.show', $workOrder);
    }

    public function showWorkOrder(Request $request, WorkOrder $workOrder, WorkOrderService $service): View
    {
        $this->ensureTechnicianCanSee($request, $workOrder->assigned_user_id);

        if (in_array($workOrder->status, ['new', 'open'], true)) {
            $workOrder = $service->open($workOrder, $request->user());
        }

        $request->user()
            ->unreadNotifications
            ->filter(fn ($notification): bool => (int) ($notification->data['work_order_id'] ?? 0) === (int) $workOrder->id)
            ->each->markAsRead();

        $workOrder->load(['customer', 'installation', 'equipment', 'notice', 'review', 'materials.material', 'photos']);
        $materials = Material::where('is_active', true)->orderBy('name')->get();

        return view('technician.work-order', compact('workOrder', 'materials'));
    }

    public function updateWorkOrder(Request $request, WorkOrder $workOrder, WorkOrderService $service): RedirectResponse
    {
        $this->ensureTechnicianCanSee($request, $workOrder->assigned_user_id);

        $validated = $request->validate([
            'work_performed' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
            'result' => ['nullable', 'string', 'in:solucionado,pendiente,no_solucionado'],
            'materials' => ['array'],
            'materials.*.material_id' => ['nullable', 'integer', 'exists:materials,id'],
            'materials.*.description' => ['nullable', 'string', 'max:255'],
            'materials.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'photos.*' => ['nullable', 'image', 'max:12288'],
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $path = $photo->store("work-orders/{$workOrder->id}", 'public');
            $workOrder->photos()->create([
                'created_by' => $request->user()->id,
                'path' => $path,
                'kind' => 'field',
            ]);
        }

        $workOrder->update([
            'status' => $workOrder->status === 'closed' ? 'closed' : 'in_progress',
            'work_performed' => $validated['work_performed'] ?? null,
            'observations' => $validated['observations'] ?? null,
            'result' => $validated['result'] ?? 'pendiente',
        ]);

        $service->saveMaterials($workOrder, $validated['materials'] ?? []);

        if ($request->input('action') === 'sign') {
            return redirect()->route('technician.work-orders.signature', $workOrder);
        }

        if ($request->input('action') === 'close') {
            if (! $workOrder->customer_signature_path) {
                return redirect()
                    ->route('technician.work-orders.signature', $workOrder)
                    ->with('status', 'Falta la firma del cliente para cerrar el parte.');
            }

            $service->close($workOrder->refresh(), $validated);

            return redirect()->route('technician.dashboard')->with('status', 'Parte cerrado.');
        }

        return back()->with('status', 'Parte guardado.');
    }

    public function signature(Request $request, WorkOrder $workOrder): View
    {
        $this->ensureTechnicianCanSee($request, $workOrder->assigned_user_id);

        $workOrder->load(['installation', 'equipment']);

        return view('technician.signature', compact('workOrder'));
    }

    public function storeSignature(Request $request, WorkOrder $workOrder, WorkOrderService $service): RedirectResponse
    {
        $this->ensureTechnicianCanSee($request, $workOrder->assigned_user_id);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'signature_data' => ['required', 'string'],
        ]);

        $signature = preg_replace('#^data:image/\w+;base64,#i', '', $validated['signature_data']);
        $binary = base64_decode($signature, true);

        abort_if($binary === false, 422, 'Firma no valida.');

        $path = 'signatures/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        $workOrder->update([
            'customer_name' => $validated['customer_name'],
            'customer_signature_path' => $path,
            'signed_at' => now(),
        ]);

        $workOrder->refresh();

        $service->close($workOrder, [
            'work_performed' => $workOrder->work_performed,
            'observations' => $workOrder->observations,
            'result' => $workOrder->result ?: 'pendiente',
        ]);

        return redirect()->route('technician.dashboard')->with('status', 'Parte firmado y cerrado.');
    }

    private function ensureTechnicianCanSee(Request $request, ?int $assignedUserId): void
    {
        $user = $request->user();

        if (in_array($user->role, ['admin', 'management'], true)) {
            return;
        }

        abort_if($assignedUserId !== $user->id, 403);
    }
}
