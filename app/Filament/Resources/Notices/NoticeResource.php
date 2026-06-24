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
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            Select::make('customer_id')->label('Cliente')->relationship('customer', 'legal_name')->searchable()->preload()->required(),
            Select::make('installation_id')->label('Instalacion')->relationship('installation', 'name')->searchable()->preload()->required(),
            Select::make('equipment_id')->label('Equipo')->relationship('equipment', 'name')->searchable()->preload(),
            Select::make('contract_id')->label('Contrato')->relationship('contract', 'number')->searchable()->preload(),
            TextInput::make('reported_by')->label('Avisado por'),
            TextInput::make('contact_name')->label('Contacto'),
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
                'pending_quote' => 'Pendiente presupuesto',
                'resolved' => 'Resuelto',
                'cancelled' => 'Cancelado',
            ])->default('pending')->required(),
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
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
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
                    ->hidden(fn (Notice $record): bool => $record->workOrder()->exists() || in_array($record->status, ['resolved', 'cancelled'], true)),
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
}
