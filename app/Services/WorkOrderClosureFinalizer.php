<?php

namespace App\Services;

use App\Models\WorkOrder;
use Illuminate\Support\Facades\DB;

class WorkOrderClosureFinalizer
{
    public function __construct(
        private readonly ReviewScheduler $reviewScheduler,
        private readonly WorkOrderPdfService $workOrderPdfService,
    ) {
    }

    public function finalize(WorkOrder $workOrder): WorkOrder
    {
        return DB::transaction(function () use ($workOrder): WorkOrder {
            $workOrder = $workOrder->refresh();

            if ($workOrder->status !== 'closed') {
                return $workOrder;
            }

            if ($workOrder->notice) {
                $workOrder->notice->update([
                    'status' => $this->noticeStatusForResult($workOrder->result),
                    'closed_at' => now(),
                    'requires_quote' => false,
                ]);
            }

            if ($workOrder->review && $workOrder->review->status !== 'closed') {
                $this->reviewScheduler->closeReview(
                    review: $workOrder->review,
                    result: $workOrder->result,
                    notes: $workOrder->observations,
                );
            }

            $this->workOrderPdfService->generate($workOrder->refresh());

            return $workOrder->refresh();
        });
    }

    private function noticeStatusForResult(?string $result): string
    {
        return match ($result) {
            'solved' => 'resolved',
            'cancelled' => 'cancelled',
            'unresolved' => 'completed',
            default => 'pending',
        };
    }
}
