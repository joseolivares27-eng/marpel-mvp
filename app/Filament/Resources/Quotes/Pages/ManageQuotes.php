<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageQuotes extends ManageRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
