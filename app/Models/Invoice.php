<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(fn (Invoice $invoice) => app(\App\Services\OperationalContextValidator::class)->validate($invoice));
    }

    protected $fillable = [
        'customer_id',
        'installation_id',
        'equipment_id',
        'number',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'subtotal',
        'tax',
        'total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
            'due_at' => 'date',
            'paid_at' => 'date',
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

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
