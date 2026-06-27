<x-layouts.mobile heading="Nuevo aviso" :subheading="auth()->user()->name">
    <a class="back-link" href="{{ route('technician.dashboard') }}">&larr; Ruta de hoy</a>

    <form method="post" action="{{ route('technician.notices.store') }}">
        @csrf

        <section class="form-card">
            <h2>Cliente e instalacion</h2>

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
                <label for="equipment_id">Equipo <small>(opcional)</small></label>
                <select id="equipment_id" name="equipment_id" class="select" disabled>
                    <option value="">Sin equipo concreto</option>
                </select>
            </div>
        </section>

        <section class="form-card">
            <h2>Aviso</h2>

            <div class="field">
                <label for="priority">Prioridad</label>
                <select id="priority" name="priority" class="select" required>
                    <option value="low" @selected(old('priority') === 'low')>Baja</option>
                    <option value="normal" @selected(old('priority', 'normal') === 'normal')>Normal</option>
                    <option value="urgent" @selected(old('priority') === 'urgent')>Urgente</option>
                </select>
            </div>

            <div class="field">
                <label for="description">Descripcion del problema</label>
                <textarea id="description" name="description" class="textarea" required placeholder="Ej. La puerta del garaje no abre con el mando.">{{ old('description') }}</textarea>
            </div>

            <div class="field">
                <label for="contact_name">Contacto en la instalacion <small>(opcional)</small></label>
                <input id="contact_name" name="contact_name" class="input" value="{{ old('contact_name') }}" placeholder="Nombre de quien avisa">
            </div>

            <div class="field">
                <label for="contact_phone">Telefono contacto <small>(opcional)</small></label>
                <input id="contact_phone" name="contact_phone" class="input" type="tel" value="{{ old('contact_phone') }}">
            </div>
        </section>

        <div class="sticky-action-row">
            <button class="button success full" type="submit">Crear aviso</button>
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

                equipmentSelect.innerHTML = '<option value="">Sin equipo concreto</option>'
                    + options.map((item) => `<option value="${item.id}">${item.name}</option>`).join('');
                equipmentSelect.disabled = options.length === 0;

                if (oldEquipment) {
                    equipmentSelect.value = oldEquipment;
                }
            };

            customerSelect.addEventListener('change', () => {
                fillInstallations(customerSelect.value);
                equipmentSelect.innerHTML = '<option value="">Sin equipo concreto</option>';
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
