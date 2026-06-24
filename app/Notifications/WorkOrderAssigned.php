<?php

namespace App\Notifications;

use App\Models\WorkOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class WorkOrderAssigned extends Notification
{
    use Queueable;

    public function __construct(private readonly WorkOrder $workOrder)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $this->workOrder->loadMissing(['installation', 'equipment', 'notice', 'review']);

        return [
            'work_order_id' => $this->workOrder->id,
            'title' => 'Nuevo parte asignado',
            'body' => trim(($this->workOrder->installation?->name ?? 'Instalacion').' - '.($this->workOrder->equipment?->name ?? 'Sin equipo concreto')),
            'origin_type' => $this->workOrder->notice_id ? 'notice' : ($this->workOrder->review_id ? 'review' : 'manual'),
            'origin_id' => $this->workOrder->notice_id ?: $this->workOrder->review_id,
            'installation' => $this->workOrder->installation?->name,
            'equipment' => $this->workOrder->equipment?->name,
        ];
    }
}
