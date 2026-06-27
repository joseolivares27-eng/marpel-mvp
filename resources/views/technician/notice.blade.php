<x-layouts.mobile :heading="'Aviso '.strtoupper($notice->priority)" :subheading="$notice->installation->name">
    @php
        $phone = $notice->contact_phone ?: $notice->installation->contact_phone;
        $contactName = $notice->contact_name ?: $notice->installation->contact_name;
        $statusLabels = [
            'pending' => 'Pendiente',
            'assigned' => 'Asignado',
            'in_progress' => 'En curso',
            'completed' => 'Realizado',
            'resolved' => 'Realizado',
            'pending_quote' => 'Pendiente',
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

    <a class="back-link" href="{{ route('technician.notices.index') }}">&larr; Avisos</a>

    <section class="route-hero {{ $notice->priority === 'urgent' ? 'urgent' : '' }}">
        <div class="badge-row">
            <span class="badge {{ $notice->priority === 'urgent' ? 'danger' : 'warning' }}">{{ strtoupper($notice->priority) }}</span>
            <span class="badge neutral">{{ $statusLabels[$notice->status] ?? $notice->status }}</span>
        </div>
        <p class="card-kicker">Trabajo solicitado</p>
        <h2>{{ $notice->installation->name }}</h2>
        <p class="job-meta">{{ $notice->customer->legal_name }} · {{ $notice->equipment?->code }} {{ $notice->equipment?->name ?? 'Sin equipo concreto' }}</p>
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
            <a class="button" href="{{ $notice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">📍 Abrir Maps</a>
            <a class="button secondary" href="{{ $notice->installation->wazeUrl() }}" target="_blank" rel="noreferrer">🧭 Waze</a>
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
    </section>

    <h2 class="section-title">Acceso</h2>
    <section class="job-card">
        <p class="problem-text">{{ $notice->installation->access_instructions ?: 'Sin instrucciones de acceso.' }}</p>
    </section>

    @if ($notice->equipment)
        <h2 class="section-title">Historial equipo</h2>
        <section class="job-card">
            <div class="history-list">
                @forelse ($notice->equipment->workOrders as $history)
                    <div class="history-item">
                        <strong>{{ $history->finished_at?->format('d/m/Y') ?? 'Sin fecha' }}</strong>
                        <span>{{ $history->work_performed ?: ($workOrderResultLabels[$history->result] ?? 'Parte sin descripcion.') }}</span>
                    </div>
                @empty
                    <p class="job-meta">Sin partes anteriores.</p>
                @endforelse

                @foreach ($notice->equipment->reviews as $review)
                    <div class="history-item">
                        <strong>{{ $review->performed_at?->format('d/m/Y') ?? $review->scheduled_at?->format('d/m/Y') }}</strong>
                        <span>Revision {{ $review->result ?: $review->status }}</span>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.mobile>
