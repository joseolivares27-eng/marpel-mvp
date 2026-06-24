<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkOrder extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(fn (WorkOrder $workOrder) => app(\App\Services\OperationalContextValidator::class)->validate($workOrder));
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
