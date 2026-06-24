<?php

namespace App\Filament\Resources\WorkOrders;

use App\Filament\Resources\WorkOrders\Pages\ManageWorkOrders;
use App\Models\Material;
use App\Models\Notice;
use App\Models\Review;
use App\Models\WorkOrder;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

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
            Tabs::make('Origen del parte')
                ->tabs([
                    Tab::make('Aviso')
                        ->schema([
                            Select::make('notice_id')
                                ->label('Aviso origen')
                                ->relationship('notice', 'description')
                                ->getOptionLabelFromRecordUsing(fn (Notice $record): string => self::noticeLabel($record))
                                ->searchable(['description', 'reported_by', 'contact_name', 'contact_phone'])
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (?int $state, Set $set): void {
                                    self::fillFromNotice($state, $set);
                                }),
                        ]),
                    Tab::make('Revision')
                        ->schema([
                            Select::make('review_id')
                                ->label('Revision origen')
                                ->relationship('review', 'id')
                                ->getOptionLabelFromRecordUsing(fn (Review $record): string => self::reviewLabel($record))
                                ->searchable(['type', 'status', 'result'])
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (?int $state, Set $set): void {
                                    self::fillFromReview($state, $set);
                                }),
                        ]),
                ])
                ->persistTab()
                ->id('work-order-origin-tabs')
                ->columnSpanFull(),
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload()->required(),
            Select::make('equipment_id')->label('Equipo')->relationship('equipment', 'name')->searchable()->preload(),
            Select::make('assigned_user_id')->label('Tecnico')->relationship('technician', 'name')->searchable()->preload(),
            Textarea::make('observations')
                ->label('Descripcion / trabajo solicitado')
                ->placeholder('Ej. puerta abierta, no abre, mandos no funcionan, hace ruido...')
                ->columnSpanFull(),
            Select::make('quote_id')->label('Presupuesto')->relationship('quote', 'number')->searchable()->preload(),
            Select::make('status')->label('Estado')->options([
                'open' => 'Abierto',
                'in_progress' => 'En curso',
                'closed' => 'Cerrado',
                'cancelled' => 'Cancelado',
            ])
                ->default('open')
                ->afterStateHydrated(function (Select $component, ?string $state): void {
                    if ($state === 'new') {
                        $component->state('open');
                    }
                })
                ->required(),
            DateTimePicker::make('started_at')->label('Fecha inicio'),
            DateTimePicker::make('finished_at')->label('Fecha fin'),
            Select::make('result')->label('Resultado')->options([
                'pending' => 'Pendiente',
                'solved' => 'Solucionado',
                'not_solved' => 'No solucionado',
            ])
                ->default('pending')
                ->afterStateHydrated(function (Select $component, ?string $state): void {
                    $component->state(self::normalizeResultState($state));
                })
                ->required(),
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
            TextInput::make('customer_name')->label('Nombre firmante'),
            DateTimePicker::make('signed_at')->label('Firmado')->disabled()->dehydrated(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Parte')->sortable(),
                TextColumn::make('origin_label')->label('Origen')->badge(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->sortable(),
                TextColumn::make('result')->label('Resultado')->badge()->formatStateUsing(fn (?string $state): string => self::resultLabel($state))->sortable(),
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

    private static function fillFromNotice(?int $noticeId, Set $set): void
    {
        if (! $noticeId) {
            return;
        }

        $notice = Notice::query()->find($noticeId);

        if (! $notice) {
            return;
        }

        $set('review_id', null);
        $set('customer_id', $notice->customer_id);
        $set('installation_id', $notice->installation_id);
        $set('equipment_id', $notice->equipment_id);
        $set('assigned_user_id', $notice->assigned_user_id);
        $set('observations', $notice->description);
        $set('status', 'open');
        $set('result', 'pending');
    }

    private static function fillFromReview(?int $reviewId, Set $set): void
    {
        if (! $reviewId) {
            return;
        }

        $review = Review::query()->find($reviewId);

        if (! $review) {
            return;
        }

        $set('notice_id', null);
        $set('customer_id', $review->customer_id);
        $set('installation_id', $review->installation_id);
        $set('equipment_id', $review->equipment_id);
        $set('assigned_user_id', $review->assigned_user_id);
        $set('observations', $review->notes ?: 'Revision programada');
        $set('status', 'open');
        $set('result', 'pending');
    }

    private static function statusLabel(?string $state): string
    {
        return [
            'open' => 'Abierto',
            'new' => 'Abierto',
            'in_progress' => 'En curso',
            'closed' => 'Cerrado',
            'cancelled' => 'Cancelado',
        ][$state] ?? ($state ?: 'Abierto');
    }

    private static function resultLabel(?string $state): string
    {
        return [
            'pending' => 'Pendiente',
            'solved' => 'Solucionado',
            'not_solved' => 'No solucionado',
            'solucionado' => 'Solucionado',
            'pendiente' => 'Pendiente',
            'no_solucionado' => 'No solucionado',
            'ok' => 'Solucionado',
            'pending_material' => 'Pendiente',
            'requires_quote' => 'Pendiente',
            'not_located' => 'No solucionado',
            'incident' => 'No solucionado',
        ][$state] ?? 'Pendiente';
    }

    private static function normalizeResultState(?string $state): string
    {
        return match ($state) {
            'solved', 'solucionado', 'ok' => 'solved',
            'not_solved', 'no_solucionado', 'not_located', 'incident' => 'not_solved',
            default => 'pending',
        };
    }

    private static function noticeLabel(Notice $notice): string
    {
        return Str::limit(($notice->installation?->name ?: 'Instalacion').' - '.$notice->description, 90);
    }

    private static function reviewLabel(Review $review): string
    {
        $date = $review->scheduled_at?->format('d/m/Y H:i') ?: 'Sin fecha';
        $installation = $review->installation?->name ?: 'Instalacion';
        $equipment = $review->equipment?->name ?: 'Equipo';

        return Str::limit("{$date} - {$installation} - {$equipment}", 90);
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
