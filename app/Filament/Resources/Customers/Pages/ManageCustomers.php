<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ManageCustomers extends ManageRecords
{
    protected static string $resource = CustomerResource::class;

    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'active'))
                ->badge(fn (): int => Customer::query()->where('status', 'active')->count()),
            'prospect' => Tab::make('Prospect')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'prospect'))
                ->badge(fn (): int => Customer::query()->where('status', 'prospect')->count()),
            'todos' => Tab::make('Todos')
                ->badge(fn (): int => Customer::query()->count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'activos';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
