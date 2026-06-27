<x-layouts.mobile heading="Firma cliente" :subheading="$workOrder->installation->name">
    <a class="back-link" href="{{ route('technician.work-orders.show', $workOrder) }}">&larr; Parte {{ $workOrder->folio_label }}</a>

    <section class="route-hero">
        <div class="badge-row">
            <span class="badge success">Firma</span>
            <span class="badge neutral">Cierre de parte</span>
        </div>
        <p class="card-kicker">Confirmacion del cliente</p>
        <h2>{{ $workOrder->installation->name }}</h2>
        <p class="job-meta">{{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Parte de trabajo' }}</p>
        <p class="problem-text">{{ $workOrder->work_performed ?: 'Trabajo pendiente de describir.' }}</p>
    </section>

    <form id="signature-form" method="post" action="{{ route('technician.work-orders.signature.store', $workOrder) }}">
        @csrf
        <section class="form-card signature-panel">
            <h2>Datos firma</h2>
            <div class="field">
                <label for="customer_name">Nombre cliente</label>
                <input id="customer_name" class="input" name="customer_name" value="{{ old('customer_name', $workOrder->customer_name) }}" placeholder="Nombre y apellidos" required>
            </div>

            <div class="field">
                <label>Firma en pantalla</label>
                <div class="signature-wrap">
                    <canvas id="signature-pad" class="signature-pad"></canvas>
                    <span id="signature-placeholder" class="signature-placeholder">Firme aqui</span>
                </div>
                <input id="signature_data" type="hidden" name="signature_data">
            </div>
        </section>

        <div class="action-grid">
            <button id="clear-signature" class="button secondary" type="button">Limpiar</button>
            <button class="button success" type="submit">Guardar firma y cerrar</button>
        </div>
    </form>

    <script>
        const canvas = document.getElementById('signature-pad');
        const form = document.getElementById('signature-form');
        const clearButton = document.getElementById('clear-signature');
        const signatureData = document.getElementById('signature_data');
        const signaturePlaceholder = document.getElementById('signature-placeholder');
        const context = canvas.getContext('2d');
        let drawing = false;
        let hasInk = false;

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();

            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.lineWidth = 3;
            context.lineCap = 'round';
            context.lineJoin = 'round';
            context.strokeStyle = '#172033';
        }

        function point(event) {
            const rect = canvas.getBoundingClientRect();
            const touch = event.touches ? event.touches[0] : event;

            return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
        }

        function start(event) {
            event.preventDefault();
            drawing = true;
            hasInk = true;
            signaturePlaceholder.hidden = true;

            const p = point(event);
            context.beginPath();
            context.moveTo(p.x, p.y);
        }

        function move(event) {
            if (! drawing) {
                return;
            }

            event.preventDefault();
            const p = point(event);
            context.lineTo(p.x, p.y);
            context.stroke();
        }

        function end() {
            drawing = false;
        }

        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        window.addEventListener('mouseup', end);
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end);

        clearButton.addEventListener('click', () => {
            context.clearRect(0, 0, canvas.width, canvas.height);
            hasInk = false;
            signaturePlaceholder.hidden = false;
        });

        form.addEventListener('submit', (event) => {
            if (! hasInk) {
                event.preventDefault();
                alert('Falta la firma.');
                return;
            }

            signatureData.value = canvas.toDataURL('image/png');
        });
    </script>
</x-layouts.mobile>
