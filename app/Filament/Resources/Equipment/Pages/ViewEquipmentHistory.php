<?php

namespace App\Filament\Resources\Equipment\Pages;

use App\Filament\Resources\Equipment\EquipmentResource;
use App\Models\Equipment;
use Filament\Resources\Pages\Page;

class ViewEquipmentHistory extends Page
{
    protected static string 
    $resource = EquipmentResource::class;

    protected  string $view = 'filament.resources.equipment.pages.view-equipment-history';

    public Equipment $record;

    public function mount(Equipment $record): void
    {
        $this->record = $record->load([
            'installation.customer',
            'type',
            'notices.technician',
            'reviews.technician',
            'workOrders.technician',
            'workOrders.materials.material',
            'workOrders.photos',
            'quotes',
            'invoiceLines.invoice',
        ]);
    }

    public function getTitle(): string
    {
        return 'Historial '.$this->record->code;
    }
}
