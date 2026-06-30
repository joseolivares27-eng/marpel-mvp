<?php

namespace App\Services;

use App\Models\Notice;

class RaquelNoticeService extends ChannelNoticeService
{
    /**
     * @param array{
     *     nombre_cliente?: string|null,
     *     telefono: string,
     *     email?: string|null,
     *     direccion: string,
     *     descripcion: string,
     *     persona_contacto?: string|null,
     *     origen?: string|null,
     *     prioridad?: string|null,
     *     tipo_cliente?: string|null,
     *     equipo_tipo?: string|null,
     *     notas?: string|null
     * } $payload
     */
    public function createFromRaquelPayload(array $payload): Notice
    {
        return $this->createFromPayload($payload);
    }

    protected function defaultChannel(): string
    {
        return 'telefono';
    }

    protected function sourceLabel(): string
    {
        return 'Raquel';
    }
}
