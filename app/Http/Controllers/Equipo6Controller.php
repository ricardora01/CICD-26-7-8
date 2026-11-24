<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Equipo 6 — Evaluador de calificaciones por unidades.
 *
 * Recibe las calificaciones de las unidades de una materia con su
 * ponderación (porcentaje) y determina si la materia queda acreditada,
 * la calificación final, el nivel de desempeño y qué unidades faltan.
 *
 * Endpoint: POST /api/equipo-6/procesar
 */
final class Equipo6Controller extends Controller
{
    /** Calificación mínima para acreditar una unidad. */
    public const CALIFICACION_MINIMA = 70.0;

    /** Cantidad máxima de unidades por materia. */
    public const MAX_UNIDADES = 8;

    /** La suma de las ponderaciones debe dar exactamente este valor. */
    public const SUMA_PONDERACIONES = 100;

    /**
     * Punto de entrada de la API.
     */
    public function procesar(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make(
                $request->all(),
                $this->reglas(),
                $this->mensajes()
            );

            if ($validator->fails()) {
                return $this->respuestaError(
                    'Los datos enviados no son válidos.',
                    $validator->errors()->toArray(),
                    422
                );
            }

            /** @var array{materia?: string|null, unidades: list<array{calificacion: int|float|string, ponderacion: int|string}>} $datos */
            $datos = $validator->validated();

            $unidades = array_map(
                fn (array $unidad): array => [
                    'calificacion' => (float) $unidad['calificacion'],
                    'ponderacion' => (int) $unidad['ponderacion'],
                ],
                $datos['unidades']
            );

            $suma = $this->sumaPonderaciones($unidades);

            if ($suma !== self::SUMA_PONDERACIONES) {
                return $this->respuestaError(
                    'Los datos enviados no son válidos.',
                    ['unidades' => [
                        'La suma de las ponderaciones debe ser exactamente '
                        .self::SUMA_PONDERACIONES.'. Suma recibida: '.$suma.'.',
                    ]],
                    422
                );
            }

            $resultado = $this->evaluar($unidades);
            $resultado = ['materia' => $datos['materia'] ?? null] + $resultado;

            return response()->json([
                'success' => true,
                'data' => $resultado,
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            // Se registra el detalle en el log, pero nunca se envía al cliente.
            Log::error('Equipo6Controller: error inesperado', ['exception' => $e]);

            return $this->respuestaError(
                'Ocurrió un error interno al procesar la solicitud.',
                [],
                500
            );
        }
    }

    /**
     * Responde a métodos HTTP distintos de POST sin exponer información interna.
     */
    public function metodoNoPermitido(): JsonResponse
    {
        return $this->respuestaError(
            'Método no permitido. Usa POST para consumir este endpoint.',
            [],
            405
        )->header('Allow', 'POST');
    }

    /**
     * Lógica principal (sin HTTP). Se puede probar de forma unitaria.
     *
     * @param  list<array{calificacion: float, ponderacion: int}>  $unidades
     * @return array{
     *     estatus: string,
     *     calificacion_final: int|null,
     *     promedio_ponderado: float,
     *     nivel_desempeno: string,
     *     total_unidades: int,
     *     unidades_acreditadas: int,
     *     unidades_pendientes: list<int>,
     *     detalle: list<array{unidad: int, calificacion: float, ponderacion: int, aporte: float, acreditada: bool, puntos_faltantes: float}>
     * }
     */
    public function evaluar(array $unidades): array
    {
        $detalle = [];
        $pendientes = [];
        $promedio = 0.0;

        foreach ($unidades as $indice => $unidad) {
            $numero = $indice + 1;
            $acreditada = $this->unidadAcreditada($unidad['calificacion']);
            $aporte = $unidad['calificacion'] * (float) $unidad['ponderacion'] / 100.0;
            $promedio += $aporte;

            if (! $acreditada) {
                $pendientes[] = $numero;
            }

            $detalle[] = [
                'unidad' => $numero,
                'calificacion' => round($unidad['calificacion'], 2),
                'ponderacion' => $unidad['ponderacion'],
                'aporte' => round($aporte, 2),
                'acreditada' => $acreditada,
                'puntos_faltantes' => $this->puntosFaltantes($unidad['calificacion']),
            ];
        }

        $promedio = round($promedio, 2);
        $acreditada = $pendientes === [];
        $final = $acreditada ? (int) round($promedio) : null;

        return [
            'estatus' => $acreditada ? 'ACREDITADA' : 'NO ACREDITADA',
            'calificacion_final' => $final,
            'promedio_ponderado' => $promedio,
            'nivel_desempeno' => $this->nivelDesempeno($final),
            'total_unidades' => count($unidades),
            'unidades_acreditadas' => count($unidades) - count($pendientes),
            'unidades_pendientes' => $pendientes,
            'detalle' => $detalle,
        ];
    }

    /**
     * Una unidad se acredita con 70 o más.
     */
    public function unidadAcreditada(float $calificacion): bool
    {
        return $calificacion >= self::CALIFICACION_MINIMA;
    }

    /**
     * Puntos que le faltan a una unidad para llegar a 70 (0 si ya acreditó).
     */
    public function puntosFaltantes(float $calificacion): float
    {
        return round(max(0.0, self::CALIFICACION_MINIMA - $calificacion), 2);
    }

    /**
     * Nivel de desempeño según la calificación final.
     * null significa que la materia no se acreditó.
     */
    public function nivelDesempeno(?int $calificacionFinal): string
    {
        return match (true) {
            $calificacionFinal === null,
            $calificacionFinal < self::CALIFICACION_MINIMA => 'Desempeño insuficiente',
            $calificacionFinal >= 95 => 'Excelente',
            $calificacionFinal >= 85 => 'Notable',
            $calificacionFinal >= 75 => 'Bueno',
            default => 'Suficiente',
        };
    }

    /**
     * @param  list<array{calificacion: float, ponderacion: int}>  $unidades
     */
    public function sumaPonderaciones(array $unidades): int
    {
        return array_sum(array_column($unidades, 'ponderacion'));
    }

    /**
     * @return array<string, list<string>>
     */
    private function reglas(): array
    {
        return [
            'materia' => ['sometimes', 'nullable', 'string', 'min:3', 'max:100', 'regex:/^[\pL\pN\s.\-]+$/u'],
            'unidades' => ['present', 'array', 'list', 'min:1', 'max:'.self::MAX_UNIDADES],
            'unidades.*' => ['required', 'array:calificacion,ponderacion'],
            'unidades.*.calificacion' => ['bail', 'required', 'numeric', 'decimal:0,2', 'between:0,100'],
            'unidades.*.ponderacion' => ['bail', 'required', 'integer', 'between:1,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'materia.string' => 'La materia debe ser texto.',
            'materia.min' => 'La materia debe tener al menos 3 caracteres.',
            'materia.max' => 'La materia no puede tener más de 100 caracteres.',
            'materia.regex' => 'La materia solo puede contener letras, números, espacios, puntos y guiones.',
            'unidades.present' => 'Debes enviar la lista de unidades.',
            'unidades.array' => 'Las unidades deben enviarse como una lista.',
            'unidades.list' => 'Las unidades deben enviarse como una lista, no como un objeto.',
            'unidades.min' => 'Debes enviar al menos una unidad.',
            'unidades.max' => 'No se permiten más de '.self::MAX_UNIDADES.' unidades.',
            'unidades.*.required' => 'Cada unidad es obligatoria.',
            'unidades.*.array' => 'Cada unidad debe tener solo calificacion y ponderacion.',
            'unidades.*.calificacion.required' => 'La calificación de cada unidad es obligatoria.',
            'unidades.*.calificacion.numeric' => 'La calificación debe ser un número.',
            'unidades.*.calificacion.decimal' => 'La calificación admite máximo 2 decimales.',
            'unidades.*.calificacion.between' => 'La calificación debe estar entre 0 y 100.',
            'unidades.*.ponderacion.required' => 'La ponderación de cada unidad es obligatoria.',
            'unidades.*.ponderacion.integer' => 'La ponderación debe ser un número entero.',
            'unidades.*.ponderacion.between' => 'La ponderación debe estar entre 1 y 100.',
        ];
    }

    /**
     * Formato único para todas las respuestas de error.
     *
     * @param  array<string, mixed>  $errores
     */
    private function respuestaError(string $mensaje, array $errores, int $codigo): JsonResponse
    {
        $cuerpo = ['success' => false, 'message' => $mensaje];

        if ($errores !== []) {
            $cuerpo['errors'] = $errores;
        }

        return response()->json($cuerpo, $codigo, [], JSON_UNESCAPED_UNICODE);
    }
}
