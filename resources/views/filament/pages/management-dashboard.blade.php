@php
    use App\Models\Customer;
    use App\Models\Invoice;
    use App\Models\Notice;
    use App\Models\Quote;
    use App\Models\Review;
    use App\Models\WorkOrder;

    $monthlyRevenue = Invoice::where('status', 'paid')->whereMonth('paid_at', now()->month)->sum('total');
    $openNotices = Notice::whereNotIn('status', ['closed', 'cancelled'])->count();
    $pendingReviews = Review::whereIn('status', ['scheduled', 'in_progress'])->count();
    $activeCustomers = Customer::where('status', 'active')->count();
    $pendingQuotes = Quote::whereIn('status', ['sent', 'draft'])->count();
    $closedNotInvoiced = WorkOrder::where('status', 'closed')->whereDoesntHave('invoiceLines')->count();
@endphp

<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
        <x-filament::section>
            <div class="text-sm text-gray-500">Facturacion mes</div>
            <div class="mt-2 text-2xl font-bold">{{ number_format($monthlyRevenue, 2, ',', '.') }} EUR</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Avisos abiertos</div>
            <div class="mt-2 text-2xl font-bold">{{ $openNotices }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Revisiones pendientes</div>
            <div class="mt-2 text-2xl font-bold">{{ $pendingReviews }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Clientes activos</div>
            <div class="mt-2 text-2xl font-bold">{{ $activeCustomers }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Presupuestos vivos</div>
            <div class="mt-2 text-2xl font-bold">{{ $pendingQuotes }}</div>
        </x-filament::section>
        <x-filament::section>
            <div class="text-sm text-gray-500">Partes sin facturar</div>
            <div class="mt-2 text-2xl font-bold">{{ $closedNotInvoiced }}</div>
        </x-filament::section>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Alertas</x-slot>
            <ul class="space-y-2 text-sm">
                <li>Revisar presupuestos enviados sin respuesta.</li>
                <li>Facturar partes cerrados al final de cada jornada.</li>
                <li>Priorizar revisiones vencidas para contratos activos.</li>
            </ul>
        </x-filament::section>
        <x-filament::section>
            <x-slot name="heading">Rentabilidad MVP</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                El MVP deja preparadas las lineas de materiales, horas de parte y facturacion para calcular margen por cliente, instalacion y tecnico en la siguiente iteracion.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
