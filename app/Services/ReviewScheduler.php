<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\Review;
use Carbon\CarbonImmutable;

class ReviewScheduler
{
    public function calculateNextReviewDate(Equipment $equipment, ?CarbonImmutable $from = null): CarbonImmutable
    {
        $baseDate = $from ?? CarbonImmutable::now();
        $periodicity = $equipment->revision_periodicity ?: ($equipment->type?->default_revision_periodicity ?? 'semiannual');

        return match ($periodicity) {
            'monthly' => $baseDate->addMonthNoOverflow(),
            'quarterly' => $baseDate->addMonthsNoOverflow(3),
            'semiannual' => $baseDate->addMonthsNoOverflow(6),
            'annual' => $baseDate->addYearNoOverflow(),
            'custom' => $baseDate->addDays(
                $equipment->custom_revision_interval_days
                    ?: $equipment->revision_interval_days
                    ?: $equipment->type?->default_custom_revision_interval_days
                    ?: $equipment->type?->default_revision_interval_days
                    ?: 180,
            ),
            default => $baseDate->addDays($equipment->revision_interval_days ?: 180),
        };
    }

    public function closeReview(Review $review, string $result, ?string $notes = null): Review
    {
        $performedAt = CarbonImmutable::now();
        $equipment = $review->equipment;
        $nextReviewAt = $this->calculateNextReviewDate($equipment, $performedAt);

        $review->update([
            'status' => 'closed',
            'result' => $result,
            'notes' => $notes,
            'performed_at' => $performedAt,
            'next_review_at' => $nextReviewAt,
        ]);

        $equipment->update([
            'last_review_at' => $performedAt->toDateString(),
            'next_review_at' => $nextReviewAt->toDateString(),
        ]);

        return $review->refresh();
    }
}
