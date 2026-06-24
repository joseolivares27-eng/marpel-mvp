<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Equipment;
use App\Models\Installation;
use App\Models\Notice;
use App\Models\Quote;
use App\Models\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class OperationalContextValidator
{
    public function validate(Model $model): void
    {
        $customerId = $model->getAttribute('customer_id');
        $installationId = $model->getAttribute('installation_id');
        $equipmentId = $model->getAttribute('equipment_id');
        $contractId = $model->getAttribute('contract_id');
        $noticeId = $model->getAttribute('notice_id');
        $reviewId = $model->getAttribute('review_id');
        $quoteId = $model->getAttribute('quote_id');

        if ($customerId && $installationId) {
            $installation = Installation::query()->find($installationId);

            if ($installation && (int) $installation->customer_id !== (int) $customerId) {
                Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_INSTALLATION_CUSTOMER', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'customer_id' => $customerId,
                    'installation_id' => $installationId,
                    'installation_customer_id' => $installation->customer_id,
                ]);

                throw ValidationException::withMessages([
                    'installation_id' => 'La instalacion seleccionada no pertenece al cliente indicado.',
                ]);
            }
        }

        if ($equipmentId) {
            $equipment = Equipment::query()->with('installation')->find($equipmentId);

            if (! $equipment) {
                return;
            }

            if ($installationId && (int) $equipment->installation_id !== (int) $installationId) {
                Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_EQUIPMENT_INSTALLATION', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'installation_id' => $installationId,
                    'equipment_id' => $equipmentId,
                    'equipment_installation_id' => $equipment->installation_id,
                ]);

                throw ValidationException::withMessages([
                    'equipment_id' => 'El equipo seleccionado no pertenece a la instalacion indicada.',
                ]);
            }

            if ($customerId && (int) $equipment->installation->customer_id !== (int) $customerId) {
                Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_EQUIPMENT_CUSTOMER', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'customer_id' => $customerId,
                    'equipment_id' => $equipmentId,
                    'equipment_customer_id' => $equipment->installation->customer_id,
                ]);

                throw ValidationException::withMessages([
                    'equipment_id' => 'El equipo seleccionado no pertenece al cliente indicado.',
                ]);
            }
        }

        if ($contractId) {
            $contract = Contract::query()->find($contractId);

            if (! $contract) {
                return;
            }

            if ($customerId && (int) $contract->customer_id !== (int) $customerId) {
                Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_CONTRACT_CUSTOMER', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'customer_id' => $customerId,
                    'contract_id' => $contractId,
                    'contract_customer_id' => $contract->customer_id,
                ]);

                throw ValidationException::withMessages([
                    'contract_id' => 'El contrato seleccionado no pertenece al cliente indicado.',
                ]);
            }

            if ($installationId && $contract->installation_id && (int) $contract->installation_id !== (int) $installationId) {
                Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_CONTRACT_INSTALLATION', [
                    'model' => $model::class,
                    'model_id' => $model->getKey(),
                    'installation_id' => $installationId,
                    'contract_id' => $contractId,
                    'contract_installation_id' => $contract->installation_id,
                ]);

                throw ValidationException::withMessages([
                    'contract_id' => 'El contrato seleccionado no cubre la instalacion indicada.',
                ]);
            }
        }

        if ($noticeId && $notice = Notice::query()->find($noticeId)) {
            $this->assertOriginMatches($notice, $customerId, $installationId, $equipmentId, 'aviso');
        }

        if ($reviewId && $review = Review::query()->find($reviewId)) {
            $this->assertOriginMatches($review, $customerId, $installationId, $equipmentId, 'revision');
        }

        if ($quoteId && $quote = Quote::query()->find($quoteId)) {
            $this->assertOriginMatches($quote, $customerId, $installationId, $equipmentId, 'presupuesto');
        }
    }

    private function assertOriginMatches(Model $origin, ?int $customerId, ?int $installationId, ?int $equipmentId, string $label): void
    {
        if ($customerId && (int) $origin->getAttribute('customer_id') !== (int) $customerId) {
            Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_ORIGIN_CUSTOMER', [
                'origin' => $origin::class,
                'origin_id' => $origin->getKey(),
                'label' => $label,
                'customer_id' => $customerId,
                'origin_customer_id' => $origin->getAttribute('customer_id'),
            ]);

            throw ValidationException::withMessages([
                "{$label}_id" => "El {$label} de origen no pertenece al cliente indicado.",
            ]);
        }

        if ($installationId && (int) $origin->getAttribute('installation_id') !== (int) $installationId) {
            Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_ORIGIN_INSTALLATION', [
                'origin' => $origin::class,
                'origin_id' => $origin->getKey(),
                'label' => $label,
                'installation_id' => $installationId,
                'origin_installation_id' => $origin->getAttribute('installation_id'),
            ]);

            throw ValidationException::withMessages([
                "{$label}_id" => "El {$label} de origen no pertenece a la instalacion indicada.",
            ]);
        }

        $originEquipmentId = $origin->getAttribute('equipment_id');

        if ($equipmentId && $originEquipmentId && (int) $originEquipmentId !== (int) $equipmentId) {
            Log::warning('MARPEL_OPERATIONAL_CONTEXT_INVALID_ORIGIN_EQUIPMENT', [
                'origin' => $origin::class,
                'origin_id' => $origin->getKey(),
                'label' => $label,
                'equipment_id' => $equipmentId,
                'origin_equipment_id' => $originEquipmentId,
            ]);

            throw ValidationException::withMessages([
                "{$label}_id" => "El {$label} de origen no pertenece al equipo indicado.",
            ]);
        }
    }
}
