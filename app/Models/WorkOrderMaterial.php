<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class WorkOrderMaterial extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (WorkOrderMaterial $materialLine): void {
            Log::info('MARPEL_WORK_ORDER_MATERIAL_SAVING', [
                'id' => $materialLine->id,
                'work_order_id' => $materialLine->work_order_id,
                'material_id' => $materialLine->material_id,
                'description' => $materialLine->description,
                'quantity' => $materialLine->quantity,
                'unit_cost' => $materialLine->unit_cost,
                'unit_price' => $materialLine->unit_price,
            ]);
        });
    }

    protected $fillable = [
        'work_order_id',
        'material_id',
        'description',
        'quantity',
        'unit_cost',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
