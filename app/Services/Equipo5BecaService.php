<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class Equipo5BecaService
{
    /**
     * @return array{
     *     elegible: bool,
     *     tipo_beca: string,
     *     monto_mensual: int,
     *     ingreso_por_persona: float,
     *     motivos: list<string>
     * }
     */
    public function evaluar(
        float $promedio,
        float $ingresoFamiliar,
        int $integrantesFamilia,
        float $asistencia,
        int $materiasReprobadas
    ): array {
        if (
            ! is_finite($promedio) ||
            ! is_finite($ingresoFamiliar) ||
            ! is_finite($asistencia) ||
            $promedio < 0 ||
            $promedio > 100 ||
            $ingresoFamiliar < 0 ||
            $integrantesFamilia < 1 ||
            $asistencia < 0 ||
            $asistencia > 100 ||
            $materiasReprobadas < 0
        ) {
            throw new InvalidArgumentException(
                'Los datos proporcionados no son válidos.'
            );
        }

        $ingresoPorPersona = $ingresoFamiliar / $integrantesFamilia;

        $motivos = [];

        if ($promedio < 80) {
            $motivos[] = 'El promedio es inferior a 80.';
        }

        if ($asistencia < 85) {
            $motivos[] = 'La asistencia es inferior al 85%.';
        }

        if ($materiasReprobadas > 0) {
            $motivos[] = 'El estudiante tiene materias reprobadas.';
        }

        if ($ingresoPorPersona > 5000) {
            $motivos[] = 'El ingreso por persona supera el límite permitido.';
        }

        $tipoBeca = 'rechazada';
        $montoMensual = 0;

        if ($motivos === []) {
            if ($promedio >= 95 && $ingresoPorPersona <= 2000) {
                $tipoBeca = 'completa';
                $montoMensual = 3000;
            } elseif ($promedio >= 90 && $ingresoPorPersona <= 3500) {
                $tipoBeca = 'parcial';
                $montoMensual = 2000;
            } else {
                $tipoBeca = 'basica';
                $montoMensual = 1000;
            }
        }

        return [
            'elegible' => $motivos === [],
            'tipo_beca' => $tipoBeca,
            'monto_mensual' => $montoMensual,
            'ingreso_por_persona' => round($ingresoPorPersona, 2),
            'motivos' => $motivos,
        ];
    }
}
