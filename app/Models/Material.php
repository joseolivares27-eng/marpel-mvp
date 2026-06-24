<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'unit',
        'cost_price',
        'sale_price',
        'stock_quantity',
        'minimum_stock',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock_quantity' => 'decimal:2',
            'minimum_stock' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function workOrderMaterials(): HasMany
    {
        return $this->hasMany(WorkOrderMaterial::class);
    }
}
