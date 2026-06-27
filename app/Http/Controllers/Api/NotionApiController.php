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

        $validator = Validator::make($request->all(), [
            'notion_page_id' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'cif' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'persona_contacto' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'localidad' => ['nullable', 'string', 'max:255'],
            'provincia' => ['nullable', 'string', 'max:255'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
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

        $validator = Validator::make($request->all(), [
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

        $validator = Validator::make($request->all(), [
            'notion_page_id' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'cif' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
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
