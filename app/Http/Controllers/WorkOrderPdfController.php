<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Services\WorkOrderPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkOrderPdfController extends Controller
{
    public function __invoke(Request $request, WorkOrder $workOrder, WorkOrderPdfService $pdfService): StreamedResponse
    {
        $this->ensureCanDownload($request, $workOrder);

        abort_if($workOrder->status !== 'closed', 404, 'El PDF solo esta disponible para partes cerrados.');

        $disk = Storage::disk('public');
        $path = $workOrder->pdf_path;

        if ($path && $disk->exists($path) && $this->pdfIsStale($workOrder, $path)) {
            $path = null;
        }

        if (! $path || ! $disk->exists($path)) {
            $path = $pdfService->generate($workOrder->refresh());
        }

        abort_if(! $disk->exists($path), 404, 'No se encontro el PDF del parte.');

        return $disk->response($path, "parte-{$workOrder->id}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function pdfIsStale(WorkOrder $workOrder, string $pdfPath): bool
    {
        $disk = Storage::disk('public');
        $generatedAt = $disk->lastModified($pdfPath);

        if (! $generatedAt) {
            return true;
        }

        $updatedAt = $workOrder->signed_at ?? $workOrder->updated_at;

        return $updatedAt && $updatedAt->getTimestamp() > $generatedAt;
    }

    private function ensureCanDownload(Request $request, WorkOrder $workOrder): void
    {
        $user = $request->user();

        abort_if(! $user, 403);

        if (in_array($user->role, ['admin', 'management'], true)) {
            return;
        }

        abort_if((int) $workOrder->assigned_user_id !== (int) $user->id, 403);
    }
}
