@php
    use App\Models\Notice;
    use App\Models\Review;
    use App\Models\WorkOrder;
    use App\Models\User;

    $pendingNotices = Notice::whereIn('status', ['pending', 'assigned', 'in_progress', 'pending_quote'])->count();
    $upcomingReviews = Review::whereIn('status', ['scheduled', 'assigned', 'in_progress'])->where('scheduled_at', '<=', now()->addDays(14))->count();
    $openWorkOrders = WorkOrder::whereIn('status', ['new', 'in_progress'])->count();
    $toInvoice = WorkOrder::where('status', 'closed')->whereDoesntHave('invoiceLines')->count();
    $urgentNotices = Notice::with(['customer', 'installation', 'equipment', 'technician'])
        ->whereIn('status', ['pending', 'assigned', 'in_progress'])
        ->orderByRaw("case when priority = 'urgent' then 0 else 1 end")
        ->orderBy('scheduled_at')
        ->limit(8)
        ->get();
    $technicians = User::where('role', 'technician')->withCount(['assignedNotices', 'assignedReviews'])->get();
@endphp

<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        <x-filament::section>
            <div class="text-sm text-gray-500">Avisos pendientes</div>
            <div class="mt-2 text-3xl font-bold">{{ $pendingNotices }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Revisiones 14 dias</div>
            <div class="mt-2 text-3xl font-bold">{{ $upcomingReviews }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Partes abiertos</div>
            <div class="mt-2 text-3xl font-bold">{{ $openWorkOrders }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Pendiente facturar</div>
            <div class="mt-2 text-3xl font-bold">{{ $toInvoice }}</div>
        </x-filament::section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-filament::section class="xl:col-span-2">
            <x-slot name="heading">Avisos urgentes y asignados</x-slot>
            <div class="space-y-3">
                @foreach ($urgentNotices as $notice)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="font-semibold">{{ $notice->installation->name }}</div>
                                <div class="text-sm text-gray-500">{{ $notice->customer->legal_name }} · {{ $notice->equipment?->name ?? 'Sin equipo concreto' }}</div>
                            </div>
                            <div class="text-sm font-medium uppercase">{{ $notice->priority }} · {{ $notice->status }}</div>
                        </div>
                        <p class="mt-2 text-sm">{{ $notice->description }}</p>
                        <div class="mt-2 text-sm text-gray-500">Tecnico: {{ $notice->technician?->name ?? 'Sin asignar' }}</div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Tecnicos</x-slot>
            <div class="space-y-3">
                @foreach ($technicians as $technician)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div>
                            <div class="font-medium">{{ $technician->name }}</div>
                            <div class="text-sm text-gray-500">{{ $technician->phone }}</div>
                        </div>
                        <div class="text-right text-sm">
                            <div>{{ $technician->assigned_notices_count }} avisos</div>
                            <div>{{ $technician->assigned_reviews_count }} revisiones</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
