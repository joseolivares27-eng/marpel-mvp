<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotionSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class NotionApiController extends Controller
{
    public function clientes(Request $request, NotionSyncService $service): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return $this->unauthorized();
        }

        $payload = $this->parseNotionPayload($request->all(), [
            'Nombre' => 'nombre',
            'CIF' => 'cif',
            'Email' => 'email',
            'Teléfono' => 'telefono',
            'Teléfono 2' => 'telefono2',
            'IBAN' => 'iban',
            'Persona de contacto' => 'persona_contacto',
            'Dirección' => 'direccion',
            'Localidad' => 'localidad',
            'Provincia' => 'provincia',
            'Código Postal' => 'codigo_postal',
            'Tipo' => 'tipo',
            'Fecha Inicio ' => 'fecha_inicio',
            'Importe mensual' => 'importe_mensual',
            'Nº equipos' => 'numero_equipos',
            'Descripción equipos' => 'descripcion_equipos',
            'Notas' => 'notas',
            'Observaciones' => 'observaciones',
            'Estado' => 'estado',
            'Carpeta Drive' => 'carpeta_drive',
        ]);

        $validator = Validator::make($payload, [
            'notion_page_id' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'cif' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'telefono2' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'persona_contacto' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'localidad' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'fecha_inicio' => ['nullable', 'date'],
            'importe_mensual' => ['nullable', 'numeric'],
            'numero_equipos' => ['nullable', 'numeric'],
            'descripcion_equipos' => ['nullable', 'string', 'max:5000'],
            'notas' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'estado' => ['nullable', 'string', 'max:50'],
            'carpeta_drive' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->invalid($validator->errors());
        }

        try {
            $customer = $service->syncCustomer($validator->validated());
        } catch (Throwable $exception) {
            return $this->failed($exception);
        }

        return response()->json([
            'success' => true,
            'cliente_id' => $customer->id,
            'mensaje' => 'Cliente sincronizado correctamente desde Notion.',
        ], 201);
    }

    public function avisos(Request $request, NotionSyncService $service): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return $this->unauthorized();
        }

        $payload = $this->parseNotionPayload($request->all(), [
            'Cliente' => 'cliente',
            'Comunidad / Empresa' => 'comunidad_empresa',
            'Dirección' => 'direccion',
            'Teléfono' => 'telefono',
            'Email' => 'email',
            'Tipo de instalación' => 'tipo_instalacion',
            'Prioridad' => 'prioridad',
            'Descripción de la avería' => 'descripcion_averia',
            'Observaciones' => 'observaciones',
            'Creado por' => 'creado_por',
            'Tecnico' => 'tecnico',
            'Fecha de aviso' => 'fecha_aviso',
            'Fecha programada' => 'fecha_programada',
        ]);

        $validator = Validator::make($payload, [
            'notion_page_id' => ['nullable', 'string', 'max:255'],
            'cliente' => ['nullable', 'string', 'max:255'],
            'comunidad_empresa' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tipo_instalacion' => ['nullable', 'string', 'max:255'],
            'prioridad' => ['nullable', 'string', 'max:50'],
            'descripcion_averia' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'creado_por' => ['nullable', 'string', 'max:255'],
            'tecnico' => ['nullable', 'string', 'max:255'],
            'fecha_aviso' => ['nullable', 'date'],
            'fecha_programada' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->invalid($validator->errors());
        }

        try {
            $notice = $service->syncNotice($validator->validated());
        } catch (Throwable $exception) {
            return $this->failed($exception);
        }

        return response()->json([
            'success' => true,
            'aviso_id' => $notice->id,
            'cliente_id' => $notice->customer_id,
            'instalacion_id' => $notice->installation_id,
            'mensaje' => 'Aviso sincronizado correctamente desde Notion.',
        ], 201);
    }

    public function contratos(Request $request, NotionSyncService $service): JsonResponse
    {
        if (! $this->tokenIsValid($request)) {
            return $this->unauthorized();
        }

        $payload = $this->parseNotionPayload($request->all(), [
            'Nombre' => 'nombre',
            'CIF' => 'cif',
            'Email' => 'email',
            'Teléfono' => 'telefono',
            'IBAN' => 'iban',
            'Dirección' => 'direccion',
            'Localidad' => 'localidad',
            'Provincia' => 'provincia',
            'Código Postal' => 'codigo_postal',
            'Fecha inicio' => 'fecha_inicio',
            'Nº contrato' => 'numero_contrato',
            'Nº equipos' => 'numero_equipos',
            'Descripción equipos' => 'descripcion_equipos',
            'Observaciones' => 'observaciones',
            'Importe mensual' => 'importe_mensual',
            'Estado' => 'estado',
            'Carpeta Drive' => 'carpeta_drive',
        ]);

        $validator = Validator::make($payload, [
            'notion_page_id' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'cif' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'iban' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'localidad' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'fecha_inicio' => ['nullable', 'date'],
            'numero_contrato' => ['nullable', 'string', 'max:100'],
            'numero_equipos' => ['nullable', 'numeric'],
            'descripcion_equipos' => ['nullable', 'string', 'max:5000'],
            'observaciones' => ['nullable', 'string', 'max:5000'],
            'importe_mensual' => ['nullable', 'numeric'],
            'estado' => ['nullable', 'string', 'max:50'],
            'carpeta_drive' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->invalid($validator->errors());
        }

        try {
            $contract = $service->syncContract($validator->validated());
        } catch (Throwable $exception) {
            return $this->failed($exception);
        }

        return response()->json([
            'success' => true,
            'contrato_id' => $contract->id,
            'cliente_id' => $contract->customer_id,
            'mensaje' => 'Contrato sincronizado correctamente desde Notion.',
        ], 201);
    }

    /**
     * Accepts either a flat payload (custom webhook body) or Notion's native
     * automation event payload (full page object under data.properties) and
     * always returns a flat array using our own field names.
     *
     * @param array<string, mixed> $body
     * @param array<string, string> $propertyMap Notion property name => our field name
     * @return array<string, mixed>
     */
    private function parseNotionPayload(array $body, array $propertyMap): array
    {
        $properties = $body['data']['properties'] ?? null;

        if (! is_array($properties)) {
            return $body;
        }

        $payload = [
            'notion_page_id' => $body['data']['id'] ?? null,
        ];

        foreach ($propertyMap as $notionName => $ourKey) {
            if (isset($properties[$notionName])) {
                $payload[$ourKey] = $this->extractPropertyValue($properties[$notionName]);
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $property
     */
    private function extractPropertyValue(array $property): mixed
    {
        return match ($property['type'] ?? null) {
            'title' => $this->joinRichText($property['title'] ?? []),
            'rich_text' => $this->joinRichText($property['rich_text'] ?? []),
            'email' => $property['email'] ?? null,
            'phone_number' => $property['phone_number'] ?? null,
            'url' => $property['url'] ?? null,
            'select' => $property['select']['name'] ?? null,
            'status' => $property['status']['name'] ?? null,
            'number' => $property['number'] ?? null,
            'checkbox' => $property['checkbox'] ?? null,
            'date' => $property['date']['start'] ?? null,
            'people' => collect($property['people'] ?? [])->pluck('name')->filter()->implode(', ') ?: null,
            default => null,
        };
    }

    /**
     * @param array<int, array<string, mixed>> $richText
     */
    private function joinRichText(array $richText): ?string
    {
        $text = collect($richText)->pluck('plain_text')->filter()->implode('');

        return $text === '' ? null : $text;
    }

    private function tokenIsValid(Request $request): bool
    {
        $expectedToken = env('MARPEL_NOTION_API_TOKEN');
        $providedToken = (string) $request->header('X-MARPEL-NOTION-TOKEN', '');

        return is_string($expectedToken) && $expectedToken !== '' && hash_equals($expectedToken, $providedToken);
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'mensaje' => 'Token de API no valido.',
        ], 401);
    }

    private function invalid(mixed $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'mensaje' => 'Datos no validos.',
            'errors' => $errors,
        ], 422);
    }

    private function failed(Throwable $exception): JsonResponse
    {
        report($exception);

        return response()->json([
            'success' => false,
            'mensaje' => 'No se pudo procesar la sincronizacion.',
        ], 500);
    }
}
