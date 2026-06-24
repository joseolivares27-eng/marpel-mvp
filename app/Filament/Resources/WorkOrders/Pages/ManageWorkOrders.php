<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ManageWorkOrders extends ManageRecords
{
    protected static string $resource = WorkOrderResource::class;

    public function getTabs(): array
    {
        return [
            'todos' => Tab::make('Todos')
                ->badge(fn (): int => WorkOrder::query()->count()),
            'avisos' => Tab::make('Avisos')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('notice_id'))
                ->badge(fn (): int => WorkOrder::query()->whereNotNull('notice_id')->count()),
            'revisiones' => Tab::make('Revisiones')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('review_id'))
                ->badge(fn (): int => WorkOrder::query()->whereNotNull('review_id')->count()),
            'manuales' => Tab::make('Manuales')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('notice_id')->whereNull('review_id'))
                ->badge(fn (): int => WorkOrder::query()->whereNull('notice_id')->whereNull('review_id')->count()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'todos';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    try {
                        return $model::create($data);
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('No se pudo crear el parte')
                            ->body($exception->getMessage())
                            ->persistent()
                            ->send();

                        throw $exception;
                    }
                })
                ->successNotificationTitle('Parte creado'),
        ];
    }
}
