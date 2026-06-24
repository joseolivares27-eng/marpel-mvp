<x-layouts.mobile heading="Ruta de hoy" :subheading="auth()->user()->name">
    @php
        $nextWorkOrder = $workOrders->first();
        $nextNotice = $notices->first();
        $nextReview = $reviews->first();
        $focusPhone = null;
        $focusIsUrgent = false;
        $statusLabels = [
            'new' => 'Abierto',
            'open' => 'Abierto',
            'in_progress' => 'En curso',
            'pending' => 'Pendiente',
            'assigned' => 'Asignado',
            'completed' => 'Realizado',
            'resolved' => 'Realizado',
            'cancelled' => 'Cancelado',
            'scheduled' => 'Programada',
        ];
    @endphp

    <div class="metric-grid">
        <div class="metric">
            <strong>{{ $workOrders->count() }}</strong>
            <span>Partes abiertos</span>
        </div>
        <div class="metric">
            <strong>{{ $notices->count() + $reviews->count() }}</strong>
            <span>Avisos y revisiones</span>
        </div>
    </div>

    @if ($nextWorkOrder)
        @php
            $focusPhone = $nextWorkOrder->notice?->contact_phone ?: $nextWorkOrder->installation->contact_phone;
            $focusIsUrgent = $nextWorkOrder->notice?->priority === 'urgent';
        @endphp
        <section class="route-hero {{ $focusIsUrgent ? 'urgent' : '' }}">
            <div class="badge-row">
                <span class="badge {{ $focusIsUrgent ? 'danger' : 'success' }}">{{ $focusIsUrgent ? 'URGENTE' : 'PARTE' }}</span>
                <span class="badge neutral">{{ $statusLabels[$nextWorkOrder->status] ?? $nextWorkOrder->status }}</span>
            </div>
            <p class="card-kicker">Siguiente trabajo</p>
            <h2>{{ $nextWorkOrder->installation->name }}</h2>
            <p class="job-meta">Parte #{{ $nextWorkOrder->id }} · {{ $nextWorkOrder->equipment?->code }} {{ $nextWorkOrder->equipment?->name ?? 'Sin equipo concreto' }}</p>
            <p class="problem-text">
                @if ($nextWorkOrder->notice)
                    {{ $nextWorkOrder->notice->description }}
                @elseif ($nextWorkOrder->review)
                    {{ $nextWorkOrder->review->notes ?: 'Revision programada.' }}
                @else
                    {{ $nextWorkOrder->observations ?: 'Parte de trabajo asignado.' }}
                @endif
            </p>
            <div class="contact-strip">
                <div class="contact-item">
                    <span>
                        <small>Direccion</small>
                        <strong>{{ $nextWorkOrder->installation->address }}</strong>
                    </span>
                </div>
                <div class="contact-item">
                    <span>
                        <small>Llamar</small>
                        <strong>{{ $focusPhone ?: 'Sin telefono' }}</strong>
                    </span>
                </div>
            </div>
            <div class="action-grid">
                <a class="button" href="{{ $nextWorkOrder->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Abrir Maps</a>
                @if ($focusPhone)
                    <a class="button secondary" href="tel:{{ $focusPhone }}">Llamar</a>
                @else
                    <span class="button secondary">Sin telefono</span>
                @endif
            </div>
            <div class="primary-action-grid">
                <a class="button success full" href="{{ route('technician.work-orders.show', $nextWorkOrder) }}">
                    {{ in_array($nextWorkOrder->status, ['new', 'open'], true) ? 'Abrir parte' : 'Continuar parte' }}
                </a>
            </div>
        </section>
    @elseif ($nextNotice)
        @php($focusPhone = $nextNotice->contact_phone ?: $nextNotice->installation->contact_phone)
        <section class="route-hero {{ $nextNotice->priority === 'urgent' ? 'urgent' : '' }}">
            <div class="badge-row">
                <span class="badge {{ $nextNotice->priority === 'urgent' ? 'danger' : 'warning' }}">{{ strtoupper($nextNotice->priority) }}</span>
                <span class="badge neutral">{{ $statusLabels[$nextNotice->status] ?? $nextNotice->status }}</span>
            </div>
            <p class="card-kicker">Siguiente aviso</p>
            <h2>{{ $nextNotice->installation->name }}</h2>
            <p class="job-meta">{{ $nextNotice->customer->legal_name }} · {{ $nextNotice->equipment?->code }} {{ $nextNotice->equipment?->name ?? 'Sin equipo concreto' }}</p>
            <p class="problem-text">{{ $nextNotice->description }}</p>
            <div class="contact-strip">
                <div class="contact-item">
                    <span>
                        <small>Direccion</small>
                        <strong>{{ $nextNotice->installation->address }}</strong>
                    </span>
                </div>
                <div class="contact-item">
                    <span>
                        <small>Llamar</small>
                        <strong>{{ $focusPhone ?: 'Sin telefono' }}</strong>
                    </span>
                </div>
            </div>
            <div class="action-grid">
                <a class="button" href="{{ $nextNotice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Abrir Maps</a>
                @if ($focusPhone)
                    <a class="button secondary" href="tel:{{ $focusPhone }}">Llamar</a>
                @else
                    <span class="button secondary">Sin telefono</span>
                @endif
            </div>
            <div class="primary-action-grid">
                @if ($nextNotice->workOrder)
                    <a class="button success full" href="{{ route('technician.work-orders.show', $nextNotice->workOrder) }}">Abrir parte</a>
                @else
                    <form method="post" action="{{ route('technician.notices.start', $nextNotice) }}">
                        @csrf
                        <button class="button success full" type="submit">Iniciar parte</button>
                    </form>
                @endif
            </div>
        </section>
    @elseif ($nextReview)
        @php($focusPhone = $nextReview->installation->contact_phone)
        <section class="route-hero">
            <div class="badge-row">
                <span class="badge success">REVISION</span>
                <span class="badge neutral">{{ $nextReview->scheduled_at->format('H:i') }}</span>
            </div>
            <p class="card-kicker">Siguiente revision</p>
            <h2>{{ $nextReview->installation->name }}</h2>
            <p class="job-meta">{{ $nextReview->equipment->code }} {{ $nextReview->equipment->name }}</p>
            <p class="problem-text">{{ $nextReview->notes ?: 'Revision programada.' }}</p>
            <div class="action-grid">
                <a class="button" href="{{ $nextReview->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Abrir Maps</a>
                @if ($focusPhone)
                    <a class="button secondary" href="tel:{{ $focusPhone }}">Llamar</a>
                @else
                    <span class="button secondary">Sin telefono</span>
                @endif
            </div>
            <div class="primary-action-grid">
                @if ($nextReview->workOrder)
                    <a class="button success full" href="{{ route('technician.work-orders.show', $nextReview->workOrder) }}">Abrir parte</a>
                @else
                    <form method="post" action="{{ route('technician.reviews.start', $nextReview) }}">
                        @csrf
                        <button class="button success full" type="submit">Iniciar revision</button>
                    </form>
                @endif
            </div>
        </section>
    @else
        <div class="empty-state">No tienes trabajos pendientes para hoy.</div>
    @endif

    <h2 id="avisos" class="section-title">Avisos asignados <small>{{ $notices->count() }}</small></h2>
    @forelse ($notices as $notice)
        @php($noticePhone = $notice->contact_phone ?: $notice->installation->contact_phone)
        <article class="job-card {{ $notice->priority === 'urgent' ? 'urgent' : '' }}">
            <div class="badge-row">
                <span class="badge {{ $notice->priority === 'urgent' ? 'danger' : 'warning' }}">{{ strtoupper($notice->priority) }}</span>
                <span class="badge neutral">{{ $notice->scheduled_at?->format('H:i') ?? 'Sin hora' }}</span>
            </div>
            <h3>{{ $notice->installation->name }}</h3>
            <p class="job-meta">{{ $notice->equipment?->code }} {{ $notice->equipment?->name ?? 'Instalacion' }}</p>
            <p class="problem-text">{{ $notice->description }}</p>
            <div class="action-grid">
                <a class="button secondary" href="{{ route('technician.notices.show', $notice) }}">Ver aviso</a>
                <a class="button" href="{{ $notice->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Maps</a>
            </div>
            @if ($noticePhone)
                <div class="primary-action-grid">
                    <a class="button secondary full" href="tel:{{ $noticePhone }}">Llamar {{ $noticePhone }}</a>
                </div>
            @endif
            <div class="primary-action-grid">
                @if ($notice->workOrder)
                    <a class="button success full" href="{{ route('technician.work-orders.show', $notice->workOrder) }}">Abrir parte</a>
                @else
                    <form method="post" action="{{ route('technician.notices.start', $notice) }}">
                        @csrf
                        <button class="button success full" type="submit">Iniciar parte</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p class="empty-state">No tienes avisos pendientes.</p>
    @endforelse

    <h2 class="section-title">Revisiones <small>{{ $reviews->count() }}</small></h2>
    @forelse ($reviews as $review)
        <article class="job-card">
            <div class="badge-row">
                <span class="badge success">REVISION</span>
                <span class="badge neutral">{{ $review->scheduled_at->format('H:i') }}</span>
            </div>
            <h3>{{ $review->installation->name }}</h3>
            <p class="job-meta">{{ $review->equipment->code }} {{ $review->equipment->name }}</p>
            <div class="action-grid">
                <a class="button secondary" href="{{ $review->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Maps</a>
                @if ($review->workOrder)
                    <a class="button success full" href="{{ route('technician.work-orders.show', $review->workOrder) }}">Abrir parte</a>
                @else
                    <form method="post" action="{{ route('technician.reviews.start', $review) }}">
                        @csrf
                        <button class="button success full" type="submit">Iniciar</button>
                    </form>
                @endif
            </div>
        </article>
    @empty
        <p class="empty-state">No tienes revisiones pendientes.</p>
    @endforelse

    <h2 id="partes" class="section-title">Partes abiertos <small>{{ $workOrders->count() }}</small></h2>
    @forelse ($workOrders as $workOrder)
        <article class="job-card">
            <div class="badge-row">
                <span class="badge {{ in_array($workOrder->status, ['new', 'open'], true) ? 'danger' : 'success' }}">{{ $statusLabels[$workOrder->status] ?? $workOrder->status }}</span>
                <span class="badge neutral">Parte #{{ $workOrder->id }}</span>
            </div>
            <h3>{{ $workOrder->installation->name }}</h3>
            <p class="job-meta">{{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Sin equipo concreto' }}</p>
            <a class="button full" href="{{ route('technician.work-orders.show', $workOrder) }}">{{ in_array($workOrder->status, ['new', 'open'], true) ? 'Abrir parte' : 'Continuar parte' }}</a>
        </article>
    @empty
        <p class="empty-state">No hay partes abiertos.</p>
    @endforelse
</x-layouts.mobile>
