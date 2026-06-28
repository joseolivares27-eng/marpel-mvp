@php
    use App\Models\Notice;
    use App\Models\Review;
    use App\Services\GoogleCalendarService;

    $noticeStatusLabels = [
        'pending' => 'Pendiente',
        'assigned' => 'Asignado',
        'in_progress' => 'En curso',
        'completed' => 'Realizado',
        'resolved' => 'Realizado',
        'pending_quote' => 'Pendiente',
        'cancelled' => 'Cancelado',
    ];

    $items = collect()
        ->merge(Notice::with(['installation', 'technician'])->whereNotNull('scheduled_at')->whereDate('scheduled_at', '>=', now()->toDateString())->limit(30)->get()->map(fn ($notice) => [
            'time' => $notice->scheduled_at,
            'type' => 'Aviso',
            'installation' => $notice->installation->name,
            'technician' => $notice->technician?->name ?? 'Sin asignar',
            'status' => $noticeStatusLabels[$notice->status] ?? $notice->status,
            'synced' => filled($notice->google_event_id),
        ]))
        ->merge(Review::with(['installation', 'technician'])->whereDate('scheduled_at', '>=', now()->toDateString())->limit(30)->get()->map(fn ($review) => [
            'time' => $review->scheduled_at,
            'type' => 'Revision',
            'installation' => $review->installation->name,
            'technician' => $review->technician?->name ?? 'Sin asignar',
            'status' => $review->status,
            'synced' => filled($review->google_event_id),
        ]))
        ->sortBy('time');

    $googleCalendarService = app(GoogleCalendarService::class);
    $isConnected = $googleCalendarService->isConnected();
    $externalEvents = $isConnected ? $googleCalendarService->pullUpcomingEvents() : [];
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Agenda operativa</x-slot>
        <div class="space-y-3">
            @foreach ($items as $item)
                <div class="grid gap-2 rounded-lg border border-gray-200 p-4 text-sm dark:border-white/10 md:grid-cols-5">
                    <div class="font-semibold">{{ $item['time']?->format('d/m H:i') }}</div>
                    <div>{{ $item['type'] }}</div>
                    <div class="md:col-span-2">{{ $item['installation'] }}</div>
                    <div class="flex items-center justify-between">
                        <span>{{ $item['technician'] }} · {{ $item['status'] }}</span>
                        @if ($item['synced'])
                            <x-filament::icon icon="heroicon-o-check-circle" class="h-4 w-4 text-success-500" title="Sincronizado con Google Calendar" />
                        @else
                            <x-filament::icon icon="heroicon-o-cloud" class="h-4 w-4 text-gray-300 dark:text-gray-600" title="No sincronizado" />
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Eventos de Google Calendar</x-slot>
        @if (! $isConnected)
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Google Calendar no esta conectado.
                <a href="{{ \App\Filament\Pages\GoogleCalendarSettings::getUrl() }}" class="text-primary-600 underline dark:text-primary-400">Conectar ahora</a>.
            </p>
        @elseif (empty($externalEvents))
            <p class="text-sm text-gray-500 dark:text-gray-400">No hay eventos próximos en el calendario de Google.</p>
        @else
            <div class="space-y-2">
                @foreach ($externalEvents as $event)
                    <a href="{{ $event['htmlLink'] }}" target="_blank" class="flex items-center justify-between rounded-lg border border-gray-200 p-3 text-sm dark:border-white/10">
                        <span>{{ $event['start']?->format('d/m H:i') ?? 'Sin fecha' }} · {{ $event['title'] }}</span>
                        <x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4 text-gray-400" />
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
