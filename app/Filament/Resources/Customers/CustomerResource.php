<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\ManageCustomers;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
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
            TextInput::make('phone2')->label('Telefono 2')->tel()->maxLength(50),
            TextInput::make('primary_contact_name')->label('Contacto principal')->maxLength(255),
            TextInput::make('iban')->label('IBAN')->maxLength(50),
            TextInput::make('fiscal_address')
                ->label('Direccion')
                ->helperText('Direccion fiscal/administrativa del cliente.')
                ->columnSpanFull(),
            TextInput::make('city')->label('Localidad'),
            TextInput::make('province')->label('Provincia'),
            TextInput::make('postal_code')->label('Codigo postal'),
            Select::make('client_type')->label('Tipo')->options([
                'maintenance' => 'Mantenimiento',
                'repairs' => 'Reparaciones',
                'maintenance_repairs' => 'Mantenimiento + Reparaciones',
            ]),
            DatePicker::make('contract_start_date')->label('Fecha inicio contrato'),
            TextInput::make('monthly_amount')->label('Importe mensual')->numeric()->prefix('EUR'),
            TextInput::make('equipment_count')->label('Nº equipos')->numeric(),
            Textarea::make('equipment_description')->label('Descripcion equipos')->columnSpanFull(),
            Select::make('status')->label('Estado')->options([
                'active' => 'Activo',
                'inactive' => 'Inactivo',
            ])->default('active')->required(),
            TextInput::make('drive_folder_url')
                ->label('Carpeta Drive')
                ->helperText('Se rellena solo al sincronizar el contrato desde Notion, o puedes pegarla a mano.')
                ->url()
                ->columnSpanFull(),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),

            Repeater::make('installations')
                ->relationship()
                ->label('Instalaciones (direcciones de equipos a mantener)')
                ->helperText('Cada instalacion es una direccion distinta de la fiscal donde hay equipos que mantener.')
                ->schema([
                    TextInput::make('name')->label('Nombre instalacion')->required()->maxLength(255),
                    TextInput::make('address')->label('Direccion')->required()->columnSpanFull(),
                    TextInput::make('city')->label('Municipio'),
                    TextInput::make('province')->label('Provincia'),
                    TextInput::make('postal_code')->label('Codigo postal'),
                    TextInput::make('contact_name')->label('Contacto en la instalacion'),
                    TextInput::make('contact_phone')->label('Telefono contacto')->tel(),
                    TextInput::make('contact_email')->label('Email contacto')->email(),
                    Select::make('status')->label('Estado')->options([
                        'active' => 'Activa',
                        'inactive' => 'Inactiva',
                    ])->default('active')->required(),
                    Textarea::make('access_instructions')->label('Instrucciones de acceso')->columnSpanFull(),

                    Repeater::make('equipment')
                        ->relationship()
                        ->label('Equipos en esta instalacion')
                        ->helperText('Equipos que hay que mantener en esta direccion. El codigo interno se genera solo.')
                        ->schema([
                            Select::make('equipment_type_id')
                                ->label('Tipo')
                                ->relationship('type', 'name')
                                ->searchable()
                                ->preload(),
                            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                            TextInput::make('brand')->label('Marca'),
                            TextInput::make('model')->label('Modelo'),
                            TextInput::make('serial_number')->label('Numero de serie'),
                            Select::make('status')->label('Estado')->options([
                                'active' => 'Activo',
                                'inactive' => 'Inactivo',
                                'out_of_service' => 'Fuera de servicio',
                            ])->default('active')->required(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->addActionLabel('Anadir equipo')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->addActionLabel('Anadir instalacion')
                ->columnSpanFull(),
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
                TextColumn::make('installations_count')->label('Instalaciones')->counts('installations'),
                TextColumn::make('drive_folder_url')
                    ->label('Carpeta Drive')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'Abrir' : '-')
                    ->url(fn (Customer $record): ?string => $record->drive_folder_url)
                    ->openUrlInNewTab(),
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
