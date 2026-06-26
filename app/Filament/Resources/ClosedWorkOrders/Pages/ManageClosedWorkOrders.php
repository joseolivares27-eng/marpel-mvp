<?php

namespace App\Filament\Resources\ClosedWorkOrders\Pages;

use App\Filament\Resources\ClosedWorkOrders\ClosedWorkOrderResource;
use Filament\Resources\Pages\ManageRecords;

class ManageClosedWorkOrders extends ManageRecords
{
    protected static string $resource = ClosedWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
