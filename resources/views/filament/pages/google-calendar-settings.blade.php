<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Conexion con Google Calendar</x-slot>

        @if (session('status') === 'google-calendar-connected')
            <div class="mb-4 rounded-lg bg-success-500/10 p-3 text-sm text-success-700 dark:text-success-400">
                Conectado correctamente con Google Calendar.
            </div>
        @endif

        @if ($this->getIsConnected())
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 text-success-600 dark:text-success-400">
                    <x-filament::icon icon="heroicon-o-check-circle" class="h-5 w-5" />
                    <span>Conectado con marpelserviciosintegrales@gmail.com</span>
                </div>
                <form method="POST" action="{{ route('google-calendar.disconnect') }}">
                    @csrf
                    <x-filament::button color="danger" type="submit">
                        Desconectar
                    </x-filament::button>
                </form>
            </div>
        @else
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                Conecta el calendario de Marpel para que los avisos y revisiones con fecha programada se sincronicen automaticamente con Google Calendar.
            </p>
            <x-filament::button tag="a" :href="route('google-calendar.connect')">
                Conectar Google Calendar
            </x-filament::button>
        @endif
    </x-filament::section>
</x-filament-panels::page>
