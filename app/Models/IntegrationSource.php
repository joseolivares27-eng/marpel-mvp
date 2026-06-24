<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_active',
        'settings',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function events(): HasMany
    {
        return $this->hasMany(IntegrationEvent::class);
    }
}
