<x-layouts.mobile :heading="'Aviso '.strtoupper($notice->priority)" :subheading="$notice->installation->name">
    <section class="job-card {{ $notice->priority === 'urgent' ? 'urgent' : '' }}">
        <div class="badge-row">
            <span class="badge {{ $notice->priority === 'urgent' ? 'warning' : '' }}">{{ strtoupper($notice->priority) }}</span>
            <span class="badge">{{ $notice->status }}</span>
        </div>
        <h2>{{ $notice->installation->name }}</h2>
        <p class="job-meta">{{ $notice->customer->legal_name }}</p>
        <p class="job-meta">{{ $notice->equipment?->code }} {{ $notice->equipment?->name ?? 'Sin equipo concreto' }}</p>
        <p>{{ $notice->description }}</p>

        <div class="action-grid">
            <a class="button" href="{{ $notice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Maps</a>
            <a class="button secondary" href="{{ $notice->installation->wazeUrl() }}" target="_blank" rel="noreferrer">Waze</a>
            <a class="button secondary" href="tel:{{ $notice->contact_phone ?: $notice->installation->contact_phone }}">Llamar</a>
            <form method="post" action="{{ route('technician.notices.start', $notice) }}">
                @csrf
                <button class="button success full" type="submit">Iniciar parte</button>
            </form>
        </div>
    </section>

    <h2 class="section-title">Acceso</h2>
    <section class="job-card">
        <p><strong>Direccion:</strong> {{ $notice->installation->address }}</p>
        <p><strong>Contacto:</strong> {{ $notice->contact_name ?: $notice->installation->contact_name }}</p>
        <p><strong>Telefono:</strong> {{ $notice->contact_phone ?: $notice->installation->contact_phone }}</p>
        <p>{{ $notice->installation->access_instructions ?: 'Sin instrucciones de acceso.' }}</p>
    </section>

    @if ($notice->equipment)
        <h2 class="section-title">Historial equipo</h2>
        <section class="job-card">
            @forelse ($notice->equipment->workOrders as $history)
                <p><strong>{{ $history->finished_at?->format('d/m/Y') ?? 'Sin fecha' }}</strong> · {{ $history->work_performed ?: $history->result }}</p>
            @empty
                <p class="job-meta">Sin partes anteriores.</p>
            @endforelse

            @foreach ($notice->equipment->reviews as $review)
                <p><strong>{{ $review->performed_at?->format('d/m/Y') ?? $review->scheduled_at?->format('d/m/Y') }}</strong> · Revision {{ $review->result ?: $review->status }}</p>
            @endforeach
        </section>
    @endif
</x-layouts.mobile>
