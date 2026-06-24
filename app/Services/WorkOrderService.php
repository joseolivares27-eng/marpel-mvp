<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Notice;
use App\Models\Quote;
use App\Models\Review;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class WorkOrderService
{
    public function __construct(private readonly ReviewScheduler $reviewScheduler)
    {
    }

    public function startFromNotice(Notice $notice, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($notice, $technician): WorkOrder {
            $notice->update([
                'status' => 'in_progress',
                'assigned_user_id' => $notice->assigned_user_id ?: $technician->id,
                'started_at' => now(),
            ]);

            return WorkOrder::firstOrCreate(
                ['notice_id' => $notice->id],
                [
                    'customer_id' => $notice->customer_id,
                    'installation_id' => $notice->installation_id,
                    'equipment_id' => $notice->equipment_id,
                    'assigned_user_id' => $technician->id,
                    'status' => 'open',
                    'started_at' => now(),
                ],
            );
        });
    }

    public function startFromReview(Review $review, User $technician): WorkOrder
    {
        return DB::transaction(function () use ($review, $technician): WorkOrder {
            $review->update([
                'status' => 'in_progress',
                'assigned_user_id' => $review->assigned_user_id ?: $technician->id,
            ]);

            return WorkOrder::firstOrCreate(
                ['review_id' => $review->id],
                [
                    'customer_id' => $review->customer_id,
                    'installation_id' => $review->installation_id,
                    'equipment_id' => $review->equipment_id,
                    'assigned_user_id' => $technician->id,
                    'status' => 'open',
                    'started_at' => now(),
                ],
            );
        });
    }

    /**
     * @param array<int, array{material_id?: int|null, description?: string|null, quantity?: numeric-string|float|int|null}> $materials
     */
    public function close(WorkOrder $workOrder, array $data, array $materials = []): WorkOrder
    {
        return DB::transaction(function () use ($workOrder, $data, $materials): WorkOrder {
            $result = $data['result'] ?? 'solved';

            $workOrder->update([
                'status' => 'closed',
                'finished_at' => now(),
                'result' => $result,
                'work_performed' => $data['work_performed'] ?? $workOrder->work_performed,
                'observations' => $data['observations'] ?? $workOrder->observations,
            ]);

            foreach ($materials as $materialLine) {
                $materialId = Arr::get($materialLine, 'material_id');
                $material = $materialId ? Material::find($materialId) : null;
                $quantity = (float) (Arr::get($materialLine, 'quantity') ?: 1);
                $description = Arr::get($materialLine, 'description') ?: $material?->name;

                if (! $description) {
                    continue;
                }

                $workOrder->materials()->create([
                    'material_id' => $material?->id,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_cost' => $material?->cost_price ?? 0,
                    'unit_price' => $material?->sale_price ?? 0,
                ]);

                if ($material) {
                    $material->decrement('stock_quantity', $quantity);
                }
            }

            if ($workOrder->notice) {
                $workOrder->notice->update([
                    'status' => $result === 'requires_quote' ? 'pending_quote' : 'closed',
                    'closed_at' => now(),
                    'requires_quote' => $result === 'requires_quote',
                ]);
            }

            if ($workOrder->review) {
                $this->reviewScheduler->closeReview(
                    review: $workOrder->review,
                    result: $result,
                    notes: $data['observations'] ?? null,
                );

                if ($result === 'incident') {
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

            if ($result === 'requires_quote') {
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

    private function nextQuoteNumber(): string
    {
        $next = Quote::query()->whereYear('created_at', now()->year)->count() + 1;

        return sprintf('PTO-%d-%06d', now()->year, $next);
    }
}
