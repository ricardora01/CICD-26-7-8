<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Equipo4Controller extends Controller
{
    /**
     * Campos permitidos en la solicitud de reinscripción.
     *
     * @var list<string>
     */
    private const CAMPOS_PERMITIDOS = [
        'promedio_anterior',
        'materias_reprobadas',
        'creditos_acumulados',
        'creditos_solicitados',
        'asistencia_porcentaje',
        'tiene_beca',
        'tiene_adeudo',
        'ingles_aprobado',
    ];

    private const CREDITOS_SERVICIO_SOCIAL = 182;

    private const CREDITOS_RESIDENCIAS = 208;

    public function evaluarReinscripcion(Request $request): JsonResponse
    {
        $camposExtra = array_values(array_diff(
            array_map('strval', array_keys($request->all())),
            self::CAMPOS_PERMITIDOS
        ));

        if ($camposExtra !== []) {
            return response()->json([
                'message' => 'La solicitud contiene campos no permitidos.',
                'errors' => [
                    'campos_no_permitidos' => $camposExtra,
                ],
            ], 422);
        }

        $validated = $request->validate([
            'promedio_anterior' => ['required', 'numeric', 'min:0', 'max:100'],
            'materias_reprobadas' => ['required', 'integer', 'min:0', 'max:15'],
            'creditos_acumulados' => ['required', 'integer', 'min:0', 'max:260'],
            'creditos_solicitados' => ['required', 'integer', 'min:1', 'max:50'],
            'asistencia_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'tiene_beca' => ['required', 'boolean'],
            'tiene_adeudo' => ['required', 'boolean'],
            'ingles_aprobado' => ['required', 'boolean'],
        ]);

        $promedio = (float) $validated['promedio_anterior'];
        $reprobadas = (int) $validated['materias_reprobadas'];
        $creditosAcumulados = (int) $validated['creditos_acumulados'];
        $creditosSolicitados = (int) $validated['creditos_solicitados'];
        $asistencia = (float) $validated['asistencia_porcentaje'];
        $tieneBeca = $this->aBooleano($validated['tiene_beca']);
        $tieneAdeudo = $this->aBooleano($validated['tiene_adeudo']);
        $inglesAprobado = $this->aBooleano($validated['ingles_aprobado']);

        $cargaMaxima = $this->calcularCargaMaxima($promedio, $reprobadas);
        $conservaBeca = $this->evaluarConservacionBeca($tieneBeca, $promedio, $reprobadas, $asistencia);

        $motivoBloqueo = $this->verificarBloqueos($tieneAdeudo, $asistencia, $reprobadas);

        if ($motivoBloqueo !== null) {
            $autorizado = false;
            $motivo = $motivoBloqueo;
        } elseif ($creditosSolicitados > $cargaMaxima) {
            $autorizado = false;
            $motivo = sprintf(
                'Los créditos solicitados (%d) exceden la carga máxima permitida (%d).',
                $creditosSolicitados,
                $cargaMaxima
            );
        } else {
            $autorizado = true;
            $motivo = 'Reinscripción autorizada.';
        }

        $cuota = $autorizado ? $this->calcularCuotaSemestral($conservaBeca, $reprobadas) : 0.0;

        return response()->json([
            'autorizado' => $autorizado,
            'motivo' => $motivo,
            'detalle_academico' => [
                'carga_maxima_permitida' => $cargaMaxima,
                'creditos_solicitados' => $creditosSolicitados,
                'conserva_beca' => $conservaBeca,
                'cuota_semestral' => $cuota,
                'servicio_social_disponible' => $creditosAcumulados >= self::CREDITOS_SERVICIO_SOCIAL,
                'residencias_disponibles' => $creditosAcumulados >= self::CREDITOS_RESIDENCIAS && $inglesAprobado,
            ],
        ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
    }

    public function verificarBloqueos(bool $tieneAdeudo, float $asistencia, int $reprobadas): ?string
    {
        if ($tieneAdeudo) {
            return 'El alumno tiene un adeudo pendiente.';
        }

        if ($asistencia < 80.0) {
            return 'La asistencia es menor al 80% requerido.';
        }

        if ($reprobadas >= 3) {
            return 'El alumno tiene 3 o más materias reprobadas.';
        }

        return null;
    }

    public function calcularCargaMaxima(float $promedio, int $reprobadas): int
    {
        if ($reprobadas >= 1 && $reprobadas <= 2) {
            return 24;
        }

        if ($reprobadas === 0 && $promedio >= 90.0) {
            return 42;
        }

        return 36;
    }

    public function evaluarConservacionBeca(bool $tieneBeca, float $promedio, int $reprobadas, float $asistencia): bool
    {
        return $tieneBeca
            && $promedio >= 85.0
            && $reprobadas === 0
            && $asistencia >= 90.0;
    }

    public function calcularCuotaSemestral(bool $conservaBeca, int $reprobadas): float
    {
        return round(($conservaBeca ? 1750.0 : 3500.0) + ($reprobadas * 450.0), 2);
    }

    /**
     * Convierte un valor ya validado con la regla "boolean" (true, false, 1, 0, "1", "0").
     */
    private function aBooleano(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOLEAN);
    }
}
