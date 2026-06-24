<x-layouts.mobile heading="Firma cliente" :subheading="$workOrder->installation->name">
    <section class="job-card">
        <h2>{{ $workOrder->installation->name }}</h2>
        <p class="job-meta">{{ $workOrder->equipment?->code }} {{ $workOrder->equipment?->name ?? 'Parte de trabajo' }}</p>
        <p>{{ $workOrder->work_performed ?: 'Trabajo pendiente de describir.' }}</p>
    </section>

    <form id="signature-form" method="post" action="{{ route('technician.work-orders.signature.store', $workOrder) }}">
        @csrf
        <div class="field">
            <label for="customer_name">Nombre cliente</label>
            <input id="customer_name" class="input" name="customer_name" value="{{ old('customer_name', $workOrder->customer_name) }}" required>
        </div>

        <div class="field">
            <label>Firma</label>
            <canvas id="signature-pad" class="signature-pad"></canvas>
            <input id="signature_data" type="hidden" name="signature_data">
        </div>

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
        const context = canvas.getContext('2d');
        let drawing = false;
        let hasInk = false;

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const rect = canvas.getBoundingClientRect();
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            context.scale(ratio, ratio);
            context.lineWidth = 3;
            context.lineCap = 'round';
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
            const p = point(event);
            context.beginPath();
            context.moveTo(p.x, p.y);
        }

        function move(event) {
            if (!drawing) return;
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
        });

        form.addEventListener('submit', (event) => {
            if (!hasInk) {
                event.preventDefault();
                alert('Falta la firma.');
                return;
            }

            signatureData.value = canvas.toDataURL('image/png');
        });
    </script>
</x-layouts.mobile>
