<?php

namespace App\Filament\Resources\Contracts;

use App\Filament\Resources\Contracts\Pages\ManageContracts;
use App\Models\Contract;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $modelLabel = 'contrato';

    protected static ?string $pluralModelLabel = 'contratos';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload(),
            TextInput::make('number')->label('Numero')->required()->maxLength(100),
            Select::make('type')->label('Tipo')->options([
                'maintenance' => 'Mantenimiento',
                'emergency' => 'Urgencias',
                'full' => 'Integral',
            ])->default('maintenance')->required(),
            Select::make('status')->label('Estado')->options([
                'active' => 'Activo',
                'paused' => 'Pausado',
                'ended' => 'Finalizado',
            ])->default('active')->required(),
            DatePicker::make('start_date')->label('Inicio')->required(),
            DatePicker::make('end_date')->label('Fin'),
            Select::make('billing_period')->label('Facturacion')->options([
                'monthly' => 'Mensual',
                'quarterly' => 'Trimestral',
                'yearly' => 'Anual',
            ])->default('monthly')->required(),
            TextInput::make('monthly_fee')->label('Cuota mensual')->numeric()->prefix('EUR')->default(0),
            CheckboxList::make('coverages')->label('Coberturas')->options([
                'preventive_maintenance' => 'Mantenimiento preventivo',
                'emergency_service' => 'Avisos urgentes',
                'labor' => 'Mano de obra',
                'materials' => 'Materiales',
                'annual_report' => 'Informe anual',
                'priority_support' => 'Prioridad de atencion',
            ])->columns(2)->columnSpanFull(),
            Toggle::make('includes_emergency_service')->label('Incluye urgencias'),
            Toggle::make('includes_preventive_maintenance')->label('Incluye preventivo')->default(true),
            Repeater::make('lines')->label('Lineas de contrato')->relationship()->schema([
                Select::make('equipment_type_id')->label('Tipo equipo')->relationship('equipmentType', 'name')->searchable()->preload(),
                TextInput::make('description')->label('Descripcion')->required(),
                TextInput::make('quantity')->label('Cantidad')->numeric()->default(1),
                TextInput::make('unit_price')->label('Precio')->numeric()->prefix('EUR')->default(0),
                TextInput::make('revision_interval_days')->label('Periodicidad revision')->numeric(),
            ])->columns(2)->columnSpanFull(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('monthly_fee')->label('Cuota')->money('EUR')->sortable(),
                IconColumn::make('includes_preventive_maintenance')->label('Preventivo')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageContracts::route('/'),
        ];
    }
}
