<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Invoice;
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

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $modelLabel = 'factura';

    protected static ?string $pluralModelLabel = 'facturas';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload(),
            Select::make('equipment_id')->label('Equipo principal')->relationship('equipment', 'name')->searchable()->preload(),
            TextInput::make('number')->label('Numero')->required(),
            Select::make('status')->label('Estado')->options([
                'draft' => 'Borrador',
                'issued' => 'Emitida',
                'overdue' => 'Vencida',
                'paid' => 'Cobrada',
            ])->default('draft')->required(),
            DatePicker::make('issued_at')->label('Emision'),
            DatePicker::make('due_at')->label('Vencimiento'),
            DatePicker::make('paid_at')->label('Cobro'),
            Repeater::make('lines')->label('Lineas')->relationship()->schema([
                Select::make('work_order_id')->label('Parte')->relationship('workOrder', 'id')->searchable()->preload(),
                Select::make('quote_id')->label('Presupuesto')->relationship('quote', 'number')->searchable()->preload(),
                TextInput::make('description')->label('Descripcion')->required(),
                TextInput::make('quantity')->label('Cantidad')->numeric()->default(1),
                TextInput::make('unit_price')->label('Precio')->numeric()->prefix('EUR')->default(0),
                TextInput::make('total')->label('Total')->numeric()->prefix('EUR')->default(0),
            ])->columns(2)->columnSpanFull(),
            TextInput::make('subtotal')->label('Subtotal')->numeric()->prefix('EUR')->default(0),
            TextInput::make('tax')->label('IVA')->numeric()->prefix('EUR')->default(0),
            TextInput::make('total')->label('Total')->numeric()->prefix('EUR')->default(0),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable(),
                TextColumn::make('equipment.code')->label('Equipo')->searchable()->toggleable(),
                TextColumn::make('total')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('due_at')->label('Vence')->date()->sortable(),
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
            'index' => ManageInvoices::route('/'),
        ];
    }
}
