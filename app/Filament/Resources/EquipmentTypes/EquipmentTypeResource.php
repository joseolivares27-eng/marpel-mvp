<?php

namespace App\Filament\Resources\EquipmentTypes;

use App\Filament\Resources\EquipmentTypes\Pages\ManageEquipmentTypes;
use App\Models\EquipmentType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EquipmentTypeResource extends Resource
{
    protected static ?string $model = EquipmentType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Base';

    protected static ?string $modelLabel = 'tipo de equipo';

    protected static ?string $pluralModelLabel = 'tipos de equipo';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('category')->label('Categoria')->maxLength(255),
            TextInput::make('icon')->label('Icono')->placeholder('heroicon-o-cog-6-tooth')->maxLength(255),
            Select::make('default_revision_periodicity')->label('Periodicidad por defecto')->options([
                'monthly' => 'Mensual',
                'quarterly' => 'Trimestral',
                'semiannual' => 'Semestral',
                'annual' => 'Anual',
                'custom' => 'Personalizada',
            ])->default('semiannual')->required(),
            TextInput::make('default_revision_interval_days')->label('Dias por defecto')->numeric()->default(180)->required(),
            TextInput::make('default_custom_revision_interval_days')->label('Dias si personalizada')->numeric(),
            Toggle::make('is_active')->label('Activo')->default(true),
            Textarea::make('description')->label('Descripcion')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Tipo')->searchable()->sortable(),
                TextColumn::make('category')->label('Categoria')->searchable()->sortable(),
                TextColumn::make('default_revision_periodicity')->label('Periodicidad')->badge()->sortable(),
                TextColumn::make('default_revision_interval_days')->label('Dias')->sortable(),
                TextColumn::make('icon')->label('Icono')->toggleable(),
                IconColumn::make('is_active')->label('Activo')->boolean(),
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
            'index' => ManageEquipmentTypes::route('/'),
        ];
    }
}
