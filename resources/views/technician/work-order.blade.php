<x-layouts.mobile :heading="'Parte #'.$workOrder->id" :subheading="$workOrder->installation->name">
    @php
        $resultLabels = [
            'pending' => 'pending',
            'pendiente' => 'pending',
            'pending_material' => 'pending',
            'requires_quote' => 'pending',
            'solved' => 'solved',
            'solucionado' => 'solved',
            'ok' => 'solved',
            'not_solved' => 'not_solved',
            'no_solucionado' => 'not_solved',
            'not_located' => 'not_solved',
            'incident' => 'not_solved',
        ];
        $defaultResult = old('result', $resultLabels[$workOrder->result] ?? 'pending');
        $phone = $workOrder->notice?->contact_phone ?: $workOrder->installation->contact_phone;
        $originLabel = $workOrder->notice ? 'Aviso' : ($workOrder->review ? 'Revision' : 'Manual');
        $hasSignature = (bool) $workOrder->customer_signature_path;
        $statusLabels = [
            'new' => 'Abierto',
            'open' => 'Abierto',
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
            <span class="badge {{ in_array($workOrder->status, ['new', 'open'], true) ? 'danger' : 'success' }}">{{ $statusLabels[$workOrder->status] ?? $workOrder->status }}</span>
            <span class="badge neutral">{{ $originLabel }}</span>
            @if ($hasSignature)
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
                        'pending' => 'Pendiente',
                        'solved' => 'Solucionado',
                        'not_solved' => 'No solucionado',
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

        <section class="form-card">
            <h2>Firma del cliente</h2>
            @if ($hasSignature)
                <p class="problem-text">
                    Firma guardada
                    @if ($workOrder->customer_name)
                        por {{ $workOrder->customer_name }}
                    @endif
                    @if ($workOrder->signed_at)
                        el {{ $workOrder->signed_at->format('d/m/Y H:i') }}
                    @endif
                </p>
            @else
                <p class="job-meta">Necesaria para cerrar el parte.</p>
            @endif

            <div class="field">
                <label for="customer_name">Nombre firmante</label>
                <input id="customer_name" class="input" name="customer_name" value="{{ old('customer_name', $workOrder->customer_name) }}" placeholder="Nombre y apellidos">
            </div>

            <div class="field signature-panel">
                <label for="signature-pad">Firma tactil</label>
                <div class="signature-wrap">
                    <canvas id="signature-pad" class="signature-pad"></canvas>
                    <div id="signature-placeholder" class="signature-placeholder">{{ $hasSignature ? 'Firmar de nuevo si hace falta' : 'Firma aqui' }}</div>
                </div>
                <input id="signature_data" type="hidden" name="signature_data">
            </div>

            <div class="action-grid">
                <button id="clear-signature" class="button secondary" type="button">Limpiar firma</button>
                <span class="button secondary">Fecha automatica</span>
            </div>
        </section>

        <div class="action-grid">
            <button class="button secondary" name="action" value="save" type="submit">Guardar cambios</button>
        </div>

        <div class="sticky-action-row">
            <button class="button success full" name="action" value="close" type="submit">
                {{ $hasSignature ? 'Cerrar parte' : 'Guardar firma y cerrar parte' }}
            </button>
        </div>
    </form>

    <script>
        (() => {
            const canvas = document.getElementById('signature-pad');
            const input = document.getElementById('signature_data');
            const clearButton = document.getElementById('clear-signature');
            const placeholder = document.getElementById('signature-placeholder');

            if (! canvas || ! input) {
                return;
            }

            const context = canvas.getContext('2d');
            if (! context) {
                return;
            }

            let drawing = false;
            let hasInk = false;

            const configureCanvas = () => {
                const rect = canvas.getBoundingClientRect();
                const ratio = Math.max(window.devicePixelRatio || 1, 1);

                canvas.width = Math.floor(rect.width * ratio);
                canvas.height = Math.floor(rect.height * ratio);
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 3;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = '#12233d';
            };

            const pointFromEvent = (event) => {
                const pointer = event.touches ? event.touches[0] : event;
                const rect = canvas.getBoundingClientRect();

                return {
                    x: pointer.clientX - rect.left,
                    y: pointer.clientY - rect.top,
                };
            };

            const start = (event) => {
                event.preventDefault();
                drawing = true;
                hasInk = true;

                if (placeholder) {
                    placeholder.hidden = true;
                }

                const point = pointFromEvent(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
            };

            const move = (event) => {
                if (! drawing) {
                    return;
                }

                event.preventDefault();
                const point = pointFromEvent(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                input.value = canvas.toDataURL('image/png');
            };

            const stop = () => {
                if (! drawing) {
                    return;
                }

                drawing = false;
                input.value = hasInk ? canvas.toDataURL('image/png') : '';
            };

            configureCanvas();
            window.addEventListener('resize', configureCanvas);
            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', stop);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', stop);
            canvas.addEventListener('touchcancel', stop);

            clearButton?.addEventListener('click', () => {
                context.clearRect(0, 0, canvas.width, canvas.height);
                hasInk = false;
                drawing = false;
                input.value = '';

                if (placeholder) {
                    placeholder.hidden = false;
                }
            });
        })();
    </script>
</x-layouts.mobile>
