<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Installation;
use App\Models\Notice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LucasNoticeService
{
    /**
     * @param array{
     *     nombre_cliente?: string|null,
     *     telefono: string,
     *     email?: string|null,
     *     direccion: string,
     *     descripcion: string,
     *     persona_contacto?: string|null,
     *     origen?: string|null,
     *     prioridad?: string|null,
     *     tipo_cliente?: string|null,
     *     equipo_tipo?: string|null,
     *     notas?: string|null
     * } $payload
     */
    public function createFromLucasPayload(array $payload): Notice
    {
        return DB::transaction(function () use ($payload): Notice {
            $phone = trim($payload['telefono']);
            $address = trim($payload['direccion']);
            $contactName = $this->contactName($payload);
            $installation = $this->findInstallationByNormalizedAddress($address);

            if ($installation) {
                $customer = $installation->customer;
            } else {
                $customer = $this->findCustomerByName($payload['nombre_cliente'] ?? null);

                if (! $customer) {
                    $customer = Customer::create([
                        'legal_name' => $this->customerName($payload),
                        'trade_name' => $this->customerName($payload),
                        'email' => $payload['email'] ?? null,
                        'phone' => null,
                        'primary_contact_name' => $contactName,
                        'status' => 'prospect',
                        'notes' => $this->customerNotes($payload),
                    ]);
                }

                $installation = Installation::create([
                    'customer_id' => $customer->id,
                    'name' => $this->installationName($address),
                    'address' => $address,
                    'contact_name' => $contactName,
                    'contact_phone' => $phone,
                    'contact_email' => $payload['email'] ?? null,
                    'notes' => 'Creada automaticamente desde Lucas.',
                    'status' => 'active',
                ]);
            }

            $notice = Notice::create([
                'customer_id' => $customer->id,
                'installation_id' => $installation->id,
                'reported_by' => $contactName,
                'contact_name' => $contactName,
                'contact_phone' => $phone,
                'channel' => filled($payload['origen'] ?? null) ? $payload['origen'] : 'whatsapp',
                'priority' => $this->normalizePriority($payload['prioridad'] ?? 'normal'),
                'status' => 'pending',
                'description' => $this->fullDescription($payload),
            ]);

            app(WorkOrderService::class)->createAutomaticallyFromNotice($notice);

            return $notice->refresh();
        });
    }

    private function findInstallationByNormalizedAddress(string $address): ?Installation
    {
        $normalizedAddress = $this->normalizeAddress($address);

        return Installation::query()
            ->with('customer')
            ->get()
            ->first(fn (Installation $installation): bool => $this->normalizeAddress($installation->address) === $normalizedAddress);
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

    private function installationName(string $address): string
    {
        return Str::limit('Ubicacion '.$address, 255, '');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function customerName(array $payload): string
    {
        if (filled($payload['nombre_cliente'] ?? null)) {
            return trim((string) $payload['nombre_cliente']);
        }

        if (filled($payload['persona_contacto'] ?? null)) {
            return 'Prospecto '.$payload['persona_contacto'];
        }

        return 'Prospecto '.$payload['telefono'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function contactName(array $payload): string
    {
        if (filled($payload['persona_contacto'] ?? null)) {
            return trim((string) $payload['persona_contacto']);
        }

        if (filled($payload['nombre_cliente'] ?? null)) {
            return trim((string) $payload['nombre_cliente']);
        }

        return trim((string) $payload['telefono']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function customerNotes(array $payload): ?string
    {
        $type = $payload['tipo_cliente'] ?? 'posible_cliente';

        return 'Cliente creado automaticamente desde Lucas. Tipo indicado: '.$type.'.';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function fullDescription(array $payload): string
    {
        $lines = [
            trim((string) $payload['descripcion']),
            '',
            'Origen: '.(filled($payload['origen'] ?? null) ? $payload['origen'] : 'whatsapp'),
            'Telefono: '.$payload['telefono'],
            'Direccion: '.$payload['direccion'],
        ];

        if (! empty($payload['persona_contacto'])) {
            $lines[] = 'Persona contacto: '.$payload['persona_contacto'];
        }

        if (! empty($payload['nombre_cliente'])) {
            $lines[] = 'Nombre cliente indicado: '.$payload['nombre_cliente'];
        }

        if (! empty($payload['email'])) {
            $lines[] = 'Email: '.$payload['email'];
        }

        if (! empty($payload['tipo_cliente'])) {
            $lines[] = 'Tipo cliente indicado: '.$payload['tipo_cliente'];
        }

        if (! empty($payload['equipo_tipo'])) {
            $lines[] = 'Tipo de equipo indicado: '.$payload['equipo_tipo'];
        }

        if (! empty($payload['notas'])) {
            $lines[] = 'Notas: '.$payload['notas'];
        }

        return implode(PHP_EOL, $lines);
    }

    private function normalizeAddress(string $address): string
    {
        return $this->normalizeText(str_replace(['º', 'ª'], ['o', 'a'], $address));
    }

    private function normalizeText(string $value): string
    {
        $value = Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        return $value;
    }

    private function normalizePriority(string $priority): string
    {
        return match ($priority) {
            'baja', 'low' => 'low',
            'alta', 'urgente', 'urgent' => 'urgent',
            default => 'normal',
        };
    }
}
