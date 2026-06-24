<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Equipment;
use App\Models\EquipmentType;
use App\Models\Installation;
use App\Models\IntegrationSource;
use App\Models\Material;
use App\Models\Notice;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@marpel.local'],
            [
                'name' => 'Administracion Marpel',
                'phone' => '950000001',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ],
        );

        $technician = User::updateOrCreate(
            ['email' => 'tecnico@marpel.local'],
            [
                'name' => 'Juan Tecnico',
                'phone' => '950000002',
                'password' => Hash::make('password'),
                'role' => 'technician',
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'gerencia@marpel.local'],
            [
                'name' => 'Gerencia Marpel',
                'phone' => '950000003',
                'password' => Hash::make('password'),
                'role' => 'management',
                'is_active' => true,
            ],
        );

        $customer = Customer::updateOrCreate(
            ['tax_id' => 'B00000000'],
            [
                'legal_name' => 'Administrador Fincas XYZ',
                'trade_name' => 'Fincas XYZ',
                'fiscal_address' => 'Calle Mayor 1, Almeria',
                'email' => 'contacto@fincasxyz.local',
                'phone' => '950100100',
                'primary_contact_name' => 'Maria Lopez',
                'status' => 'active',
            ],
        );

        collect([
            ['name' => 'Puerta automatica', 'category' => 'Puertas', 'icon' => 'heroicon-o-arrow-right-circle', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Puerta corredera', 'category' => 'Puertas', 'icon' => 'heroicon-o-arrows-right-left', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Puerta basculante', 'category' => 'Puertas', 'icon' => 'heroicon-o-arrow-up-circle', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Puerta seccional', 'category' => 'Puertas', 'icon' => 'heroicon-o-queue-list', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Puerta peatonal', 'category' => 'Puertas', 'icon' => 'heroicon-o-user', 'default_revision_periodicity' => 'annual', 'default_revision_interval_days' => 365],
            ['name' => 'Puerta rapida', 'category' => 'Puertas', 'icon' => 'heroicon-o-bolt', 'default_revision_periodicity' => 'quarterly', 'default_revision_interval_days' => 90],
            ['name' => 'Barrera', 'category' => 'Accesos', 'icon' => 'heroicon-o-minus', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Grupo de presion', 'category' => 'Hidraulica', 'icon' => 'heroicon-o-beaker', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Bomba de achique', 'category' => 'Hidraulica', 'icon' => 'heroicon-o-arrow-down-tray', 'default_revision_periodicity' => 'quarterly', 'default_revision_interval_days' => 90],
            ['name' => 'Central de CO', 'category' => 'Seguridad', 'icon' => 'heroicon-o-shield-check', 'default_revision_periodicity' => 'semiannual', 'default_revision_interval_days' => 180],
            ['name' => 'Grupo electrogeno', 'category' => 'Energia', 'icon' => 'heroicon-o-bolt', 'default_revision_periodicity' => 'quarterly', 'default_revision_interval_days' => 90],
            ['name' => 'Control de accesos', 'category' => 'Accesos', 'icon' => 'heroicon-o-key', 'default_revision_periodicity' => 'annual', 'default_revision_interval_days' => 365],
            ['name' => 'Videoportero', 'category' => 'Comunicaciones', 'icon' => 'heroicon-o-video-camera', 'default_revision_periodicity' => 'annual', 'default_revision_interval_days' => 365],
            ['name' => 'Personalizado', 'category' => 'Personalizado', 'icon' => 'heroicon-o-plus-circle', 'default_revision_periodicity' => 'custom', 'default_revision_interval_days' => 180, 'default_custom_revision_interval_days' => 180],
        ])->each(fn (array $type) => EquipmentType::updateOrCreate(
            ['name' => $type['name']],
            $type + [
                'is_active' => true,
                'description' => 'Tipo de equipo administrable desde panel.',
            ],
        ));

        $doorType = EquipmentType::where('name', 'Puerta automatica')->firstOrFail();
        $barrierType = EquipmentType::where('name', 'Barrera')->firstOrFail();

        $santaTeresa = Installation::updateOrCreate(
            ['customer_id' => $customer->id, 'name' => 'Comunidad Santa Teresa'],
            [
                'address' => 'Calle Santa Teresa 12',
                'city' => 'Almeria',
                'province' => 'Almeria',
                'postal_code' => '04001',
                'contact_name' => 'Presidente comunidad',
                'contact_phone' => '600100100',
                'access_hours' => '08:00-20:00',
                'access_instructions' => 'Entrada por rampa lateral. Llaves en porteria.',
                'status' => 'active',
            ],
        );

        $huerta = Installation::updateOrCreate(
            ['customer_id' => $customer->id, 'name' => 'Comunidad Huerta del Obispo'],
            [
                'address' => 'Avenida del Mediterraneo 45',
                'city' => 'Almeria',
                'province' => 'Almeria',
                'postal_code' => '04007',
                'contact_name' => 'Administrador',
                'contact_phone' => '600200200',
                'access_instructions' => 'Avisar antes de llegar. Cuarto de maquinas en sotano -1.',
                'status' => 'active',
            ],
        );

        $door = Equipment::updateOrCreate(
            ['installation_id' => $santaTeresa->id, 'name' => 'Puerta garaje principal'],
            [
                'equipment_type_id' => $doorType->id,
                'name' => 'Puerta garaje principal',
                'category' => 'Puertas',
                'brand' => 'Nice',
                'model' => 'Robus',
                'serial_number' => 'NICE-ST-001',
                'internal_location' => 'Garaje planta -1',
                'last_review_at' => now()->subMonths(5)->toDateString(),
                'next_review_at' => now()->addMonth()->toDateString(),
                'revision_periodicity' => 'semiannual',
                'revision_interval_days' => 180,
                'status' => 'active',
            ],
        );

        $barrier = Equipment::updateOrCreate(
            ['installation_id' => $huerta->id, 'name' => 'Barrera acceso parking'],
            [
                'equipment_type_id' => $barrierType->id,
                'name' => 'Barrera acceso parking',
                'category' => 'Accesos',
                'brand' => 'CAME',
                'model' => 'GARD',
                'serial_number' => 'CAME-HO-001',
                'internal_location' => 'Entrada parking',
                'last_review_at' => now()->subMonths(6)->toDateString(),
                'next_review_at' => now()->addDays(7)->toDateString(),
                'revision_periodicity' => 'semiannual',
                'revision_interval_days' => 180,
                'status' => 'active',
            ],
        );

        Material::upsert([
            ['sku' => 'MAT-FOTO-001', 'name' => 'Fotocelula seguridad', 'unit' => 'ud', 'cost_price' => 18, 'sale_price' => 35, 'stock_quantity' => 12, 'minimum_stock' => 3, 'is_active' => true],
            ['sku' => 'MAT-MANDO-001', 'name' => 'Mando garaje rolling code', 'unit' => 'ud', 'cost_price' => 12, 'sale_price' => 28, 'stock_quantity' => 25, 'minimum_stock' => 5, 'is_active' => true],
            ['sku' => 'MAT-COND-001', 'name' => 'Condensador motor', 'unit' => 'ud', 'cost_price' => 7, 'sale_price' => 18, 'stock_quantity' => 8, 'minimum_stock' => 2, 'is_active' => true],
        ], ['sku'], ['name', 'unit', 'cost_price', 'sale_price', 'stock_quantity', 'minimum_stock', 'is_active']);

        $contract = Contract::updateOrCreate(
            ['number' => 'CTR-2026-0001'],
            [
                'customer_id' => $customer->id,
                'installation_id' => $santaTeresa->id,
                'type' => 'maintenance',
                'status' => 'active',
                'start_date' => now()->startOfYear()->toDateString(),
                'billing_period' => 'monthly',
                'monthly_fee' => 95,
                'coverages' => ['preventive_maintenance', 'emergency_service', 'priority_support'],
                'includes_emergency_service' => true,
                'includes_preventive_maintenance' => true,
                'notes' => 'Contrato base para mantenimiento preventivo y avisos urgentes.',
            ],
        );

        $contract->lines()->updateOrCreate(
            ['description' => 'Mantenimiento puerta garaje principal'],
            [
                'equipment_type_id' => $doorType->id,
                'quantity' => 1,
                'unit_price' => 95,
                'revision_interval_days' => 180,
            ],
        );

        Notice::updateOrCreate(
            ['installation_id' => $santaTeresa->id, 'description' => 'La puerta no abre con mandos. Cliente esperando.'],
            [
                'customer_id' => $customer->id,
                'equipment_id' => $door->id,
                'contract_id' => $contract->id,
                'reported_by' => 'Maria Lopez',
                'contact_name' => 'Presidente comunidad',
                'contact_phone' => '600100100',
                'channel' => 'phone',
                'priority' => 'urgent',
                'status' => 'assigned',
                'assigned_user_id' => $technician->id,
                'scheduled_at' => now()->addMinutes(30),
            ],
        );

        Review::updateOrCreate(
            ['equipment_id' => $barrier->id, 'scheduled_at' => now()->addHours(2)->startOfHour()],
            [
                'customer_id' => $customer->id,
                'installation_id' => $huerta->id,
                'assigned_user_id' => $technician->id,
                'type' => 'preventive',
                'status' => 'scheduled',
            ],
        );

        collect([
            ['name' => 'WhatsApp', 'slug' => 'whatsapp', 'type' => 'messaging'],
            ['name' => 'Telegram', 'slug' => 'telegram', 'type' => 'messaging'],
            ['name' => 'Lucas', 'slug' => 'lucas', 'type' => 'assistant'],
            ['name' => 'Jarvis', 'slug' => 'jarvis', 'type' => 'assistant'],
            ['name' => 'Formularios web', 'slug' => 'web-forms', 'type' => 'form'],
            ['name' => 'Correo electronico', 'slug' => 'email', 'type' => 'email'],
            ['name' => 'API externa', 'slug' => 'external-api', 'type' => 'api'],
        ])->each(fn (array $source) => IntegrationSource::updateOrCreate(
            ['slug' => $source['slug']],
            $source + [
                'is_active' => false,
                'notes' => 'Canal preparado para automatizaciones futuras.',
            ],
        ));
    }
}
