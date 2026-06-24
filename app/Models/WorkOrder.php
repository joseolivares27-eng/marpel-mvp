<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class WorkOrder extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (WorkOrder $workOrder): void {
            Log::info('MARPEL_WORK_ORDER_CREATING', [
                'customer_id' => $workOrder->customer_id,
                'installation_id' => $workOrder->installation_id,
                'equipment_id' => $workOrder->equipment_id,
                'notice_id' => $workOrder->notice_id,
                'review_id' => $workOrder->review_id,
                'quote_id' => $workOrder->quote_id,
                'assigned_user_id' => $workOrder->assigned_user_id,
                'status' => $workOrder->status,
            ]);
        });

        static::saving(function (WorkOrder $workOrder): void {
            Log::info('MARPEL_WORK_ORDER_VALIDATING', [
                'id' => $workOrder->id,
                'customer_id' => $workOrder->customer_id,
                'installation_id' => $workOrder->installation_id,
                'equipment_id' => $workOrder->equipment_id,
                'notice_id' => $workOrder->notice_id,
                'review_id' => $workOrder->review_id,
                'quote_id' => $workOrder->quote_id,
            ]);

            app(\App\Services\OperationalContextValidator::class)->validate($workOrder);

            Log::info('MARPEL_WORK_ORDER_VALIDATION_OK', [
                'id' => $workOrder->id,
            ]);
        });

        static::created(function (WorkOrder $workOrder): void {
            Log::info('MARPEL_WORK_ORDER_CREATED', [
                'id' => $workOrder->id,
                'customer_id' => $workOrder->customer_id,
                'installation_id' => $workOrder->installation_id,
            ]);
        });
    }

    protected $fillable = [
        'customer_id',
        'installation_id',
        'equipment_id',
        'notice_id',
        'review_id',
        'quote_id',
        'assigned_user_id',
        'status',
        'started_at',
        'finished_at',
        'result',
        'work_performed',
        'observations',
        'customer_name',
        'customer_signature_path',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkOrderPhoto::class);
    }

    public function invoiceLines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
