<x-layouts.mobile heading="Hoy" :subheading="auth()->user()->name">
    <div class="quick-grid">
        <div class="metric">
            <strong>{{ $notices->count() }}</strong>
            <span>Avisos</span>
        </div>
        <div class="metric">
            <strong>{{ $reviews->count() }}</strong>
            <span>Revisiones</span>
        </div>
    </div>

    @php
        $nextNotice = $notices->first();
    @endphp

    @if ($nextNotice)
        <section class="job-card {{ $nextNotice->priority === 'urgent' ? 'urgent' : '' }}">
            <div class="badge-row">
                <span class="badge {{ $nextNotice->priority === 'urgent' ? 'warning' : '' }}">{{ strtoupper($nextNotice->priority) }}</span>
                <span class="badge">{{ $nextNotice->status }}</span>
            </div>
            <h2>{{ $nextNotice->installation->name }}</h2>
            <p class="job-meta">{{ $nextNotice->customer->legal_name }} · {{ $nextNotice->equipment?->code }} {{ $nextNotice->equipment?->name ?? 'Sin equipo concreto' }}</p>
            <p>{{ $nextNotice->description }}</p>
            <div class="action-grid">
                <a class="button" href="{{ $nextNotice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Maps</a>
                <a class="button secondary" href="tel:{{ $nextNotice->contact_phone ?: $nextNotice->installation->contact_phone }}">Llamar</a>
                <a class="button secondary" href="{{ route('technician.notices.show', $nextNotice) }}">Ver aviso</a>
                <form method="post" action="{{ route('technician.notices.start', $nextNotice) }}">
                    @csrf
                    <button class="button success full" type="submit">Iniciar</button>
                </form>
            </div>
        </section>
    @endif

    <h2 id="avisos" class="section-title">Avisos asignados</h2>
    @forelse ($notices as $notice)
        <article class="job-card {{ $notice->priority === 'urgent' ? 'urgent' : '' }}">
            <div class="badge-row">
                <span class="badge {{ $notice->priority === 'urgent' ? 'warning' : '' }}">{{ strtoupper($notice->priority) }}</span>
                <span class="badge">{{ $notice->scheduled_at?->format('H:i') ?? 'Sin hora' }}</span>
            </div>
            <h3>{{ $notice->installation->name }}</h3>
            <p class="job-meta">{{ $notice->equipment?->code }} {{ $notice->equipment?->name ?? 'Instalacion' }}</p>
            <div class="action-grid">
                <a class="button secondary" href="{{ route('technician.notices.show', $notice) }}">Abrir</a>
                <a class="button" href="{{ $notice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Maps</a>
            </div>
        </article>
    @empty
        <p class="job-meta">No tienes avisos pendientes.</p>
    @endforelse

    <h2 class="section-title">Revisiones</h2>
    @forelse ($reviews as $review)
        <article class="job-card">
            <div class="badge-row">
                <span class="badge success">REVISION</span>
                <span class="badge">{{ $review->scheduled_at->format('H:i') }}</span>
            </div>
            <h3>{{ $review->installation->name }}</h3>
            <p class="job-meta">{{ $review->equipment->code }} {{ $review->equipment->name }}</p>
            <div class="action-grid">
                <a class="button secondary" href="{{ $review->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Maps</a>
                <form method="post" action="{{ route('technician.reviews.start', $review) }}">
                    @csrf
                    <button class="button success full" type="submit">Iniciar</button>
                </form>
            </div>
        </article>
    @empty
        <p class="job-meta">No tienes revisiones pendientes.</p>
    @endforelse

    <h2 id="partes" class="section-title">Partes abiertos</h2>
    @forelse ($workOrders as $workOrder)
        <article class="job-card">
            <span class="badge">{{ $workOrder->status }}</span>
            <h3>{{ $workOrder->installation->name }}</h3>
            <p class="job-meta">{{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Sin equipo concreto' }}</p>
            <a class="button full" href="{{ route('technician.work-orders.show', $workOrder) }}">Continuar parte</a>
        </article>
    @empty
        <p class="job-meta">No hay partes abiertos.</p>
    @endforelse
</x-layouts.mobile>
