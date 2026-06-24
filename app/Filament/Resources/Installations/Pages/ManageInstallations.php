<?php

namespace App\Filament\Resources\Installations\Pages;

use App\Filament\Resources\Installations\InstallationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageInstallations extends ManageRecords
{
    protected static string $resource = InstallationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
