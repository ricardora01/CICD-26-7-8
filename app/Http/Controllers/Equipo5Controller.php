<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Equipo5BecaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class Equipo5Controller extends Controller
{
    public function evaluar(
        Request $request,
        Equipo5BecaService $servicio
    ): JsonResponse {
        $camposPermitidos = [
            'promedio',
            'ingreso_familiar',
            'integrantes_familia',
            'asistencia',
            'materias_reprobadas',
        ];

        if (! $request->isJson()) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud debe estar en formato JSON.',
            ], 422);
        }

        $datos = $request->json()->all();

        $camposDesconocidos = array_diff(
            array_keys($datos),
            $camposPermitidos
        );

        if ($camposDesconocidos !== []) {
            return response()->json([
                'success' => false,
                'message' => 'La solicitud contiene campos no permitidos.',
            ], 422);
        }

        $validator = Validator::make($datos, [
            'promedio' => ['required', 'numeric', 'min:0', 'max:100'],
            'ingreso_familiar' => ['required', 'numeric', 'min:0'],
            'integrantes_familia' => ['required', 'integer', 'min:1'],
            'asistencia' => ['required', 'numeric', 'min:0', 'max:100'],
            'materias_reprobadas' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $promedio = $datos['promedio'] ?? null;
        $ingresoFamiliar = $datos['ingreso_familiar'] ?? null;
        $integrantesFamilia = $datos['integrantes_familia'] ?? null;
        $asistencia = $datos['asistencia'] ?? null;
        $materiasReprobadas = $datos['materias_reprobadas'] ?? null;

        if (
            (! is_int($promedio) && ! is_float($promedio)) ||
            (! is_int($ingresoFamiliar) && ! is_float($ingresoFamiliar)) ||
            ! is_int($integrantesFamilia) ||
            (! is_int($asistencia) && ! is_float($asistencia)) ||
            ! is_int($materiasReprobadas) ||
            ! is_finite($promedio) ||
            ! is_finite($ingresoFamiliar) ||
            ! is_finite($asistencia)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Los tipos de datos proporcionados no son válidos.',
            ], 422);
        }

        $resultado = $servicio->evaluar(
            (float) $promedio,
            (float) $ingresoFamiliar,
            $integrantesFamilia,
            (float) $asistencia,
            $materiasReprobadas
        );

        return response()->json([
            'success' => true,
            'data' => $resultado,
        ], 200);
    }
}
