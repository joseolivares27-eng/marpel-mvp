<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Installation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'name',
        'address',
        'city',
        'province',
        'postal_code',
        'contact_name',
        'contact_phone',
        'contact_email',
        'access_hours',
        'access_instructions',
        'latitude',
        'longitude',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function mapsUrl(): string
    {
        $query = $this->latitude && $this->longitude
            ? "{$this->latitude},{$this->longitude}"
            : $this->address.' '.$this->city.' '.$this->postal_code;

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }

    public function wazeUrl(): string
    {
        $query = $this->latitude && $this->longitude
            ? "ll={$this->latitude}%2C{$this->longitude}&navigate=yes"
            : 'q='.urlencode($this->address.' '.$this->city);

        return 'https://waze.com/ul?'.$query;
    }
}
