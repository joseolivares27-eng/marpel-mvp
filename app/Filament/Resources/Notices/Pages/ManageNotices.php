<?php

namespace App\Filament\Resources\Notices\Pages;

use App\Filament\Resources\Notices\NoticeResource;
use App\Models\Notice;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ManageNotices extends ManageRecords
{
    protected static string $resource = NoticeResource::class;

    public function getTabs(): array
    {
        return [
            'activos' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query): Builder => $this->activeNotices($query))
                ->badge(fn (): int => $this->activeNotices(Notice::query())->count()),
            'cerrados' => Tab::make('Cerrados')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('status', ['completed', 'resolved', 'cancelled']))
                ->badge(fn (): int => Notice::query()->whereIn('status', ['completed', 'resolved', 'cancelled'])->count()),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'activos';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data, string $model): Model {
                    $data['status'] = 'pending';

                    return $model::create($data);
                }),
        ];
    }

    private function activeNotices(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['completed', 'resolved', 'cancelled']);
    }
}
