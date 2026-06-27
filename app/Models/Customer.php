<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'legal_name',
        'trade_name',
        'tax_id',
        'fiscal_address',
        'email',
        'phone',
        'primary_contact_name',
        'notes',
        'status',
        'notion_page_id',
        'drive_folder_url',
    ];

    public function installations(): HasMany
    {
        return $this->hasMany(Installation::class);
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function activeContracts(): HasMany
    {
        return $this->hasMany(Contract::class)
            ->where('status', 'active')
            ->whereDate('start_date', '<=', now())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', now()));
    }

    public function isSubscriber(): bool
    {
        return $this->activeContracts()->exists();
    }
}
