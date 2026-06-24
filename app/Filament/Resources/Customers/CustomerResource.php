<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|\UnitEnum|null $navigationGroup = 'Base';

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('legal_name')->label('Razon social')->required()->maxLength(255),
            TextInput::make('trade_name')->label('Nombre comercial')->maxLength(255),
            TextInput::make('tax_id')->label('CIF/NIF')->maxLength(50),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('phone')->label('Telefono')->tel()->maxLength(50),
            TextInput::make('primary_contact_name')->label('Contacto principal')->maxLength(255),
            TextInput::make('fiscal_address')->label('Direccion fiscal')->columnSpanFull(),
            Select::make('status')->label('Estado')->options([
                'active' => 'Activo',
                'inactive' => 'Inactivo',
            ])->default('active')->required(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legal_name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('tax_id')->label('CIF/NIF')->searchable(),
                TextColumn::make('phone')->label('Telefono')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('created_at')->label('Alta')->date()->sortable(),
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
            'index' => ManageCustomers::route('/'),
        ];
    }
}
