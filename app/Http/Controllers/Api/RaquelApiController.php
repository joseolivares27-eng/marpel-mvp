<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RaquelNoticeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RaquelApiController extends Controller
{
    public function __invoke(Request $request, RaquelNoticeService $service): JsonResponse
    {
        $expectedToken = env('MARPEL_API_TOKEN');
        $providedToken = (string) $request->header('X-MARPEL-API-TOKEN', '');

        if (! is_string($expectedToken) || $expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'success' => false,
                'aviso_id' => null,
                'cliente_id' => null,
                'instalacion_id' => null,
                'mensaje' => 'Token de API no valido.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'nombre_cliente' => ['nullable', 'string', 'max:255'],
            'telefono' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'direccion' => ['required', 'string', 'max:500'],
            'descripcion' => ['required', 'string', 'max:5000'],
            'persona_contacto' => ['nullable', 'string', 'max:255'],
            'origen' => ['nullable', 'string', 'max:50'],
            'prioridad' => ['nullable', 'string', 'in:low,normal,urgent,baja,media,alta,urgente'],
            'tipo_cliente' => ['nullable', 'string', 'max:50'],
            'equipo_tipo' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'aviso_id' => null,
                'cliente_id' => null,
                'instalacion_id' => null,
                'mensaje' => 'Datos no validos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $notice = $service->createFromRaquelPayload($validator->validated());
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'aviso_id' => null,
                'cliente_id' => null,
                'instalacion_id' => null,
                'mensaje' => 'No se pudo crear el aviso.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'aviso_id' => $notice->id,
            'cliente_id' => $notice->customer_id,
            'instalacion_id' => $notice->installation_id,
            'mensaje' => 'Aviso creado correctamente desde Raquel.',
        ], 201);
    }
}
