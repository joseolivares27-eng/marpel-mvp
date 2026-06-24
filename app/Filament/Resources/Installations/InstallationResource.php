<?php

namespace App\Filament\Resources\Installations;

use App\Filament\Resources\Installations\Pages\ManageInstallations;
use App\Models\Installation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InstallationResource extends Resource
{
    protected static ?string $model = Installation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Base';

    protected static ?string $modelLabel = 'instalacion';

    protected static ?string $pluralModelLabel = 'instalaciones';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Instalacion')->required()->maxLength(255),
            TextInput::make('address')->label('Direccion')->required()->columnSpanFull(),
            TextInput::make('city')->label('Municipio'),
            TextInput::make('province')->label('Provincia'),
            TextInput::make('postal_code')->label('Codigo postal'),
            Select::make('status')->label('Estado')->options(['active' => 'Activa', 'inactive' => 'Inactiva'])->default('active')->required(),
            TextInput::make('contact_name')->label('Contacto'),
            TextInput::make('contact_phone')->label('Telefono contacto')->tel(),
            TextInput::make('contact_email')->label('Email contacto')->email(),
            TextInput::make('access_hours')->label('Horario acceso'),
            Textarea::make('access_instructions')->label('Instrucciones de acceso')->columnSpanFull(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Instalacion')->searchable()->sortable(),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('address')->label('Direccion')->searchable(),
                TextColumn::make('contact_phone')->label('Telefono'),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
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
            'index' => ManageInstallations::route('/'),
        ];
    }
}
