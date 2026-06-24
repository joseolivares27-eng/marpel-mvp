<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Notice;
use App\Models\Quote;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\WorkOrderAssigned;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkOrderService
{
    public function __construct(private readonly ReviewScheduler $reviewScheduler)
    {
    }

    public function startFromNotice(Notice $notice, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($notice, $technician): WorkOrder {
            $workOrder = $this->createFromNotice($notice, $technician, notifyTechnician: false);

            $notice->update([
                'status' => 'in_progress',
                'assigned_user_id' => $technician->id,
                'started_at' => now(),
            ]);

            return $this->open($workOrder, $technician);
        });
    }

    public function startFromReview(Review $review, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($review, $technician): WorkOrder {
            $workOrder = $this->createFromReview($review, $technician, notifyTechnician: false);

            $review->update([
                'status' => 'in_progress',
                'assigned_user_id' => $technician->id,
            ]);

            return $this->open($workOrder, $technician);
        });
    }

    public function createFromNotice(Notice $notice, User $technician, bool $notifyTechnician = true): WorkOrder
    {
        return DB::transaction(function () use ($notice, $technician, $notifyTechnician): WorkOrder {
            $workOrder = WorkOrder::firstOrCreate(
                ['notice_id' => $notice->id],
                [
                    'customer_id' => $notice->customer_id,
                    'installation_id' => $notice->installation_id,
                    'equipment_id' => $notice->equipment_id,
                    'assigned_user_id' => $technician->id,
                    'status' => 'open',
                    'result' => 'pending',
                    'observations' => $notice->description,
                ],
            );

            $workOrder->update([
                'assigned_user_id' => $technician->id,
                'status' => $workOrder->status === 'closed' ? 'closed' : 'open',
                'result' => $this->normalizeResult($workOrder->result),
            ]);

            $notice->update([
                'status' => $workOrder->status === 'closed' ? $notice->status : 'assigned',
                'assigned_user_id' => $technician->id,
            ]);

            if ($notifyTechnician && $workOrder->status !== 'closed') {
                $technician->notify(new WorkOrderAssigned($workOrder->refresh()));
            }

            return $workOrder->refresh();
        });
    }

    public function createFromReview(Review $review, User $technician, bool $notifyTechnician = true): WorkOrder
    {
        return DB::transaction(function () use ($review, $technician, $notifyTechnician): WorkOrder {
            $workOrder = WorkOrder::firstOrCreate(
                ['review_id' => $review->id],
                [
                    'customer_id' => $review->customer_id,
                    'installation_id' => $review->installation_id,
                    'equipment_id' => $review->equipment_id,
                    'assigned_user_id' => $technician->id,
                    'status' => 'open',
                    'result' => 'pending',
                    'observations' => $review->notes ?: 'Revision programada',
                ],
            );

            $workOrder->update([
                'assigned_user_id' => $technician->id,
                'status' => $workOrder->status === 'closed' ? 'closed' : 'open',
                'result' => $this->normalizeResult($workOrder->result),
            ]);

            $review->update([
                'status' => $workOrder->status === 'closed' ? $review->status : 'assigned',
                'assigned_user_id' => $technician->id,
            ]);

            if ($notifyTechnician && $workOrder->status !== 'closed') {
                $technician->notify(new WorkOrderAssigned($workOrder->refresh()));
            }

            return $workOrder->refresh();
        });
    }

    public function open(WorkOrder $workOrder, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $technician): WorkOrder {
            if ($workOrder->status === 'closed') {
                return $workOrder->refresh();
            }

            $workOrder->update([
                'assigned_user_id' => $workOrder->assigned_user_id ?: $technician->id,
                'status' => 'in_progress',
                'started_at' => $workOrder->started_at ?: now(),
            ]);

            if ($workOrder->notice) {
                $workOrder->notice->update([
                    'status' => 'in_progress',
                    'assigned_user_id' => $workOrder->assigned_user_id,
                    'started_at' => $workOrder->notice->started_at ?: now(),
                ]);
            }

            if ($workOrder->review) {
                $workOrder->review->update([
                    'status' => 'in_progress',
                    'assigned_user_id' => $workOrder->assigned_user_id,
                ]);
            }

            return $workOrder->refresh();
        });
    }

    /**
     * @param array<int, array{material_id?: int|null, description?: string|null, quantity?: numeric-string|float|int|null}> $materials
     */
    public function close(WorkOrder $workOrder, array $data, array $materials = []): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $data, $materials): WorkOrder {
            if (! $workOrder->customer_signature_path) {
                throw ValidationException::withMessages([
                    'customer_signature_path' => 'No se puede cerrar el parte sin firma del cliente.',
                ]);
            }

            $wasClosed = $workOrder->status === 'closed';
            $rawResult = $data['result'] ?? null;
            $result = $this->normalizeResult($rawResult);

            if ($materials !== []) {
                $this->saveMaterials($workOrder, $materials);
            }

            $workOrder->update([
                'status' => 'closed',
                'finished_at' => now(),
                'result' => $result,
                'work_performed' => $data['work_performed'] ?? $workOrder->work_performed,
                'observations' => $data['observations'] ?? $workOrder->observations,
            ]);

            if (! $wasClosed) {
                $this->decrementMaterialsStock($workOrder->refresh());
            }

            if ($workOrder->notice) {
                $workOrder->notice->update([
                    'status' => $rawResult === 'requires_quote' ? 'pending_quote' : 'completed',
                    'closed_at' => now(),
                    'requires_quote' => $rawResult === 'requires_quote',
                ]);
            }

            if ($workOrder->review) {
                $this->reviewScheduler->closeReview(
                    review: $workOrder->review,
                    result: $result,
                    notes: $data['observations'] ?? null,
                );

                if ($rawResult === 'incident') {
                    Notice::create([
                        'customer_id' => $workOrder->customer_id,
                        'installation_id' => $workOrder->installation_id,
                        'equipment_id' => $workOrder->equipment_id,
                        'reported_by' => 'Revision',
                        'channel' => 'technician',
                        'priority' => 'normal',
                        'status' => 'pending',
                        'description' => $data['observations'] ?: 'Incidencia detectada durante revision.',
                    ]);
                }
            }

            if ($rawResult === 'requires_quote') {
                $origin = array_filter([
                    'notice_id' => $workOrder->notice_id,
                    'review_id' => $workOrder->review_id,
                ]);

                $quoteData = [
                    'customer_id' => $workOrder->customer_id,
                    'installation_id' => $workOrder->installation_id,
                    'equipment_id' => $workOrder->equipment_id,
                    'number' => $this->nextQuoteNumber(),
                    'status' => 'draft',
                    'notes' => $data['observations'] ?: 'Presupuesto generado desde parte de trabajo.',
                ];

                $origin
                    ? Quote::firstOrCreate($origin, $quoteData)
                    : Quote::create($quoteData);
            }

            return $workOrder->refresh();
        });
    }

    /**
     * @param array<int, array{material_id?: int|null, description?: string|null, quantity?: numeric-string|float|int|null}> $materials
     */
    public function saveMaterials(WorkOrder $workOrder, array $materials): void
    {
        $lines = collect($materials)
            ->map(fn (array $materialLine): ?array => $this->materialLineData($materialLine))
            ->filter()
            ->values();

        $workOrder->materials()->delete();

        $lines->each(fn (array $line) => $workOrder->materials()->create($line));
    }

    /**
     * @param array{material_id?: int|null, description?: string|null, quantity?: numeric-string|float|int|null} $materialLine
     * @return array<string, mixed>|null
     */
    private function materialLineData(array $materialLine): ?array
    {
        $materialId = Arr::get($materialLine, 'material_id');
        $material = $materialId ? Material::find($materialId) : null;
        $quantity = (float) (Arr::get($materialLine, 'quantity') ?: 1);
        $description = trim((string) (Arr::get($materialLine, 'description') ?: $material?->name));

        if (! $material && $description === '') {
            return null;
        }

        return [
            'material_id' => $material?->id,
            'description' => $description !== '' ? $description : null,
            'quantity' => $quantity > 0 ? $quantity : 1,
            'unit_cost' => $material?->cost_price ?? 0,
            'unit_price' => $material?->sale_price ?? 0,
        ];
    }

    private function decrementMaterialsStock(WorkOrder $workOrder): void
    {
        $workOrder->loadMissing('materials.material');

        foreach ($workOrder->materials as $line) {
            if ($line->material) {
                $line->material->decrement('stock_quantity', (float) $line->quantity);
            }
        }
    }

    private function normalizeResult(?string $result): string
    {
        return match ($result) {
            'solved', 'solucionado', 'ok' => 'solved',
            'not_solved', 'no_solucionado', 'not_located', 'incident' => 'not_solved',
            default => 'pending',
        };
    }

    private function nextQuoteNumber(): string
    {
        $next = Quote::query()->whereYear('created_at', now()->year)->count() + 1;

        return sprintf('PTO-%d-%06d', now()->year, $next);
    }
}
