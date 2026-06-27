<x-layouts.mobile heading="Nueva revision" :subheading="auth()->user()->name">
    <a class="back-link" href="{{ route('technician.dashboard') }}">&larr; Ruta de hoy</a>

    <form method="post" action="{{ route('technician.reviews.store') }}">
        @csrf

        <section class="form-card">
            <h2>Cliente, instalacion y equipo</h2>

            <div class="field">
                <label for="customer_id">Cliente</label>
                <select id="customer_id" name="customer_id" class="select" required>
                    <option value="">Selecciona un cliente</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->legal_name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="installation_id">Instalacion</label>
                <select id="installation_id" name="installation_id" class="select" required disabled>
                    <option value="">Elige primero un cliente</option>
                </select>
            </div>

            <div class="field">
                <label for="equipment_id">Equipo</label>
                <select id="equipment_id" name="equipment_id" class="select" required disabled>
                    <option value="">Elige primero una instalacion</option>
                </select>
            </div>
        </section>

        <section class="form-card">
            <h2>Revision</h2>

            <div class="field">
                <label for="type">Tipo</label>
                <select id="type" name="type" class="select" required>
                    <option value="corrective" @selected(old('type', 'corrective') === 'corrective')>Correctiva</option>
                    <option value="preventive" @selected(old('type') === 'preventive')>Preventiva</option>
                </select>
            </div>

            <div class="field">
                <label for="notes">Observaciones <small>(opcional)</small></label>
                <textarea id="notes" name="notes" class="textarea" placeholder="Ej. Revision tras aviso de ruido en el motor.">{{ old('notes') }}</textarea>
            </div>
        </section>

        <div class="sticky-action-row">
            <button class="button success full" type="submit">Crear revision</button>
        </div>
    </form>

    <script>
        (() => {
            const installations = {!! $installationsJson !!};
            const equipment = {!! $equipmentJson !!};

            const customerSelect = document.getElementById('customer_id');
            const installationSelect = document.getElementById('installation_id');
            const equipmentSelect = document.getElementById('equipment_id');

            const oldInstallation = '{{ old('installation_id') }}';
            const oldEquipment = '{{ old('equipment_id') }}';

            const fillInstallations = (customerId) => {
                const options = installations.filter((item) => String(item.customer_id) === String(customerId));

                installationSelect.innerHTML = '<option value="">Selecciona una instalacion</option>'
                    + options.map((item) => `<option value="${item.id}">${item.name}</option>`).join('');
                installationSelect.disabled = options.length === 0;

                if (oldInstallation) {
                    installationSelect.value = oldInstallation;
                }
            };

            const fillEquipment = (installationId) => {
                const options = equipment.filter((item) => String(item.installation_id) === String(installationId));

                equipmentSelect.innerHTML = '<option value="">Selecciona un equipo</option>'
                    + options.map((item) => `<option value="${item.id}">${item.name}</option>`).join('');
                equipmentSelect.disabled = options.length === 0;

                if (oldEquipment) {
                    equipmentSelect.value = oldEquipment;
                }
            };

            customerSelect.addEventListener('change', () => {
                fillInstallations(customerSelect.value);
                equipmentSelect.innerHTML = '<option value="">Elige primero una instalacion</option>';
                equipmentSelect.disabled = true;
            });

            installationSelect.addEventListener('change', () => fillEquipment(installationSelect.value));

            if (customerSelect.value) {
                fillInstallations(customerSelect.value);
            }

            if (installationSelect.value) {
                fillEquipment(installationSelect.value);
            }
        })();
    </script>
</x-layouts.mobile>
