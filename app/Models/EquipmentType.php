<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'icon',
        'default_revision_periodicity',
        'default_revision_interval_days',
        'default_custom_revision_interval_days',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
