<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Installation;
use Illuminate\Console\Command;

class ImportClientsFromJson extends Command
{
    protected $signature = 'clients:import-json {path}';

    protected $description = 'Importa clientes e instalaciones desde un JSON generado a partir de un Excel de clientes';

    public function handle(): int
    {
        $path = $this->argument('path');
        $data = json_decode(file_get_contents($path), true);

        foreach ($data as $row) {
            $customer = null;

            if (! empty($row['tax_id'])) {
                $customer = Customer::where('tax_id', $row['tax_id'])->first();
            }
            if (! $customer) {
                $customer = Customer::where('legal_name', $row['legal_name'])->first();
            }

            $attributes = [
                'legal_name' => $row['legal_name'],
                'trade_name' => $row['trade_name'],
                'tax_id' => $row['tax_id'],
                'fiscal_address' => $row['fiscal_address'],
                'city' => $row['city'],
                'province' => $row['province'],
                'postal_code' => $row['postal_code'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'primary_contact_name' => $row['primary_contact_name'],
                'notes' => $row['notes'],
            ];

            if ($customer) {
                $customer->fill($attributes);
                $customer->save();
                $this->line("Actualizado: {$customer->legal_name}");
            } else {
                $attributes['status'] = $row['status'] ?? 'active';
                $customer = Customer::create($attributes);
                $this->line("Creado: {$customer->legal_name}");
            }

            foreach ($row['installations'] as $inst) {
                $installation = Installation::where('customer_id', $customer->id)
                    ->where('name', $inst['name'])
                    ->first();

                $instAttributes = [
                    'customer_id' => $customer->id,
                    'name' => $inst['name'],
                    'address' => $inst['address'],
                    'city' => $inst['city'],
                    'province' => $inst['province'],
                    'postal_code' => $inst['postal_code'],
                    'contact_name' => $inst['contact_name'],
                    'contact_phone' => $inst['contact_phone'],
                    'contact_email' => $inst['contact_email'],
                    'notes' => $inst['notes'],
                ];

                if ($installation) {
                    $installation->fill($instAttributes);
                    $installation->save();
                } else {
                    $instAttributes['status'] = 'active';
                    Installation::create($instAttributes);
                }
            }
        }

        $this->info('Importacion completada.');

        return self::SUCCESS;
    }
}
