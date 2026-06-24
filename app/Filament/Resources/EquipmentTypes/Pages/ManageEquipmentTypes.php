<?php

namespace App\Filament\Resources\EquipmentTypes\Pages;

use App\Filament\Resources\EquipmentTypes\EquipmentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEquipmentTypes extends ManageRecords
{
    protected static string $resource = EquipmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
