<?php

namespace App\Filament\Resources\Equipment;

use App\Filament\Resources\Equipment\Pages\ManageEquipment;
use App\Filament\Resources\Equipment\Pages\ViewEquipmentHistory;
use App\Models\Equipment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string|\UnitEnum|null $navigationGroup = 'Base';

    protected static ?string $modelLabel = 'equipo';

    protected static ?string $pluralModelLabel = 'equipos';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload()->required(),
            Select::make('equipment_type_id')->label('Tipo')->relationship('type', 'name')->searchable()->preload(),
            TextInput::make('code')->label('Codigo interno')->disabled()->dehydrated(false)->placeholder('Automatico'),
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('category')->label('Categoria libre'),
            TextInput::make('brand')->label('Marca'),
            TextInput::make('model')->label('Modelo'),
            TextInput::make('serial_number')->label('Numero de serie'),
            TextInput::make('internal_location')->label('Ubicacion interna'),
            DatePicker::make('installed_at')->label('Fecha instalacion'),
            DatePicker::make('last_review_at')->label('Ultima revision'),
            DatePicker::make('next_review_at')->label('Proxima revision'),
            Select::make('revision_periodicity')->label('Periodicidad')->options([
                'monthly' => 'Mensual',
                'quarterly' => 'Trimestral',
                'semiannual' => 'Semestral',
                'annual' => 'Anual',
                'custom' => 'Personalizada',
            ])->default('semiannual')->required(),
            TextInput::make('revision_interval_days')->label('Periodicidad dias')->numeric()->default(180),
            TextInput::make('custom_revision_interval_days')->label('Dias personalizada')->numeric(),
            Select::make('status')->label('Estado')->options(['active' => 'Activo', 'inactive' => 'Inactivo', 'out_of_service' => 'Fuera de servicio'])->default('active')->required(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Codigo')->searchable()->sortable(),
                TextColumn::make('name')->label('Equipo')->searchable()->sortable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable()->sortable(),
                TextColumn::make('type.name')->label('Tipo')->sortable(),
                TextColumn::make('category')->label('Categoria')->searchable()->toggleable(),
                TextColumn::make('serial_number')->label('Serie')->searchable(),
                TextColumn::make('next_review_at')->label('Proxima revision')->date()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
            ])
            ->recordActions([
                Action::make('history')
                    ->label('Historial')
                    ->icon('heroicon-o-clock')
                    ->url(fn (Equipment $record): string => self::getUrl('history', ['record' => $record])),
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
            'index' => ManageEquipment::route('/'),
            'history' => ViewEquipmentHistory::route('/{record}/historial'),
        ];
    }
}
