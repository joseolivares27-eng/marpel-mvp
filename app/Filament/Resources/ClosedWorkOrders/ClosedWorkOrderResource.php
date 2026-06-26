<?php

namespace App\Filament\Resources\ClosedWorkOrders;

use App\Filament\Resources\ClosedWorkOrders\Pages\ManageClosedWorkOrders;
use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClosedWorkOrderResource extends WorkOrderResource
{
    protected static ?string $model = WorkOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Base';

    protected static ?string $slug = 'closed-work-orders';

    protected static ?string $modelLabel = 'parte cerrado';

    protected static ?string $pluralModelLabel = 'partes cerrados';

    protected static ?int $navigationSort = 6;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('status', 'closed');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('N parte')
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Fecha inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Fecha cierre')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('customer.legal_name')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('installation.name')
                    ->label('Instalacion')
                    ->searchable(),
                TextColumn::make('equipment.name')
                    ->label('Equipo')
                    ->searchable(),
                TextColumn::make('technician.name')
                    ->label('Tecnico')
                    ->searchable(),
                TextColumn::make('origin_label')
                    ->label('Origen')
                    ->badge(),
                TextColumn::make('result')
                    ->label('Resultado')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::resultLabel($state))
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Ver parte')
                    ->icon('heroicon-o-eye'),
                Action::make('download_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (WorkOrder $record): string => route('work-orders.pdf.download', $record))
                    ->visible(fn (WorkOrder $record): bool => filled($record->pdf_path)),
            ]);
    }

    private static function resultLabel(?string $state): string
    {
        return [
            'pending' => 'Pendiente',
            'solved' => 'Solucionado',
            'unresolved' => 'No solucionado',
            'not_solved' => 'No solucionado',
            'cancelled' => 'Anulado',
            'anulado' => 'Anulado',
            'solucionado' => 'Solucionado',
            'pendiente' => 'Pendiente',
            'no_solucionado' => 'No solucionado',
            'ok' => 'Solucionado',
            'pending_material' => 'Pendiente',
            'requires_quote' => 'Pendiente',
            'not_located' => 'No solucionado',
            'incident' => 'No solucionado',
        ][$state] ?? 'Pendiente';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClosedWorkOrders::route('/'),
        ];
    }
}
