<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use App\Services\WorkOrderService;
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
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->activeWorkOrders($query))
                ->badge(fn (): int => $this->activeWorkOrders(WorkOrder::query())->count()),
            'avisos' => Tab::make('Avisos')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->activeWorkOrders($query)->whereNotNull('notice_id'))
                ->badge(fn (): int => $this->activeWorkOrders(WorkOrder::query())->whereNotNull('notice_id')->count()),
            'revisiones' => Tab::make('Revisiones')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->activeWorkOrders($query)->whereNotNull('review_id'))
                ->badge(fn (): int => $this->activeWorkOrders(WorkOrder::query())->whereNotNull('review_id')->count()),
            'manuales' => Tab::make('Manuales')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->activeWorkOrders($query)->whereNull('notice_id')->whereNull('review_id'))
                ->badge(fn (): int => $this->activeWorkOrders(WorkOrder::query())->whereNull('notice_id')->whereNull('review_id')->count()),
            'cerrados' => Tab::make('Cerrados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'closed'))
                ->badge(fn (): int => WorkOrder::query()->where('status', 'closed')->count()),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'todos';
    }

    protected function getHeaderActions(): array
    {
        $page = $this;

        return [
            CreateAction::make()
                ->using(function (array $data, string $model) use ($page): Model {
                    try {
                        if ($page->shouldCreateNoticeWorkOrder($data)) {
                            return app(WorkOrderService::class)->createNoticeWorkOrderFromWorkOrderData($data);
                        }

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

    /**
     * @param array<string, mixed> $data
     */
    private function shouldCreateNoticeWorkOrder(array $data): bool
    {
        return blank($data['review_id'] ?? null)
            && ($this->activeTab === 'avisos' || filled($data['notice_id'] ?? null));
    }

    private function activeWorkOrders(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('status')
                ->orWhere('status', '!=', 'closed');
        });
    }
}
