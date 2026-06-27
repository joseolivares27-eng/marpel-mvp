<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Installation;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotionSyncService
{
    /**
     * @param array<string, mixed> $payload
     */
    public function syncCustomer(array $payload): Customer
    {
        return DB::transaction(function () use ($payload): Customer {
            $customer = $this->findCustomer($payload);

            $data = [
                'legal_name' => $payload['nombre'] ?? $customer?->legal_name ?? 'Cliente Notion sin nombre',
                'tax_id' => $payload['cif'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['telefono'] ?? null,
                'phone2' => $payload['telefono2'] ?? null,
                'iban' => $payload['iban'] ?? null,
                'primary_contact_name' => $payload['persona_contacto'] ?? null,
                'fiscal_address' => $payload['direccion'] ?? null,
                'city' => $payload['localidad'] ?? null,
                'province' => $payload['provincia'] ?? null,
                'postal_code' => $payload['codigo_postal'] ?? null,
                'client_type' => $this->mapClientType($payload['tipo'] ?? null),
                'contract_start_date' => $payload['fecha_inicio'] ?? null,
                'monthly_amount' => $payload['importe_mensual'] ?? null,
                'equipment_count' => $payload['numero_equipos'] ?? null,
                'equipment_description' => $payload['descripcion_equipos'] ?? null,
                'notes' => $payload['notas'] ?? $payload['observaciones'] ?? null,
                'status' => $this->mapCustomerStatus($payload['estado'] ?? null),
                'notion_page_id' => $payload['notion_page_id'] ?? null,
            ];

            if (filled($payload['carpeta_drive'] ?? null)) {
                $data['drive_folder_url'] = $payload['carpeta_drive'];
            }

            if ($customer) {
                $customer->update($data);

                return $customer->refresh();
            }

            return Customer::create($data);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function syncNotice(array $payload): Notice
    {
        return DB::transaction(function () use ($payload): Notice {
            $existing = filled($payload['notion_page_id'] ?? null)
                ? Notice::where('notion_page_id', $payload['notion_page_id'])->first()
                : null;

            $clientName = $payload['cliente'] ?? $payload['comunidad_empresa'] ?? null;
            $address = trim((string) ($payload['direccion'] ?? ''));

            $installation = $address !== '' ? $this->findInstallationByNormalizedAddress($address) : null;

            if ($installation) {
                $customer = $installation->customer;
            } else {
                $customer = $this->findCustomerByName($clientName) ?? Customer::create([
                    'legal_name' => $clientName ?: 'Cliente Notion sin nombre',
                    'email' => $payload['email'] ?? null,
                    'phone' => $payload['telefono'] ?? null,
                    'status' => 'prospect',
                    'notes' => 'Cliente creado automaticamente desde el aviso de Notion.',
                ]);

                $installation = Installation::create([
                    'customer_id' => $customer->id,
                    'name' => ($payload['comunidad_empresa'] ?? null) ?: Str::limit('Ubicacion '.$address, 255, ''),
                    'address' => $address !== '' ? $address : 'Sin direccion indicada',
                    'contact_phone' => $payload['telefono'] ?? null,
                    'contact_email' => $payload['email'] ?? null,
                    'notes' => 'Creada automaticamente desde el aviso de Notion.',
                    'status' => 'active',
                ]);
            }

            $data = [
                'customer_id' => $customer->id,
                'installation_id' => $installation->id,
                'contact_phone' => $payload['telefono'] ?? null,
                'channel' => 'notion',
                'priority' => $this->mapNoticePriority($payload['prioridad'] ?? null),
                'description' => $this->noticeDescription($payload),
                'scheduled_at' => $payload['fecha_programada'] ?? $payload['fecha_aviso'] ?? null,
                'assigned_user_id' => $this->findTechnicianByName($payload['tecnico'] ?? null)?->id,
                'notion_page_id' => $payload['notion_page_id'] ?? null,
            ];

            if ($existing) {
                $existing->update($data);

                return $existing->refresh();
            }

            $notice = Notice::create($data);

            app(WorkOrderService::class)->createAutomaticallyFromNotice($notice);

            return $notice->refresh();
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function syncContract(array $payload): Contract
    {
        return DB::transaction(function () use ($payload): Contract {
            $customer = $this->findCustomer($payload) ?? Customer::create([
                'legal_name' => $payload['nombre'] ?? 'Cliente Notion sin nombre',
                'tax_id' => $payload['cif'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['telefono'] ?? null,
                'fiscal_address' => $payload['direccion'] ?? null,
                'city' => $payload['localidad'] ?? null,
                'province' => $payload['provincia'] ?? null,
                'postal_code' => $payload['codigo_postal'] ?? null,
                'status' => 'active',
                'notes' => 'Cliente creado automaticamente desde el contrato de Notion.',
            ]);

            $customerUpdates = array_filter([
                'drive_folder_url' => $payload['carpeta_drive'] ?? null,
                'iban' => $payload['iban'] ?? null,
                'contract_start_date' => $payload['fecha_inicio'] ?? null,
                'monthly_amount' => $payload['importe_mensual'] ?? null,
                'equipment_count' => $payload['numero_equipos'] ?? null,
                'equipment_description' => $payload['descripcion_equipos'] ?? null,
            ], fn ($value) => filled($value));

            if ($customerUpdates !== []) {
                $customer->update($customerUpdates);
            }

            $existing = filled($payload['notion_page_id'] ?? null)
                ? Contract::where('notion_page_id', $payload['notion_page_id'])->first()
                : null;

            $data = [
                'customer_id' => $customer->id,
                'number' => $payload['numero_contrato'] ?? $existing?->number ?? 'NOTION-'.Str::upper(Str::random(6)),
                'type' => 'maintenance',
                'status' => $this->mapContractStatus($payload['estado'] ?? null),
                'start_date' => $payload['fecha_inicio'] ?? now()->toDateString(),
                'billing_period' => 'monthly',
                'monthly_fee' => $payload['importe_mensual'] ?? 0,
                'notes' => $payload['observaciones'] ?? $payload['descripcion_equipos'] ?? null,
                'notion_page_id' => $payload['notion_page_id'] ?? null,
                'drive_folder_url' => $payload['carpeta_drive'] ?? null,
            ];

            if ($existing) {
                $existing->update($data);

                return $existing->refresh();
            }

            return Contract::create($data);
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function findCustomer(array $payload): ?Customer
    {
        if (filled($payload['notion_page_id'] ?? null)) {
            $byNotionId = Customer::where('notion_page_id', $payload['notion_page_id'])->first();

            if ($byNotionId) {
                return $byNotionId;
            }
        }

        if (filled($payload['cif'] ?? null)) {
            $byTaxId = Customer::where('tax_id', $payload['cif'])->first();

            if ($byTaxId) {
                return $byTaxId;
            }
        }

        return $this->findCustomerByName($payload['nombre'] ?? null);
    }

    private function findCustomerByName(?string $name): ?Customer
    {
        if (! filled($name)) {
            return null;
        }

        $normalizedName = $this->normalizeText($name);

        return Customer::query()
            ->get()
            ->first(fn (Customer $customer): bool => in_array($normalizedName, [
                $this->normalizeText($customer->legal_name),
                $this->normalizeText($customer->trade_name ?? ''),
            ], true));
    }

    private function findTechnicianByName(?string $name): ?User
    {
        if (! filled($name)) {
            return null;
        }

        $normalizedName = $this->normalizeText($name);

        return User::query()
            ->where('role', 'technician')
            ->get()
            ->first(fn (User $user): bool => $this->normalizeText($user->name) === $normalizedName);
    }

    private function findInstallationByNormalizedAddress(string $address): ?Installation
    {
        $normalizedAddress = $this->normalizeAddress($address);

        return Installation::query()
            ->with('customer')
            ->get()
            ->first(fn (Installation $installation): bool => $this->normalizeAddress($installation->address) === $normalizedAddress);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function noticeDescription(array $payload): string
    {
        $lines = array_filter([
            $payload['descripcion_averia'] ?? 'Aviso creado desde Notion.',
            '',
            filled($payload['tipo_instalacion'] ?? null) ? 'Tipo de instalacion: '.$payload['tipo_instalacion'] : null,
            filled($payload['comunidad_empresa'] ?? null) ? 'Comunidad / empresa: '.$payload['comunidad_empresa'] : null,
            filled($payload['creado_por'] ?? null) ? 'Creado por: '.$payload['creado_por'] : null,
            filled($payload['observaciones'] ?? null) ? 'Observaciones: '.$payload['observaciones'] : null,
        ], fn ($line) => $line !== null);

        return implode(PHP_EOL, $lines);
    }

    private function mapCustomerStatus(?string $estado): string
    {
        return match ($estado) {
            'Finalizado' => 'inactive',
            default => 'active',
        };
    }

    private function mapClientType(?string $tipo): ?string
    {
        return match ($tipo) {
            'Mantenimiento' => 'maintenance',
            'Reparaciones' => 'repairs',
            'Mantenimiento + Reparaciones' => 'maintenance_repairs',
            default => null,
        };
    }

    private function mapContractStatus(?string $estado): string
    {
        return match ($estado) {
            'Activo', 'Firmado' => 'active',
            default => 'paused',
        };
    }

    private function mapNoticePriority(?string $prioridad): string
    {
        return match ($prioridad) {
            'Urgente', 'Alta' => 'urgent',
            'Baja' => 'low',
            default => 'normal',
        };
    }

    private function normalizeAddress(string $address): string
    {
        return $this->normalizeText(str_replace(['º', 'ª'], ['o', 'a'], $address));
    }

    private function normalizeText(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
