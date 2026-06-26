@php
    $noticeStatusLabels = [
        'pending' => 'Pendiente',
        'assigned' => 'Asignado',
        'in_progress' => 'En curso',
        'completed' => 'Realizado',
        'resolved' => 'Realizado',
        'pending_quote' => 'Pendiente',
        'cancelled' => 'Cancelado',
    ];

    $workOrderStatusLabels = [
        'new' => 'Abierto',
        'open' => 'Abierto',
        'in_progress' => 'En curso',
        'closed' => 'Cerrado',
        'cancelled' => 'Cancelado',
    ];

    $workOrderResultLabels = [
        'pending' => 'Pendiente',
        'solved' => 'Solucionado',
        'unresolved' => 'No solucionado',
        'not_solved' => 'No solucionado',
        'solucionado' => 'Solucionado',
        'pendiente' => 'Pendiente',
        'no_solucionado' => 'No solucionado',
        'ok' => 'Solucionado',
        'pending_material' => 'Pendiente',
        'requires_quote' => 'Pendiente',
        'not_located' => 'No solucionado',
        'incident' => 'No solucionado',
    ];
@endphp

<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">{{ $record->code }} · {{ $record->name }}</x-slot>
        <div class="grid gap-3 text-sm md:grid-cols-2">
            <div><strong>Cliente:</strong> {{ $record->installation->customer->legal_name }}</div>
            <div><strong>Instalacion:</strong> {{ $record->installation->name }}</div>
            <div><strong>Tipo:</strong> {{ $record->type?->name ?? 'Sin tipo' }}</div>
            <div><strong>Categoria:</strong> {{ $record->category ?? $record->type?->category ?? 'Sin categoria' }}</div>
            <div><strong>Marca/modelo:</strong> {{ $record->brand ?? '-' }} {{ $record->model ?? '' }}</div>
            <div><strong>Proxima revision:</strong> {{ $record->next_review_at?->format('d/m/Y') ?? 'Sin fecha' }}</div>
        </div>
    </x-filament::section>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Avisos</x-slot>
            <div class="space-y-3 text-sm">
                @forelse ($record->notices->sortByDesc('created_at') as $notice)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <strong>{{ $notice->created_at->format('d/m/Y') }}</strong> · {{ $notice->priority }} · {{ $noticeStatusLabels[$notice->status] ?? $notice->status }}
                        <p>{{ $notice->description }}</p>
                    </div>
                @empty
                    <p>Sin avisos.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Revisiones</x-slot>
            <div class="space-y-3 text-sm">
                @forelse ($record->reviews->sortByDesc('scheduled_at') as $review)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <strong>{{ $review->scheduled_at->format('d/m/Y') }}</strong> · {{ $review->status }} · {{ $review->result ?? 'Sin resultado' }}
                        <p>{{ $review->notes }}</p>
                    </div>
                @empty
                    <p>Sin revisiones.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Partes, materiales y fotos</x-slot>
        <div class="space-y-4 text-sm">
            @forelse ($record->workOrders->sortByDesc('created_at') as $workOrder)
                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div><strong>Parte #{{ $workOrder->id }}</strong> · {{ $workOrderStatusLabels[$workOrder->status] ?? $workOrder->status }} · {{ $workOrderResultLabels[$workOrder->result] ?? 'Pendiente' }} · {{ $workOrder->finished_at?->format('d/m/Y H:i') ?? 'Abierto' }}</div>
                    <p>{{ $workOrder->work_performed ?: $workOrder->observations }}</p>
                    @if ($workOrder->materials->isNotEmpty())
                        <div class="mt-2">
                            <strong>Materiales:</strong>
                            {{ $workOrder->materials->map(fn ($line) => $line->description.' x'.$line->quantity)->join(', ') }}
                        </div>
                    @endif
                    @if ($workOrder->photos->isNotEmpty())
                        <div class="mt-2"><strong>Fotos:</strong> {{ $workOrder->photos->count() }}</div>
                    @endif
                </div>
            @empty
                <p>Sin partes.</p>
            @endforelse
        </div>
    </x-filament::section>

    <div class="grid gap-6 xl:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Presupuestos</x-slot>
            <div class="space-y-3 text-sm">
                @forelse ($record->quotes->sortByDesc('created_at') as $quote)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <strong>{{ $quote->number }}</strong> · {{ $quote->status }} · {{ number_format((float) $quote->total, 2, ',', '.') }} EUR
                    </div>
                @empty
                    <p>Sin presupuestos.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Facturacion vinculada</x-slot>
            <div class="space-y-3 text-sm">
                @forelse ($record->invoiceLines->sortByDesc('created_at') as $line)
                    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <strong>{{ $line->invoice?->number ?? 'Factura' }}</strong> · {{ $line->description }} · {{ number_format((float) $line->total, 2, ',', '.') }} EUR
                    </div>
                @empty
                    <p>Sin facturacion vinculada.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
