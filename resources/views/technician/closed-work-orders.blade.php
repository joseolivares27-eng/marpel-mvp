<x-layouts.mobile heading="Partes cerrados" :subheading="auth()->user()->name">
    @php
        $resultLabels = [
            'pending' => 'Pendiente',
            'solved' => 'Solucionado',
            'unresolved' => 'No solucionado',
            'cancelled' => 'Anulado',
        ];
    @endphp

    <a class="back-link" href="{{ route('technician.dashboard') }}">&larr; Ruta de hoy</a>

    <div class="metric-grid">
        <div class="metric">
            <strong>{{ $workOrders->count() }}</strong>
            <span>Partes cerrados</span>
        </div>
        <div class="metric">
            <strong>{{ $workOrders->where('result', 'solved')->count() }}</strong>
            <span>Solucionados</span>
        </div>
    </div>

    @forelse ($workOrders as $workOrder)
        <article class="job-card">
            <div class="badge-row">
                <span class="badge {{ $workOrder->result === 'solved' ? 'success' : ($workOrder->result === 'unresolved' ? 'warning' : 'neutral') }}">
                    {{ $resultLabels[$workOrder->result] ?? 'Pendiente' }}
                </span>
                <span class="badge neutral">Parte {{ $workOrder->folio_label }}</span>
            </div>
            <h3>{{ $workOrder->installation->name }}</h3>
            <p class="job-meta">{{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Sin equipo concreto' }}</p>
            <p class="job-meta">Cerrado el {{ $workOrder->finished_at?->format('d/m/Y H:i') ?? '-' }}</p>
            <div class="action-grid">
                <a class="button secondary" href="{{ route('technician.work-orders.show', $workOrder) }}">📄 Ver parte</a>
                <a class="button" href="{{ route('work-orders.pdf.download', $workOrder) }}">⬇ Descargar PDF</a>
            </div>
        </article>
    @empty
        <p class="empty-state">Todavia no tienes partes cerrados.</p>
    @endforelse
</x-layouts.mobile>
