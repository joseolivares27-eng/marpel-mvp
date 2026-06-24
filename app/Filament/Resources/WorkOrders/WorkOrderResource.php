<?php

namespace App\Filament\Resources\WorkOrders;

use App\Filament\Resources\WorkOrders\Pages\ManageWorkOrders;
use App\Models\Material;
use App\Models\WorkOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WorkOrderResource extends Resource
{
    protected static ?string $model = WorkOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion';

    protected static ?string $modelLabel = 'parte';

    protected static ?string $pluralModelLabel = 'partes de trabajo';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload()->required(),
            Select::make('equipment_id')->label('Equipo')->relationship('equipment', 'name')->searchable()->preload(),
            Select::make('assigned_user_id')->label('Tecnico')->relationship('technician', 'name')->searchable()->preload(),
            Textarea::make('observations')
                ->label('Descripcion / trabajo solicitado')
                ->placeholder('Ej. puerta abierta, no abre, mandos no funcionan, hace ruido...')
                ->columnSpanFull(),
            Select::make('review_id')->label('Revision')->relationship('review', 'id')->searchable()->preload(),
            Select::make('quote_id')->label('Presupuesto')->relationship('quote', 'number')->searchable()->preload(),
            Select::make('status')->label('Estado')->options([
                'open' => 'Abierto',
                'in_progress' => 'En curso',
                'closed' => 'Cerrado',
                'cancelled' => 'Cancelado',
            ])->default('open')->required(),
            DateTimePicker::make('started_at')->label('Inicio'),
            DateTimePicker::make('finished_at')->label('Fin'),
            Select::make('result')->label('Resultado')->options([
                'solved' => 'Solucionado',
                'pending_material' => 'Pendiente material',
                'requires_quote' => 'Requiere presupuesto',
                'not_located' => 'No localizado',
            ]),
            Textarea::make('work_performed')->label('Trabajo realizado')->columnSpanFull(),
            Repeater::make('materials')->label('Materiales')->relationship()->schema([
                Select::make('material_id')
                    ->label('Material catalogo')
                    ->relationship('material', 'name')
                    ->placeholder('Material manual / no catalogado')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->helperText('Opcional. Dejalo vacio para escribir un material manual/libre.')
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if (! $state) {
                            return;
                        }

                        $material = Material::find($state);

                        if (! $material) {
                            return;
                        }

                        $set('description', $material->name);
                        $set('unit_cost', $material->cost_price);
                        $set('unit_price', $material->sale_price);
                    }),
                TextInput::make('description')
                    ->label('Descripcion manual/libre')
                    ->placeholder('Opcional. Ej. fotocelula, mando, cableado...'),
                TextInput::make('quantity')->label('Cantidad')->numeric()->default(1),
                TextInput::make('unit_cost')->label('Coste')->numeric()->prefix('EUR')->default(0),
                TextInput::make('unit_price')->label('Venta')->numeric()->prefix('EUR')->default(0),
            ])
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): ?array => self::normalizeMaterialLine($data))
                ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): ?array => self::normalizeMaterialLine($data))
                ->columns(2)
                ->columnSpanFull(),
            FileUpload::make('customer_signature_path')->label('Firma cliente')->disk('public')->directory('signatures'),
            TextInput::make('customer_name')->label('Firma nombre'),
            DateTimePicker::make('signed_at')->label('Firmado'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Parte')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable(),
                TextColumn::make('equipment.name')->label('Equipo')->searchable(),
                TextColumn::make('technician.name')->label('Tecnico')->searchable(),
                TextColumn::make('finished_at')->label('Cierre')->dateTime('d/m/Y H:i')->sortable(),
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private static function normalizeMaterialLine(array $data): ?array
    {
        $materialId = $data['material_id'] ?? null;
        $description = trim((string) ($data['description'] ?? ''));
        $quantity = (float) ($data['quantity'] ?? 0);
        $unitCost = (float) ($data['unit_cost'] ?? 0);
        $unitPrice = (float) ($data['unit_price'] ?? 0);

        if (! $materialId && $description === '' && $unitCost === 0.0 && $unitPrice === 0.0) {
            return null;
        }

        if ($materialId && $material = Material::find($materialId)) {
            $data['description'] = $description !== '' ? $description : $material->name;
            $data['unit_cost'] = $unitCost > 0 ? $unitCost : $material->cost_price;
            $data['unit_price'] = $unitPrice > 0 ? $unitPrice : $material->sale_price;
        } else {
            $data['description'] = $description !== '' ? $description : null;
        }

        $data['quantity'] = $quantity > 0 ? $quantity : 1;

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWorkOrders::route('/'),
        ];
    }
}
