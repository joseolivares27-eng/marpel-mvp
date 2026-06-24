<?php

namespace App\Filament\Resources\Materials;

use App\Filament\Resources\Materials\Pages\ManageMaterials;
use App\Models\Material;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Almacen';

    protected static ?string $modelLabel = 'material';

    protected static ?string $pluralModelLabel = 'materiales';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')->label('SKU')->maxLength(100),
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('unit')->label('Unidad')->default('ud')->required(),
            TextInput::make('cost_price')->label('Coste')->numeric()->prefix('EUR')->default(0),
            TextInput::make('sale_price')->label('Venta')->numeric()->prefix('EUR')->default(0),
            TextInput::make('stock_quantity')->label('Stock')->numeric()->default(0),
            TextInput::make('minimum_stock')->label('Stock minimo')->numeric()->default(0),
            Toggle::make('is_active')->label('Activo')->default(true),
            Textarea::make('notes')->label('Notas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sku')->label('SKU')->searchable(),
                TextColumn::make('name')->label('Material')->searchable()->sortable(),
                TextColumn::make('stock_quantity')->label('Stock')->sortable(),
                TextColumn::make('minimum_stock')->label('Minimo'),
                TextColumn::make('sale_price')->label('Precio')->money('EUR')->sortable(),
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
            'index' => ManageMaterials::route('/'),
        ];
    }
}
