<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'installation_id',
        'equipment_type_id',
        'code',
        'name',
        'category',
        'brand',
        'model',
        'serial_number',
        'internal_location',
        'installed_at',
        'last_review_at',
        'next_review_at',
        'revision_periodicity',
        'revision_interval_days',
        'custom_revision_interval_days',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (Equipment $equipment): void {
            if (! $equipment->code) {
                $equipment->code = app(\App\Services\EquipmentCodeGenerator::class)->next();
            }

            if (! $equipment->revision_periodicity) {
                $equipment->revision_periodicity = $equipment->type?->default_revision_periodicity ?? 'semiannual';
            }

            if (! $equipment->revision_interval_days) {
                $equipment->revision_interval_days = $equipment->type?->default_revision_interval_days ?? 180;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'installed_at' => 'date',
            'last_review_at' => 'date',
            'next_review_at' => 'date',
        ];
    }

    public function installation(): BelongsTo
    {
        return $this->belongsTo(Installation::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class, 'equipment_type_id');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function photos(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkOrderPhoto::class,
            WorkOrder::class,
            'equipment_id',
            'work_order_id',
            'id',
            'id',
        );
    }

    public function usedMaterials(): HasManyThrough
    {
        return $this->hasManyThrough(
            WorkOrderMaterial::class,
            WorkOrder::class,
            'equipment_id',
            'work_order_id',
            'id',
            'id',
        );
    }

    public function invoiceLines(): HasManyThrough
    {
        return $this->hasManyThrough(
            InvoiceLine::class,
            WorkOrder::class,
            'equipment_id',
            'work_order_id',
            'id',
            'id',
        );
    }

    public function isCustomType(): bool
    {
        return mb_strtolower($this->type?->name ?? '') === 'personalizado';
    }
}
