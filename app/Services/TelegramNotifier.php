<?php

namespace App\Services;

use App\Filament\Resources\Notices\NoticeResource;
use App\Models\Notice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function notifyManualNotice(Notice $notice): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.avisos_chat_id');

        if (! $token || ! $chatId) {
            return;
        }

        $notice->loadMissing(['customer', 'installation']);

        $text = "📝 Aviso creado desde oficina\n\n"
            ."👤 Cliente: {$this->safe($notice->customer?->legal_name)}\n"
            ."🏢 Instalacion: {$this->safe($notice->installation?->name)}\n"
            ."📍 Direccion: {$this->safe($notice->installation?->address)}\n"
            ."📞 Telefono: {$this->safe($notice->contact_phone ?: $notice->reported_by)}\n"
            ."🔧 Averia: {$this->safe($notice->description)}\n"
            ."⏰ Fecha/Hora: ".now()->format('d/m/Y H:i')
            ."\n\n🔗 ".NoticeResource::getUrl('view', ['record' => $notice]);

        try {
            Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el aviso a Telegram: '.$e->getMessage());
        }
    }

    protected function safe(?string $value): string
    {
        return $value !== null && $value !== '' ? $value : 'Sin especificar';
    }
}
