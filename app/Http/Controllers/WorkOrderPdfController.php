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

        if ($path && $disk->exists($path) && $this->pdfNeedsSignatureRefresh($workOrder, $path)) {
            $path = null;
        }

        if (! $path || ! $disk->exists($path)) {
            $path = $pdfService->generate($workOrder->refresh());
        }

        abort_if(! $disk->exists($path), 404, 'No se encontro el PDF del parte.');

        return $disk->download($path, "parte-{$workOrder->id}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function pdfNeedsSignatureRefresh(WorkOrder $workOrder, string $pdfPath): bool
    {
        if (! $workOrder->customer_signature_path) {
            return false;
        }

        $pdf = Storage::disk('public')->get($pdfPath);

        if (! is_string($pdf)) {
            return true;
        }

        if (str_contains($pdf, 'Firma guardada, pero no se pudo incrustar')) {
            return true;
        }

        return ! str_contains($pdf, '/Subtype /Image');
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
