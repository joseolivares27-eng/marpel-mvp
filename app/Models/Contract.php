<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(fn (Contract $contract) => app(\App\Services\OperationalContextValidator::class)->validate($contract));
    }

    protected $fillable = [
        'customer_id',
        'installation_id',
        'number',
        'type',
        'status',
        'start_date',
        'end_date',
        'billing_period',
        'monthly_fee',
        'coverages',
        'includes_emergency_service',
        'includes_preventive_maintenance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'monthly_fee' => 'decimal:2',
            'coverages' => 'array',
            'includes_emergency_service' => 'boolean',
            'includes_preventive_maintenance' => 'boolean',
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

    public function lines(): HasMany
    {
        return $this->hasMany(ContractLine::class);
    }
}
