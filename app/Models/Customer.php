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
        'city',
        'province',
        'postal_code',
        'email',
        'phone',
        'phone2',
        'iban',
        'primary_contact_name',
        'client_type',
        'contract_start_date',
        'monthly_amount',
        'equipment_count',
        'equipment_description',
        'notes',
        'status',
        'notion_page_id',
        'drive_folder_url',
    ];

    protected function casts(): array
    {
        return [
            'contract_start_date' => 'date',
            'monthly_amount' => 'decimal:2',
        ];
    }

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
