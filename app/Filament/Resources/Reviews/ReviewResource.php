<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
use App\Models\User;
use App\Services\WorkOrderService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion';

    protected static ?string $modelLabel = 'revision';

    protected static ?string $pluralModelLabel = 'revisiones';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload()->required(),
            Select::make('equipment_id')->label('Equipo')->relationship('equipment', 'name')->searchable()->preload()->required(),
            Select::make('contract_id')->label('Contrato')->relationship('contract', 'number')->searchable()->preload(),
            Select::make('assigned_user_id')->label('Tecnico')->relationship('technician', 'name')->searchable()->preload(),
            DateTimePicker::make('scheduled_at')->label('Programada')->required(),
            Select::make('type')->label('Tipo')->options(['preventive' => 'Preventiva', 'corrective' => 'Correctiva'])->default('preventive')->required(),
            Select::make('status')->label('Estado')->options([
                'scheduled' => 'Programada',
                'assigned' => 'Asignada',
                'in_progress' => 'En curso',
                'closed' => 'Cerrada',
                'cancelled' => 'Cancelada',
            ])->default('scheduled')->required(),
            Select::make('result')->label('Resultado')->options([
                'ok' => 'Correcto',
                'incident' => 'Incidencia',
                'requires_quote' => 'Requiere presupuesto',
            ]),
            DatePicker::make('next_review_at')->label('Proxima revision'),
            Textarea::make('notes')->label('Observaciones')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scheduled_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable(),
                TextColumn::make('equipment.name')->label('Equipo')->searchable(),
                TextColumn::make('technician.name')->label('Tecnico')->searchable(),
                TextColumn::make('workOrder.id')->label('Parte')->sortable(),
                TextColumn::make('next_review_at')->label('Proxima')->date()->sortable(),
            ])
            ->recordActions([
                Action::make('create_work_order')
                    ->label('Crear parte')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->form([
                        Select::make('technician_id')
                            ->label('Tecnico')
                            ->options(fn (): array => User::query()
                                ->where('role', 'technician')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Review $record, array $data): void {
                        $technician = User::query()->findOrFail($data['technician_id']);

                        app(WorkOrderService::class)->createFromReview($record, $technician);

                        FilamentNotification::make()
                            ->success()
                            ->title('Parte creado y notificado')
                            ->body('El tecnico ya tiene la revision en su PWA.')
                            ->send();
                    })
                    ->hidden(fn (Review $record): bool => $record->workOrder()->exists() || in_array($record->status, ['closed', 'cancelled'], true)),
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
            'index' => ManageReviews::route('/'),
        ];
    }
}
