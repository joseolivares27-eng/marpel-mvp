<?php

namespace App\Filament\Resources\Notices;

use App\Filament\Resources\Notices\Pages\ManageNotices;
use App\Models\Notice;
use App\Models\User;
use App\Services\WorkOrderService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NoticeResource extends Resource
{
    protected static ?string $model = Notice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell-alert';

    protected static string|\UnitEnum|null $navigationGroup = 'Operacion';

    protected static ?string $modelLabel = 'aviso';

    protected static ?string $pluralModelLabel = 'avisos';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
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
            TextInput::make('reported_by')
                ->label('Avisado por')
                ->helperText('Persona que comunica la incidencia. Ej. vecino, presidente, administrador.'),
            TextInput::make('contact_name')
                ->label('Contacto en instalacion')
                ->helperText('Persona a la que puede llamar el tecnico si es diferente.'),
            TextInput::make('contact_phone')->label('Telefono contacto')->tel(),
            Select::make('channel')->label('Origen')->options([
                'phone' => 'Telefono',
                'email' => 'Email',
                'whatsapp' => 'WhatsApp',
                'technician' => 'Tecnico',
            ])->default('phone')->required(),
            Select::make('priority')->label('Prioridad')->options([
                'low' => 'Baja',
                'normal' => 'Normal',
                'urgent' => 'Urgente',
            ])->default('normal')->required(),
            Select::make('status')->label('Estado')->options([
                'pending' => 'Pendiente',
                'assigned' => 'Asignado',
                'in_progress' => 'En curso',
                'completed' => 'Realizado',
                'cancelled' => 'Cancelado',
            ])
                ->default('pending')
                ->afterStateHydrated(function (Select $component, ?string $state): void {
                    if ($state === 'resolved') {
                        $component->state('completed');
                    }

                    if ($state === 'pending_quote') {
                        $component->state('pending');
                    }
                })
                ->required(),
            Select::make('assigned_user_id')->label('Tecnico')->relationship('technician', 'name')->searchable()->preload(),
            DateTimePicker::make('scheduled_at')->label('Planificado'),
            Toggle::make('requires_quote')->label('Requiere presupuesto'),
            Textarea::make('description')->label('Descripcion')->required()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priority')->label('Prioridad')->badge()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->formatStateUsing(fn (?string $state): string => self::statusLabel($state))->sortable(),
                TextColumn::make('customer.legal_name')->label('Cliente')->searchable(),
                TextColumn::make('installation.name')->label('Instalacion')->searchable(),
                TextColumn::make('equipment.name')->label('Equipo')->searchable(),
                TextColumn::make('technician.name')->label('Tecnico')->searchable(),
                TextColumn::make('workOrder.id')->label('Parte')->sortable(),
                TextColumn::make('scheduled_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
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
                    ->action(function (Notice $record, array $data): void {
                        $technician = User::query()->findOrFail($data['technician_id']);

                        app(WorkOrderService::class)->createFromNotice($record, $technician);

                        FilamentNotification::make()
                            ->success()
                            ->title('Parte creado y notificado')
                            ->body('El tecnico ya tiene el parte en su PWA.')
                            ->send();
                    })
                    ->hidden(fn (Notice $record): bool => $record->workOrder()->exists() || in_array($record->status, ['completed', 'resolved', 'cancelled'], true)),
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
            'index' => ManageNotices::route('/'),
        ];
    }

    private static function statusLabel(?string $state): string
    {
        return [
            'pending' => 'Pendiente',
            'assigned' => 'Asignado',
            'in_progress' => 'En curso',
            'completed' => 'Realizado',
            'resolved' => 'Realizado',
            'pending_quote' => 'Pendiente',
            'cancelled' => 'Cancelado',
        ][$state] ?? ($state ?: 'Pendiente');
    }
}
