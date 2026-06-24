<?php

namespace App\Filament\Resources\Quotes;

use App\Filament\Resources\Quotes\Pages\ManageQuotes;
use App\Models\Quote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $modelLabel = 'presupuesto';

    protected static ?string $pluralModelLabel = 'presupuestos';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload()->required(),
            Select::make('equipment_id')->label('Equipo')->relationship('equipment', 'name')->searchable()->preload(),
            Select::make('notice_id')->label('Aviso origen')->relationship('notice', 'id')->searchable()->preload(),
            Select::make('review_id')->label('Revision origen')->relationship('review', 'id')->searchable()->preload(),
            TextInput::make('number')->label('Numero')->required(),
            Select::make('status')->label('Estado')->options([
                'draft' => 'Borrador',
                'sent' => 'Enviado',
                'accepted' => 'Aceptado',
                'rejected' => 'Rechazado',
            ])->default('draft')->required(),
            DatePicker::make('valid_until')->label('Valido hasta'),
            DateTimePicker::make('sent_at')->label('Enviado'),
            DateTimePicker::make('accepted_at')->label('Aceptado'),
            Repeater::make('lines')->label('Lineas')->relationship()->schema([
                Select::make('material_id')->label('Material')->relationship('material', 'name')->searchable()->preload(),
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
                TextColumn::make('equipment.code')->label('Equipo')->searchable(),
                TextColumn::make('total')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('valid_until')->label('Validez')->date()->sortable(),
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
            'index' => ManageQuotes::route('/'),
        ];
    }
}
