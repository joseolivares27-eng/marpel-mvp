<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManageWorkOrders extends ManageRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->beforeFormValidated(function (): void {
                    Log::info('MARPEL_WORK_ORDER_CREATE_FORM_VALIDATING');
                })
                ->afterFormValidated(function (): void {
                    Log::info('MARPEL_WORK_ORDER_CREATE_FORM_VALIDATED');
                })
                ->before(function (): void {
                    Log::info('MARPEL_WORK_ORDER_CREATE_BEFORE_SAVE');
                })
                ->using(function (array $data, string $model): Model {
                    Log::info('MARPEL_WORK_ORDER_CREATE_SAVING', [
                        'customer_id' => $data['customer_id'] ?? null,
                        'installation_id' => $data['installation_id'] ?? null,
                        'equipment_id' => $data['equipment_id'] ?? null,
                        'assigned_user_id' => $data['assigned_user_id'] ?? null,
                        'status' => $data['status'] ?? null,
                        'has_materials' => ! empty($data['materials']),
                    ]);

                    try {
                        $record = $model::create($data);

                        Log::info('MARPEL_WORK_ORDER_CREATE_SAVED', [
                            'work_order_id' => $record->getKey(),
                        ]);

                        return $record;
                    } catch (Throwable $exception) {
                        Log::error('MARPEL_WORK_ORDER_CREATE_FAILED', [
                            'exception' => $exception::class,
                            'message' => $exception->getMessage(),
                            'customer_id' => $data['customer_id'] ?? null,
                            'installation_id' => $data['installation_id'] ?? null,
                            'equipment_id' => $data['equipment_id'] ?? null,
                            'assigned_user_id' => $data['assigned_user_id'] ?? null,
                        ]);

                        Notification::make()
                            ->danger()
                            ->title('No se pudo crear el parte')
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();

                        throw $exception;
                    }
                })
                ->after(function (): void {
                    Log::info('MARPEL_WORK_ORDER_CREATE_AFTER_SAVE');
                })
                ->successNotificationTitle('Parte creado'),
        ];
    }
}
