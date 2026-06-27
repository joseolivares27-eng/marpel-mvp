<?php

namespace App\Filament\Resources\WorkOrders;

use App\Filament\Resources\WorkOrders\Pages\ManageWorkOrders;
use App\Models\Material;
use App\Models\Notice;
use App\Models\Review;
use App\Models\WorkOrder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            Select::make('customer_id')
                ->label('Cliente')
                ->relationship('customer', 'legal_name')
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(fn (Set $set) => $set('installation_id', null) && $set('equipment_id', null))
                ->required(),
            Select::make('installation_id')
                ->label('Instalacion')
                ->relationship('installation', 'name', fn (Builder $query, Get $get) => $query->when($get('customer_id'), fn (Builder $query, $customerId) => $query->where('customer_id', $customerId)))
                ->searchable()
                ->preload()
                ->live()
                ->disabled(fn (Get $get): bool => blank($get('customer_id')))
                ->helperText(fn (Get $get): ?string => blank($get('customer_id')) ? 'Elige primero un cliente.' : null)
                ->afterStateUpdated(fn (Set $set) => $set('equipment_id', null))
                ->required(),
            Select::make('equipment_id')
                ->label('Equipo')
                ->relationship('equipment', 'name', fn (Builder $query, Get $get) => $query->when($get('installation_id'), fn (Builder $query, $installationId) => $query->where('installation_id', $installationId)))
                ->searchable()
                ->preload()
                ->disabled(fn (Get $get): bool => blank($get('installation_id')))
                ->helperText(fn (Get $get): ?string => blank($get('installation_id')) ? 'Elige primero una instalacion.' : null),
            Select::make('assigned_user_id')->label('Tecnico')->relationship('technician', 'name')->searchable()->preload(),
            Textarea::make('observations')
                ->label('Descripcion / trabajo solicitado')
                ->placeholder('Ej. puerta abierta, no abre, mandos no funcionan, hace ruido...')
                ->columnSpanFull(),
            Select::make('quote_id')->label('Presupuesto')->relationship('quote', 'number')->searchable()->preload(),
            Select::make('status')->label('Estado')->options([
                'open' => 'Abierto',
                'closed' => 'Cerrado',
            ])
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record))
                ->default('open')
                ->afterStateHydrated(function (Select $component, ?string $state): void {
                    if (in_array($state, ['new', 'in_progress'], true)) {
                        $component->state('open');
                    }

                    if (in_array($state, ['cancelled', 'resolved', 'completed'], true)) {
                        $component->state('closed');
                    }
                })
                ->live()
                ->required(),
            DateTimePicker::make('started_at')
                ->label('Fecha inicio')
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record)),
            DateTimePicker::make('finished_at')
                ->label('Fecha fin')
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record))
                ->helperText('Si se deja vacia al cerrar, se asigna automaticamente.'),
            Select::make('result')->label('Resultado')->options([
                'pending' => 'Pendiente',
                'solved' => 'Solucionado',
                'unresolved' => 'No solucionado',
                'cancelled' => 'Anulado',
            ])
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record))
                ->default('pending')
                ->afterStateHydrated(function (Select $component, ?string $state): void {
                    $component->state(self::normalizeResultState($state));
                })
                ->live()
                ->required(),
            Textarea::make('work_performed')
                ->label('Trabajo realizado')
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record))
                ->columnSpanFull(),
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
            TextInput::make('customer_name')
                ->label('Nombre firmante')
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record))
                ->helperText('Obligatorio solo si se cierra como Solucionado.'),
            FileUpload::make('customer_signature_path')
                ->label('Firma cliente')
                ->disabled(fn (?WorkOrder $record): bool => self::closedCriticalFieldsAreLocked($record))
                ->disk('public')
                ->directory('signatures')
                ->image()
                ->downloadable()
                ->openable()
                ->helperText('Obligatoria solo si se cierra como Solucionado. La fecha de firma se guarda automaticamente.'),
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
                TextColumn::make('pdf_path')
                    ->label('PDF')
                    ->formatStateUsing(fn (?string $state, WorkOrder $record): string => ($record->status === 'closed' && $state) ? 'Descargar' : '-')
                    ->url(fn (WorkOrder $record): ?string => ($record->status === 'closed' && $record->pdf_path) ? route('work-orders.pdf.download', $record) : null),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable(),
                TextColumn::make('equipment.name')->label('Equipo')->searchable(),
                TextColumn::make('technician.name')->label('Tecnico')->searchable(),
                TextColumn::make('started_at')->label('Fecha inicio')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('finished_at')->label('Cierre')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->recordActions([
                Action::make('download_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (WorkOrder $record): string => route('work-orders.pdf.download', $record))
                    ->visible(fn (WorkOrder $record): bool => $record->status === 'closed' && filled($record->pdf_path)),
                EditAction::make()
                    ->using(function (WorkOrder $record, array $data): WorkOrder {
                        try {
                            $record->update($data);

                            return $record;
                        } catch (ValidationException $exception) {
                            self::notifyValidationError($exception);

                            throw $exception;
                        }
                    }),
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
        $set('started_at', $notice->scheduled_at);
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
            'in_progress' => 'Abierto',
            'closed' => 'Cerrado',
            'cancelled' => 'Cerrado',
            'resolved' => 'Cerrado',
            'completed' => 'Cerrado',
        ][$state] ?? ($state ?: 'Abierto');
    }

    private static function resultLabel(?string $state): string
    {
        return [
            'pending' => 'Pendiente',
            'solved' => 'Solucionado',
            'unresolved' => 'No solucionado',
            'not_solved' => 'No solucionado',
            'cancelled' => 'Anulado',
            'anulado' => 'Anulado',
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
            'not_solved', 'unresolved', 'no_solucionado', 'not_located', 'incident' => 'unresolved',
            'cancelled', 'anulado' => 'cancelled',
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

    private static function notifyValidationError(ValidationException $exception): void
    {
        $message = collect($exception->errors())
            ->flatten()
            ->filter()
            ->implode("\n");

        Notification::make()
            ->danger()
            ->title('No se pudo guardar el parte')
            ->body($message ?: 'Revisa los campos obligatorios del parte.')
            ->persistent()
            ->send();
    }

    private static function closedCriticalFieldsAreLocked(?WorkOrder $record): bool
    {
        return $record?->status === 'closed'
            && auth()->user()?->role !== 'admin';
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageWorkOrders::route('/'),
        ];
    }
}
