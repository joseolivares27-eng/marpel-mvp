<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration_source_id',
        'direction',
        'event_type',
        'status',
        'related_type',
        'related_id',
        'payload',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(IntegrationSource::class, 'integration_source_id');
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
