<x-layouts.mobile :heading="'Parte '.$workOrder->folio_label" :subheading="$workOrder->installation->name">
    @php
        $resultLabels = [
            'pending' => 'pending',
            'pendiente' => 'pending',
            'pending_material' => 'pending',
            'requires_quote' => 'pending',
            'solved' => 'solved',
            'solucionado' => 'solved',
            'ok' => 'solved',
            'not_solved' => 'unresolved',
            'unresolved' => 'unresolved',
            'no_solucionado' => 'unresolved',
            'not_located' => 'unresolved',
            'incident' => 'unresolved',
            'cancelled' => 'cancelled',
            'anulado' => 'cancelled',
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
    @endphp

    <a class="back-link" href="{{ route('technician.dashboard') }}#partes">&larr; Partes</a>

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
            <x-nav-buttons :installation="$workOrder->installation" />
            @if ($phone)
                <a class="button secondary" href="tel:{{ $phone }}">📞 Llamar</a>
            @else
                <span class="button secondary">Sin telefono</span>
            @endif
        </div>

        @if ($workOrder->status === 'closed')
            <div class="action-grid">
                <a class="button full" href="{{ route('work-orders.pdf.download', $workOrder) }}">⬇ Descargar PDF</a>
            </div>
        @endif
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
                        'unresolved' => 'No solucionado',
                        'cancelled' => 'Anulado',
                    ] as $value => $label)
                        <option value="{{ $value }}" @selected($defaultResult === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </section>

        <details class="form-card collapsible-section" id="materials-section" {{ $materialRows->isNotEmpty() ? 'open' : '' }}>
            <summary><h2>Materiales <small>Opcional</small></h2></summary>

            <div id="material-rows">
                @forelse ($materialRows as $i => $materialRow)
                    <section class="material-card" data-material-row>
                        <button class="material-card-remove" type="button" data-remove-row aria-label="Quitar material">&times;</button>
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
                            <input class="input" name="materials[{{ $i }}][quantity]" type="number" min="0" step="0.01" value="{{ $materialRow['quantity'] ?? '1' }}">
                        </div>
                    </section>
                @empty
                    <p class="materials-empty-hint" data-materials-empty-hint>Sin materiales anadidos todavia.</p>
                @endforelse
            </div>

            <button type="button" class="add-row-button" id="add-material-row">+ Anadir material</button>
        </details>

        <section class="form-card">
            <h2>Fotos</h2>
            <div class="photo-action-grid">
                <button type="button" class="photo-action" id="open-camera-button">
                    <span class="photo-action-icon">📷</span>
                    <span>Hacer foto</span>
                </button>
                <label class="photo-action" for="photos">
                    <span class="photo-action-icon">🖼️</span>
                    <span>Elegir de galeria</span>
                </label>
            </div>
            <p class="camera-standalone-hint" id="camera-standalone-hint" hidden>
                La camara no funciona dentro de la app instalada (limitacion de iOS). Haz la foto con la Camara del iPhone y luego pulsa "Elegir de galeria" para adjuntarla.
            </p>
            <input id="photos" name="photos[]" type="file" accept="image/*" multiple class="visually-hidden-file">
            <p class="photo-picker-count" id="photo-picker-count"></p>

            <div class="camera-overlay" id="camera-overlay" hidden>
                <video id="camera-video" autoplay playsinline webkit-playsinline muted></video>
                <canvas id="camera-canvas" hidden></canvas>
                <p class="camera-error" id="camera-error" hidden></p>
                <div class="camera-controls">
                    <button type="button" class="button secondary" id="camera-close">Cerrar</button>
                    <button type="button" class="button success" id="camera-capture">Capturar</button>
                </div>
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

            <div class="field">
                <label for="customer_name">Nombre firmante</label>
                <input id="customer_name" class="input" name="customer_name" value="{{ old('customer_name', $workOrder->customer_name) }}" placeholder="Nombre y apellidos">
            </div>

            <div class="field" id="saved-signature-block" {{ $hasSignature ? '' : 'hidden' }}>
                <label>Firma guardada</label>
                <div class="saved-signature">
                    @if ($hasSignature)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($workOrder->customer_signature_path) }}" alt="Firma guardada">
                    @endif
                </div>
                <p class="job-meta">
                    @if ($workOrder->customer_name)
                        Firmado por {{ $workOrder->customer_name }}
                    @endif
                    @if ($workOrder->signed_at)
                        el {{ $workOrder->signed_at->format('d/m/Y H:i') }}
                    @endif
                </p>
                <button id="replace-signature" class="button secondary" type="button">✏️ Sustituir firma</button>
            </div>

            <div class="field signature-panel" id="signature-draw-block" {{ $hasSignature ? 'hidden' : '' }}>
                <label for="signature-pad">Firma tactil</label>
                <div class="signature-wrap">
                    <canvas id="signature-pad" class="signature-pad"></canvas>
                    <div id="signature-placeholder" class="signature-placeholder">Firma aqui</div>
                </div>
                <input id="signature_data" type="hidden" name="signature_data">
                @if (! $hasSignature)
                    <p class="job-meta">Necesaria si el resultado es Solucionado.</p>
                @endif
            </div>

            <div class="action-grid" id="signature-draw-actions" {{ $hasSignature ? 'hidden' : '' }}>
                <button id="clear-signature" class="button secondary" type="button">Limpiar firma</button>
                <span class="button secondary">Fecha automatica</span>
            </div>
        </section>

        <div class="action-grid">
            <button class="button secondary" name="action" value="save" type="submit">Guardar firma</button>
        </div>

        <div class="sticky-action-row">
            <button class="button success full" name="action" value="close" type="submit">Guardar y cerrar</button>
        </div>
    </form>

    <script>
        (() => {
            const rowsWrap = document.getElementById('material-rows');
            const addButton = document.getElementById('add-material-row');
            const materialOptions = @json($materials->map(fn ($material) => ['id' => $material->id, 'label' => $material->name.' · stock '.$material->stock_quantity]));

            if (! rowsWrap || ! addButton) {
                return;
            }

            let rowIndex = {{ $materialRows->count() }};

            const buildRow = () => {
                const i = rowIndex++;
                const optionsHtml = materialOptions
                    .map((m) => `<option value="${m.id}">${m.label}</option>`)
                    .join('');

                const row = document.createElement('section');
                row.className = 'material-card';
                row.setAttribute('data-material-row', '');
                row.innerHTML = `
                    <button class="material-card-remove" type="button" data-remove-row aria-label="Quitar material">&times;</button>
                    <div class="field">
                        <label>Material catalogo</label>
                        <select name="materials[${i}][material_id]" class="select">
                            <option value="">Material manual / sin catalogo</option>
                            ${optionsHtml}
                        </select>
                    </div>
                    <div class="field">
                        <label>Descripcion manual</label>
                        <input class="input" name="materials[${i}][description]" placeholder="Ej. Fotocelula, mando, cable">
                    </div>
                    <div class="field">
                        <label>Cantidad</label>
                        <input class="input" name="materials[${i}][quantity]" type="number" min="0" step="0.01" value="1">
                    </div>
                `;

                return row;
            };

            const hint = rowsWrap.querySelector('[data-materials-empty-hint]');

            addButton.addEventListener('click', () => {
                hint?.remove();
                rowsWrap.appendChild(buildRow());
            });

            rowsWrap.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-remove-row]');

                if (! trigger) {
                    return;
                }

                trigger.closest('[data-material-row]')?.remove();

                if (! rowsWrap.querySelector('[data-material-row]')) {
                    const empty = document.createElement('p');
                    empty.className = 'materials-empty-hint';
                    empty.setAttribute('data-materials-empty-hint', '');
                    empty.textContent = 'Sin materiales anadidos todavia.';
                    rowsWrap.appendChild(empty);
                }
            });
        })();

        (() => {
            const photoInput = document.getElementById('photos');
            const counter = document.getElementById('photo-picker-count');

            if (! photoInput || ! counter) {
                return;
            }

            const updateCounter = () => {
                const count = photoInput.files?.length ?? 0;
                counter.textContent = count > 0 ? `${count} foto${count > 1 ? 's' : ''} lista${count > 1 ? 's' : ''}` : '';
            };

            photoInput.addEventListener('change', updateCounter);

            const openCameraButton = document.getElementById('open-camera-button');
            const overlay = document.getElementById('camera-overlay');
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            const closeButton = document.getElementById('camera-close');
            const captureButton = document.getElementById('camera-capture');
            const errorBox = document.getElementById('camera-error');

            if (! openCameraButton || ! overlay || ! video || ! canvas || ! closeButton || ! captureButton) {
                return;
            }

            const isIosStandalone = window.navigator.standalone === true;

            if (isIosStandalone) {
                openCameraButton.hidden = true;
                document.getElementById('camera-standalone-hint').hidden = false;
                return;
            }

            let stream = null;

            const stopStream = () => {
                stream?.getTracks().forEach((track) => track.stop());
                stream = null;
            };

            const closeCamera = () => {
                stopStream();
                overlay.hidden = true;
                video.srcObject = null;
            };

            const addCapturedFile = (file) => {
                const dataTransfer = new DataTransfer();

                Array.from(photoInput.files || []).forEach((existing) => dataTransfer.items.add(existing));
                dataTransfer.items.add(file);
                photoInput.files = dataTransfer.files;
                updateCounter();
            };

            openCameraButton.addEventListener('click', async () => {
                overlay.hidden = false;
                errorBox.hidden = true;

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment' },
                        audio: false,
                    });
                    video.srcObject = stream;
                    await video.play().catch(() => {});
                } catch (error) {
                    errorBox.hidden = false;
                    errorBox.textContent = 'No se pudo abrir la camara. Revisa los permisos o usa "Elegir de galeria".';
                }
            });

            closeButton.addEventListener('click', closeCamera);

            captureButton.addEventListener('click', () => {
                if (! stream) {
                    return;
                }

                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                canvas.toBlob((blob) => {
                    if (! blob) {
                        return;
                    }

                    const file = new File([blob], `foto-${Date.now()}.jpg`, { type: 'image/jpeg' });
                    addCapturedFile(file);
                    closeCamera();
                }, 'image/jpeg', 0.9);
            });
        })();

        (() => {
            const canvas = document.getElementById('signature-pad');
            const input = document.getElementById('signature_data');
            const clearButton = document.getElementById('clear-signature');
            const placeholder = document.getElementById('signature-placeholder');
            const replaceButton = document.getElementById('replace-signature');
            const savedBlock = document.getElementById('saved-signature-block');
            const drawBlock = document.getElementById('signature-draw-block');
            const drawActions = document.getElementById('signature-draw-actions');

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

            replaceButton?.addEventListener('click', () => {
                if (! window.confirm('Ya hay una firma guardada. ¿Quieres sustituirla?')) {
                    return;
                }

                savedBlock.hidden = true;
                drawBlock.hidden = false;
                drawActions.hidden = false;
                configureCanvas();
            });
        })();
    </script>
</x-layouts.mobile>
