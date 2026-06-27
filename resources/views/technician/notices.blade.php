<x-layouts.mobile heading="Avisos asignados" :subheading="auth()->user()->name">
    @php
        $statusLabels = [
            'pending' => 'Pendiente',
            'assigned' => 'Asignado',
            'in_progress' => 'En curso',
            'completed' => 'Realizado',
            'resolved' => 'Realizado',
            'pending_quote' => 'Pendiente',
            'cancelled' => 'Cancelado',
        ];
    @endphp

    <a class="back-link" href="{{ route('technician.dashboard') }}">&larr; Ruta de hoy</a>

    <div class="metric-grid">
        <div class="metric">
            <strong>{{ $notices->count() }}</strong>
            <span>Avisos pendientes</span>
        </div>
        <div class="metric">
            <strong>{{ $notices->where('priority', 'urgent')->count() }}</strong>
            <span>Urgentes</span>
        </div>
    </div>

    @forelse ($notices as $notice)
        @php
            $phone = $notice->contact_phone ?: $notice->installation->contact_phone;
            $contactName = $notice->contact_name ?: $notice->installation->contact_name;
        @endphp
        <article class="job-card {{ $notice->priority === 'urgent' ? 'urgent' : '' }}">
            <div class="badge-row">
                <span class="badge {{ $notice->priority === 'urgent' ? 'danger' : 'warning' }}">{{ strtoupper($notice->priority) }}</span>
                <span class="badge neutral">{{ $statusLabels[$notice->status] ?? $notice->status }}</span>
                <span class="badge neutral">{{ $notice->scheduled_at?->format('H:i') ?? 'Sin hora' }}</span>
            </div>

            <h3>{{ $notice->installation->name }}</h3>
            <p class="job-meta">
                {{ $notice->customer->legal_name }} ·
                {{ $notice->equipment?->code }} {{ $notice->equipment?->name ?? 'Sin equipo concreto' }}
            </p>
            <p class="problem-text">{{ $notice->description }}</p>

            <div class="contact-strip">
                <div class="contact-item">
                    <span>
                        <small>Direccion</small>
                        <strong>{{ $notice->installation->address }}</strong>
                    </span>
                </div>
                <div class="contact-item">
                    <span>
                        <small>Contacto</small>
                        <strong>{{ $contactName ?: 'Sin contacto indicado' }}</strong>
                    </span>
                </div>
                <div class="contact-item">
                    <span>
                        <small>Telefono</small>
                        <strong>{{ $phone ?: 'Sin telefono' }}</strong>
                    </span>
                </div>
            </div>

            <div class="action-grid">
                <a class="button secondary" href="{{ route('technician.notices.show', $notice) }}">📄 Ver aviso</a>
                <a class="button" href="{{ $notice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">📍 Maps</a>
                @if ($phone)
                    <a class="button secondary" href="tel:{{ $phone }}">📞 Llamar</a>
                @else
                    <span class="button secondary">Sin telefono</span>
                @endif
                @if ($notice->workOrder)
                    <a class="button success full" href="{{ route('technician.work-orders.show', $notice->workOrder) }}">🛠 Abrir parte</a>
                @else
                    <form method="post" action="{{ route('technician.notices.start', $notice) }}">
                        @csrf
                        <button class="button success full" type="submit">🛠 Iniciar parte</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p class="empty-state">No tienes avisos pendientes.</p>
    @endforelse
</x-layouts.mobile>
