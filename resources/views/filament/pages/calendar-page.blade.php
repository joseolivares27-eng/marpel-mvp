@php
    use App\Models\Notice;
    use App\Models\Review;

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
        ]))
        ->merge(Review::with(['installation', 'technician'])->whereDate('scheduled_at', '>=', now()->toDateString())->limit(30)->get()->map(fn ($review) => [
            'time' => $review->scheduled_at,
            'type' => 'Revision',
            'installation' => $review->installation->name,
            'technician' => $review->technician?->name ?? 'Sin asignar',
            'status' => $review->status,
        ]))
        ->sortBy('time');
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Agenda operativa</x-slot>
        <div class="space-y-3">
            @foreach ($items as $item)
                <div class="grid gap-2 rounded-lg border border-gray-200 p-4 text-sm dark:border-gray-700 md:grid-cols-5">
                    <div class="font-semibold">{{ $item['time']?->format('d/m H:i') }}</div>
                    <div>{{ $item['type'] }}</div>
                    <div class="md:col-span-2">{{ $item['installation'] }}</div>
                    <div>{{ $item['technician'] }} · {{ $item['status'] }}</div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
