@php
    use App\Models\Notice;
    use App\Models\Review;
    use App\Models\WorkOrder;
    use App\Models\User;

    $noticeStatusLabels = [
        'pending' => 'Pendiente',
        'assigned' => 'Asignado',
        'in_progress' => 'En curso',
        'completed' => 'Realizado',
        'resolved' => 'Realizado',
        'pending_quote' => 'Pendiente',
        'cancelled' => 'Cancelado',
    ];

    $pendingNotices = Notice::whereIn('status', ['pending', 'assigned', 'in_progress', 'pending_quote'])->count();
    $upcomingReviews = Review::whereIn('status', ['scheduled', 'assigned', 'in_progress'])->where('scheduled_at', '<=', now()->addDays(14))->count();
    $openWorkOrders = WorkOrder::whereIn('status', ['new', 'open', 'in_progress'])->count();
    $toInvoice = WorkOrder::where('status', 'closed')->whereDoesntHave('invoiceLines')->count();
    $urgentNotices = Notice::with(['customer', 'installation', 'equipment', 'technician'])
        ->whereIn('status', ['pending', 'assigned', 'in_progress'])
        ->orderByRaw("case when priority = 'urgent' then 0 else 1 end")
        ->orderBy('scheduled_at')
        ->limit(8)
        ->get();
    $technicians = User::where('role', 'technician')->withCount(['assignedNotices', 'assignedReviews'])->get();

    $stats = [
        ['label' => 'Avisos pendientes', 'value' => $pendingNotices, 'icon' => 'heroicon-o-bell-alert', 'accent' => 'primary'],
        ['label' => 'Revisiones 14 dias', 'value' => $upcomingReviews, 'icon' => 'heroicon-o-arrow-path', 'accent' => 'warning'],
        ['label' => 'Partes abiertos', 'value' => $openWorkOrders, 'icon' => 'heroicon-o-clipboard-document-list', 'accent' => 'primary'],
        ['label' => 'Pendiente facturar', 'value' => $toInvoice, 'icon' => 'heroicon-o-banknotes', 'accent' => 'warning'],
    ];

    $accentClasses = [
        'primary' => ['icon' => 'text-primary-500 bg-primary-500/10', 'value' => 'text-primary-600 dark:text-primary-400'],
        'warning' => ['icon' => 'text-warning-500 bg-warning-500/10', 'value' => 'text-warning-600 dark:text-warning-400'],
    ];
@endphp

<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-4">
        @foreach ($stats as $stat)
            <x-filament::section>
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl {{ $accentClasses[$stat['accent']]['icon'] }}">
                        <x-filament::icon :icon="$stat['icon']" class="h-6 w-6" />
                    </div>
                    <div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</div>
                        <div class="mt-1 text-3xl font-bold {{ $accentClasses[$stat['accent']]['value'] }}">{{ $stat['value'] }}</div>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <x-filament::section class="xl:col-span-2">
            <x-slot name="heading">Avisos urgentes y asignados</x-slot>
            <div class="space-y-3">
                @forelse ($urgentNotices as $notice)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <div class="font-semibold">{{ $notice->installation->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $notice->customer->legal_name }} · {{ $notice->equipment?->name ?? 'Sin equipo concreto' }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-filament::badge :color="$notice->priority === 'urgent' ? 'danger' : 'warning'">
                                    {{ strtoupper($notice->priority) }}
                                </x-filament::badge>
                                <x-filament::badge color="primary">
                                    {{ $noticeStatusLabels[$notice->status] ?? $notice->status }}
                                </x-filament::badge>
                            </div>
                        </div>
                        <p class="mt-2 text-sm">{{ $notice->description }}</p>
                        <div class="mt-2 flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400">
                            <x-filament::icon icon="heroicon-o-user" class="h-4 w-4" />
                            {{ $notice->technician?->name ?? 'Sin asignar' }}
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay avisos urgentes pendientes.</p>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Tecnicos</x-slot>
            <div class="space-y-3">
                @forelse ($technicians as $technician)
                    <div class="flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-white/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-500/10 text-sm font-semibold text-primary-600 dark:text-primary-400">
                                {{ strtoupper(substr($technician->name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-medium">{{ $technician->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $technician->phone }}</div>
                            </div>
                        </div>
                        <div class="text-right text-sm">
                            <div>{{ $technician->assigned_notices_count }} avisos</div>
                            <div class="text-gray-500 dark:text-gray-400">{{ $technician->assigned_reviews_count }} revisiones</div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay tecnicos registrados.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
