<x-layouts.mobile :heading="'Parte #'.$workOrder->id" :subheading="$workOrder->installation->name">
    @php
        $defaultResult = old('result', $workOrder->result ?: ($workOrder->review ? 'ok' : 'solved'));
        $phone = $workOrder->notice?->contact_phone ?: $workOrder->installation->contact_phone;
        $originLabel = $workOrder->notice ? 'Aviso' : ($workOrder->review ? 'Revision' : 'Manual');
        $statusLabels = [
            'new' => 'Nuevo',
            'in_progress' => 'En curso',
            'closed' => 'Cerrado',
            'cancelled' => 'Cancelado',
        ];
        $oldMaterials = old('materials');
        $materialRows = $oldMaterials !== null
            ? collect($oldMaterials)
            : $workOrder->materials->map(fn ($line) => [
                'material_id' => $line->material_id,
                'description' => $line->description,
                'quantity' => $line->quantity,
            ]);

        while ($materialRows->count() < 3) {
            $materialRows->push(['material_id' => null, 'description' => '', 'quantity' => '']);
        }
    @endphp

    <a class="back-link" href="{{ route('technician.dashboard') }}#partes">Partes</a>

    <section class="route-hero {{ $workOrder->notice?->priority === 'urgent' ? 'urgent' : '' }}">
        <div class="badge-row">
            <span class="badge {{ $workOrder->status === 'new' ? 'danger' : 'success' }}">{{ $statusLabels[$workOrder->status] ?? $workOrder->status }}</span>
            <span class="badge neutral">{{ $originLabel }}</span>
            @if ($workOrder->customer_signature_path)
                <span class="badge success">Firmado</span>
            @endif
        </div>
        <p class="card-kicker">Parte de trabajo</p>
        <h2>{{ $workOrder->installation->name }}</h2>
        <p class="job-meta">{{ $workOrder->customer->legal_name }} · {{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Sin equipo concreto' }}</p>
        <p class="problem-text">
            @if ($workOrder->notice)
                {{ $workOrder->notice->description }}
            @elseif ($workOrder->review)
                {{ $workOrder->review->notes ?: 'Revision programada.' }}
            @else
                {{ $workOrder->observations ?: 'Parte manual sin descripcion.' }}
            @endif
        </p>

        <div class="contact-strip">
            <div class="contact-item">
                <span>
                    <small>Direccion</small>
                    <strong>{{ $workOrder->installation->address }}</strong>
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
            <a class="button" href="{{ $workOrder->installation->mapsUrl() }}" target="_blank" rel="noreferrer">Abrir Maps</a>
            @if ($phone)
                <a class="button secondary" href="tel:{{ $phone }}">Llamar</a>
            @else
                <span class="button secondary">Sin telefono</span>
            @endif
        </div>
    </section>

    <form method="post" action="{{ route('technician.work-orders.update', $workOrder) }}" enctype="multipart/form-data">
        @csrf

        <section class="form-card">
            <h2>Trabajo</h2>
            <div class="field">
                <label for="work_performed">Trabajo realizado</label>
                <textarea id="work_performed" name="work_performed" class="textarea" data-draft-key="work-order-{{ $workOrder->id }}-work" placeholder="Ej. Ajustado motor, revisadas fotocelulas, puerta funcionando.">{{ old('work_performed', $workOrder->work_performed) }}</textarea>
            </div>

            <div class="field">
                <label for="observations">Observaciones</label>
                <textarea id="observations" name="observations" class="textarea" data-draft-key="work-order-{{ $workOrder->id }}-obs" placeholder="Notas para oficina o para presupuesto.">{{ old('observations', $workOrder->observations) }}</textarea>
            </div>

            <div class="field">
                <label for="result">Resultado</label>
                <select id="result" name="result" class="select">
                    @foreach ([
                        'solved' => 'Solucionado',
                        'pending_material' => 'Pendiente material',
                        'requires_quote' => 'Requiere presupuesto',
                        'not_located' => 'No localizado',
                        'ok' => 'Revision correcta',
                        'incident' => 'Incidencia revision',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($defaultResult === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <h2 class="section-title">Materiales <small>Opcional</small></h2>
        @foreach ($materialRows as $i => $materialRow)
            <section class="material-card">
                <div class="field">
                    <label for="material_{{ $i }}">Material catalogo</label>
                    <select id="material_{{ $i }}" name="materials[{{ $i }}][material_id]" class="select">
                        <option value="">Material manual / sin catalogo</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}" @selected((string) ($materialRow['material_id'] ?? '') === (string) $material->id)>{{ $material->name }} · stock {{ $material->stock_quantity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Descripcion manual</label>
                    <input class="input" name="materials[{{ $i }}][description]" value="{{ $materialRow['description'] ?? '' }}" placeholder="Ej. Fotocelula, mando, cable">
                </div>
                <div class="field">
                    <label>Cantidad</label>
                    <input class="input" name="materials[{{ $i }}][quantity]" type="number" min="0" step="0.01" value="{{ $materialRow['quantity'] ?? ($i === 0 ? '1' : '') }}">
                </div>
            </section>
        @endforeach

        <section class="form-card">
            <h2>Fotos</h2>
            <div class="field">
                <label for="photos">Fotografias</label>
                <input id="photos" class="input" name="photos[]" type="file" accept="image/*" capture="environment" multiple>
            </div>
        </section>

        @if ($workOrder->photos->isNotEmpty())
            <h2 class="section-title">Fotos subidas</h2>
            <div class="photo-grid">
                @foreach ($workOrder->photos as $photo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" alt="Foto parte">
                @endforeach
            </div>
        @endif

        <div class="action-grid">
            <button class="button secondary" name="action" value="save" type="submit">Guardar cambios</button>
        </div>

        <div class="sticky-action-row">
            @if ($workOrder->customer_signature_path && $workOrder->status !== 'closed')
                <button class="button success full" name="action" value="close" type="submit">Cerrar parte</button>
            @else
                <button class="button success full" name="action" value="sign" type="submit">Firmar y cerrar parte</button>
            @endif
        </div>
    </form>
</x-layouts.mobile>
