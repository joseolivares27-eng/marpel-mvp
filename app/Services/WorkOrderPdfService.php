<?php

namespace App\Services;

use App\Models\WorkOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class WorkOrderPdfService
{
    public function generate(WorkOrder $workOrder): string
    {
        $workOrder->loadMissing([
            'customer',
            'installation',
            'equipment',
            'technician',
            'notice',
            'review',
            'materials.material',
            'photos',
        ]);

        $statusKey = $this->resultStatusKey($workOrder->result);

        $pdf = Pdf::loadView('pdf.work-order', [
            'workOrder' => $workOrder,
            'logoDataUri' => $this->logoDataUri(),
            'generatedAt' => now()->format('d/m/Y H:i'),
            'startedAt' => $this->formatDate($workOrder->started_at),
            'finishedAt' => $this->formatDate($workOrder->finished_at),
            'signedAt' => $this->formatDate($workOrder->signed_at),
            'noticeDescription' => $workOrder->notice?->description ?: $workOrder->observations,
            'statusLabel' => $this->resultLabel($statusKey),
            'statusBadgeClass' => $statusKey,
            'materialRows' => $this->materialRows($workOrder),
            'signatureDataUri' => $this->imageDataUri($workOrder->customer_signature_path),
            'photoDataUris' => $workOrder->photos
                ->map(fn ($photo) => $this->imageDataUri($photo->path))
                ->filter()
                ->values(),
        ])->setPaper('a4');

        $path = "work-orders/{$workOrder->id}/parte-{$workOrder->id}.pdf";

        if (! Storage::disk('public')->put($path, $pdf->output())) {
            throw new RuntimeException('No se pudo guardar el PDF del parte de trabajo.');
        }

        $workOrder->forceFill(['pdf_path' => $path])->saveQuietly();

        return $path;
    }

    /**
     * @return Collection<int, array{name: string, description: string, quantity: string}>
     */
    private function materialRows(WorkOrder $workOrder): Collection
    {
        return $workOrder->materials->map(fn ($line) => [
            'name' => $line->material?->name ?: 'Material manual',
            'description' => $line->description ?: '-',
            'quantity' => rtrim(rtrim((string) $line->quantity, '0'), '.') ?: '0',
        ])->values();
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('images/marpel-logo.png');

        if (! is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) file_get_contents($path));
    }

    private function imageDataUri(?string $diskPath): ?string
    {
        if (! $diskPath) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($diskPath)) {
            return null;
        }

        $contents = $disk->get($diskPath);
        $mime = $disk->mimeType($diskPath) ?: 'image/png';

        return "data:{$mime};base64,".base64_encode((string) $contents);
    }

    private function formatDate(mixed $date): string
    {
        return $date ? $date->format('d/m/Y H:i') : '-';
    }

    private function resultStatusKey(?string $result): string
    {
        return match ($result) {
            'solved', 'solucionado', 'ok' => 'solved',
            'unresolved', 'not_solved', 'no_solucionado', 'not_located', 'incident' => 'unresolved',
            'cancelled', 'anulado' => 'cancelled',
            default => 'pending',
        };
    }

    private function resultLabel(string $statusKey): string
    {
        return [
            'pending' => 'Pendiente',
            'solved' => 'Solucionado',
            'unresolved' => 'No solucionado',
            'cancelled' => 'Anulado',
        ][$statusKey] ?? 'Pendiente';
    }
}
