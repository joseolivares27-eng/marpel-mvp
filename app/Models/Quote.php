<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quote extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(fn (Quote $quote) => app(\App\Services\OperationalContextValidator::class)->validate($quote));
    }

    protected $fillable = [
        'customer_id',
        'installation_id',
        'equipment_id',
        'notice_id',
        'review_id',
        'number',
        'status',
        'valid_until',
        'sent_at',
        'accepted_at',
        'subtotal',
        'tax',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
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

    public function lines(): HasMany
    {
        return $this->hasMany(QuoteLine::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
