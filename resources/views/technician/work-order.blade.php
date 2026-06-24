<x-layouts.mobile :heading="'Parte #'.$workOrder->id" :subheading="$workOrder->installation->name">
    <section class="job-card">
        <div class="badge-row">
            <span class="badge">{{ $workOrder->status }}</span>
            @if ($workOrder->customer_signature_path)
                <span class="badge success">Firmado</span>
            @endif
        </div>
        <h2>{{ $workOrder->installation->name }}</h2>
        <p class="job-meta">{{ $workOrder->customer->legal_name }} · {{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Sin equipo concreto' }}</p>
        @if ($workOrder->notice)
            <p>{{ $workOrder->notice->description }}</p>
        @endif
    </section>

    <form method="post" action="{{ route('technician.work-orders.update', $workOrder) }}" enctype="multipart/form-data">
        @csrf

        <div class="field">
            <label for="work_performed">Trabajo realizado</label>
            <textarea id="work_performed" name="work_performed" class="textarea" data-draft-key="work-order-{{ $workOrder->id }}-work">{{ old('work_performed', $workOrder->work_performed) }}</textarea>
        </div>

        <div class="field">
            <label for="observations">Observaciones</label>
            <textarea id="observations" name="observations" class="textarea" data-draft-key="work-order-{{ $workOrder->id }}-obs">{{ old('observations', $workOrder->observations) }}</textarea>
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
                    <option value="{{ $value }}" @selected(old('result', $workOrder->result ?: 'solved') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <h2 class="section-title">Materiales</h2>
        @for ($i = 0; $i < 3; $i++)
            <section class="job-card">
                <div class="field">
                    <label for="material_{{ $i }}">Material catalogo</label>
                    <select id="material_{{ $i }}" name="materials[{{ $i }}][material_id]" class="select">
                        <option value="">Sin catalogo</option>
                        @foreach ($materials as $material)
                            <option value="{{ $material->id }}">{{ $material->name }} · stock {{ $material->stock_quantity }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label>Descripcion manual</label>
                    <input class="input" name="materials[{{ $i }}][description]" placeholder="Ej. Fotocelula">
                </div>
                <div class="field">
                    <label>Cantidad</label>
                    <input class="input" name="materials[{{ $i }}][quantity]" type="number" min="0" step="0.01" value="{{ $i === 0 ? '1' : '' }}">
                </div>
            </section>
        @endfor

        <div class="field">
            <label for="photos">Fotografias</label>
            <input id="photos" class="input" name="photos[]" type="file" accept="image/*" capture="environment" multiple>
        </div>

        @if ($workOrder->photos->isNotEmpty())
            <h2 class="section-title">Fotos subidas</h2>
            <div class="quick-grid">
                @foreach ($workOrder->photos as $photo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" alt="Foto parte" style="width:100%;border-radius:8px;border:1px solid #d7deea">
                @endforeach
            </div>
        @endif

        <div class="action-grid">
            <a class="button secondary" href="{{ route('technician.work-orders.signature', $workOrder) }}">Firma</a>
            <button class="button secondary" name="action" value="save" type="submit">Guardar</button>
        </div>
        <button class="button success full" style="margin-top:10px" name="action" value="close" type="submit">Cerrar parte</button>
    </form>
</x-layouts.mobile>
