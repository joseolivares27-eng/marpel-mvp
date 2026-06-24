<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Notice extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(fn (Notice $notice) => app(\App\Services\OperationalContextValidator::class)->validate($notice));
    }

    protected $fillable = [
        'customer_id',
        'installation_id',
        'equipment_id',
        'contract_id',
        'reported_by',
        'contact_name',
        'contact_phone',
        'channel',
        'priority',
        'status',
        'description',
        'assigned_user_id',
        'scheduled_at',
        'started_at',
        'closed_at',
        'requires_quote',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'closed_at' => 'datetime',
            'requires_quote' => 'boolean',
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function workOrder(): HasOne
    {
        return $this->hasOne(WorkOrder::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }
}
