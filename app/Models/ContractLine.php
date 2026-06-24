<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'equipment_type_id',
        'description',
        'quantity',
        'unit_price',
        'revision_interval_days',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }
}
