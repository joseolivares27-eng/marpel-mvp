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

        $notices = Notice::with(['customer', 'installation', 'equipment', 'workOrder'])
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['completed', 'resolved', 'cancelled'])
            ->orderByRaw("case when priority = 'urgent' then 0 else 1 end")
            ->orderBy('scheduled_at')
            ->get();

        $reviews = Review::with(['customer', 'installation', 'equipment', 'workOrder'])
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

    public function notices(Request $request): View
    {
        $user = $request->user();

        $notices = Notice::with(['customer', 'installation', 'equipment', 'workOrder'])
            ->where('assigned_user_id', $user->id)
            ->whereNotIn('status', ['completed', 'resolved', 'cancelled'])
            ->orderByRaw("case when priority = 'urgent' then 0 else 1 end")
            ->orderBy('scheduled_at')
            ->get();

        return view('technician.notices', compact('notices'));
    }

    public function showNotice(Request $request, Notice $notice): View
    {
        $this->ensureTechnicianCanSee($request, $notice->assigned_user_id);

        $notice->load([
            'customer',
            'installation',
            'workOrder',
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
            'result' => ['nullable', 'string', 'in:pending,solved,not_solved'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'signature_data' => ['nullable', 'string'],
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
            'result' => $validated['result'] ?? 'pending',
        ]);

        $service->saveMaterials($workOrder, $validated['materials'] ?? []);

        if (! empty($validated['signature_data'])) {
            if (empty($validated['customer_name'])) {
                return back()
                    ->withErrors(['customer_name' => 'Indica el nombre del firmante.'])
                    ->withInput();
            }

            if (! $this->storeSignatureImage($workOrder, $validated['signature_data'], $validated['customer_name'])) {
                return back()
                    ->withErrors(['signature_data' => 'La firma no es valida. Limpia la firma y vuelve a firmar.'])
                    ->withInput();
            }
        } elseif (! empty($validated['customer_name']) && $workOrder->customer_signature_path) {
            $workOrder->update(['customer_name' => $validated['customer_name']]);
        }

        $workOrder->refresh();

        if ($request->input('action') === 'close') {
            if (! $workOrder->customer_signature_path) {
                if (empty($validated['customer_name']) || empty($validated['signature_data'])) {
                    return back()
                        ->withErrors(['signature_data' => 'Falta el nombre y la firma del cliente para cerrar el parte.'])
                        ->withInput();
                }

                $signatureStored = $this->storeSignatureImage($workOrder, $validated['signature_data'], $validated['customer_name']);

                if (! $signatureStored) {
                    return back()
                        ->withErrors(['signature_data' => 'La firma no es valida. Limpia la firma y vuelve a firmar.'])
                        ->withInput();
                }
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

        abort_if(! $this->storeSignatureImage($workOrder, $validated['signature_data'], $validated['customer_name']), 422, 'Firma no valida.');

        $workOrder->refresh();

        $service->close($workOrder, [
            'work_performed' => $workOrder->work_performed,
            'observations' => $workOrder->observations,
            'result' => $workOrder->result ?: 'pending',
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

    private function storeSignatureImage(WorkOrder $workOrder, string $signatureData, string $customerName): bool
    {
        $signature = preg_replace('#^data:image/\w+;base64,#i', '', $signatureData);
        $binary = base64_decode($signature, true);

        if ($binary === false) {
            return false;
        }

        $oldPath = $workOrder->customer_signature_path;
        $path = 'signatures/'.Str::uuid().'.png';
        Storage::disk('public')->put($path, $binary);

        $workOrder->update([
            'customer_name' => $customerName,
            'customer_signature_path' => $path,
            'signed_at' => now(),
        ]);

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return true;
    }
}
