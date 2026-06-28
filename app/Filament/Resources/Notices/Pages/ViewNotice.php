<?php

namespace App\Filament\Resources\Notices\Pages;

use App\Filament\Resources\Notices\NoticeResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;

class ViewNotice extends ViewRecord
{
    protected static string $resource = NoticeResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('priority')->label('Prioridad'),
            TextEntry::make('status')->label('Estado'),
            TextEntry::make('customer.legal_name')->label('Cliente'),
            TextEntry::make('installation.name')->label('Instalacion'),
            TextEntry::make('installation.address')->label('Direccion'),
            TextEntry::make('equipment.name')->label('Equipo')->placeholder('Sin equipo concreto'),
            TextEntry::make('technician.name')->label('Tecnico')->placeholder('Sin asignar'),
            TextEntry::make('contact_phone')->label('Telefono contacto')->placeholder('Sin especificar'),
            TextEntry::make('reported_by')->label('Avisado por')->placeholder('Sin especificar'),
            TextEntry::make('description')->label('Averia')->columnSpanFull(),
            TextEntry::make('scheduled_at')->label('Fecha programada')->dateTime('d/m/Y H:i')->placeholder('Sin programar'),
        ])->columns(2);
    }
}
