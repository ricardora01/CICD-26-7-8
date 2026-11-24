<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Servicio de validación de horario y carga académica para Equipo 7.
 *
 * @phpstan-type BloqueHorario array{dia: string, hora_inicio: string, hora_fin: string}
 * @phpstan-type MateriaInput array{clave: string, creditos: int, tipo: string, horarios: list<BloqueHorario>}
 * @phpstan-type ConflictoHorario array{
 *     dia: string,
 *     materia_a: string,
 *     materia_b: string,
 *     rango_a: string,
 *     rango_b: string,
 *     minutos_traslape: int
 * }
 * @phpstan-type ResultadoEvaluacion array{
 *     estatus: string,
 *     resumen: array{
 *         total_materias: int,
 *         creditos_totales: int,
 *         limite_creditos: array{minimo: int, maximo: int},
 *         horas_semanales: float,
 *         horas_muertas_totales: float,
 *         horas_por_dia: array<string, float>
 *     },
 *     conflictos_horario: list<ConflictoHorario>,
 *     errores_reglas: list<string>,
 *     observaciones: list<string>
 * }
 */
class Equipo7HorarioService
{
    public const MIN_CREDITOS_REGULAR = 12;

    public const MAX_CREDITOS_REGULAR = 36;

    public const MIN_CREDITOS_IRREGULAR = 8;

    public const MAX_CREDITOS_IRREGULAR = 24;

    public const MAX_OPTATIVAS = 2;

    public const HORA_INICIO_MIN = 420; // 07:00

    public const HORA_FIN_MAX = 1260; // 21:00

    public const DURACION_MIN_MINUTOS = 50;

    public const DURACION_MAX_MINUTOS = 240;

    public const MAX_HORAS_DIA_OBSERVACION = 8.0;

    public const MAX_HORAS_MUERTAS_OBSERVACION = 3.0;

    /** @var list<string> */
    public const DIAS_VALIDOS = [
        'lunes',
        'martes',
        'miercoles',
        'jueves',
        'viernes',
        'sabado',
    ];

    /**
     * Convierte una hora en formato HH:MM (24h) a minutos desde medianoche.
     */
    public function horaAMinutos(string $hora): int
    {
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $hora, $matches) !== 1) {
            throw new InvalidArgumentException('Formato de hora inválido.');
        }

        return ((int) $matches[1] * 60) + (int) $matches[2];
    }

    /**
     * Evalúa una propuesta de horario y carga académica.
     *
     * @param  list<MateriaInput>  $materias
     * @return ResultadoEvaluacion
     */
    public function evaluar(string $estatusAlumno, array $materias): array
    {
        $erroresReglas = [];
        $conflictosHorario = [];
        $observaciones = [];

        $clavesVistas = [];
        $creditosTotales = 0;
        $obligatorias = 0;
        $optativas = 0;

        /** @var array<string, list<array{clave: string, inicio: int, fin: int, rango: string}>> $bloquesPorDia */
        $bloquesPorDia = array_fill_keys(self::DIAS_VALIDOS, []);

        foreach ($materias as $materia) {
            $clave = strtoupper(trim($materia['clave']));

            if (isset($clavesVistas[$clave])) {
                $erroresReglas[] = "La clave de materia {$clave} está duplicada.";
            }
            $clavesVistas[$clave] = true;

            $creditosTotales += $materia['creditos'];

            if ($materia['tipo'] === 'obligatoria') {
                $obligatorias++;
            } elseif ($materia['tipo'] === 'optativa') {
                $optativas++;
            }

            foreach ($materia['horarios'] as $bloque) {
                $dia = strtolower(trim($bloque['dia']));
                $inicio = $this->horaAMinutos($bloque['hora_inicio']);
                $fin = $this->horaAMinutos($bloque['hora_fin']);
                $rango = "{$bloque['hora_inicio']}-{$bloque['hora_fin']}";

                $errorBloque = $this->validarBloque($clave, $dia, $inicio, $fin, $rango);
                if ($errorBloque !== null) {
                    $erroresReglas[] = $errorBloque;
                }

                if (isset($bloquesPorDia[$dia]) && $fin > $inicio) {
                    $bloquesPorDia[$dia][] = [
                        'clave' => $clave,
                        'inicio' => $inicio,
                        'fin' => $fin,
                        'rango' => $rango,
                    ];
                }
            }
        }

        $limites = $this->obtenerLimitesCreditos($estatusAlumno);
        if ($creditosTotales < $limites['minimo'] || $creditosTotales > $limites['maximo']) {
            $erroresReglas[] = sprintf(
                'Los créditos totales (%d) están fuera del rango permitido (%d a %d) para un alumno %s.',
                $creditosTotales,
                $limites['minimo'],
                $limites['maximo'],
                $estatusAlumno
            );
        }

        if ($obligatorias < 1) {
            $erroresReglas[] = 'La carga académica debe incluir al menos una materia obligatoria.';
        }

        if ($optativas > self::MAX_OPTATIVAS) {
            $erroresReglas[] = sprintf(
                'Se excede el máximo de %d materias optativas permitidas.',
                self::MAX_OPTATIVAS
            );
        }

        $minutosSemanales = 0;
        $minutosMuertosTotales = 0;
        /** @var array<string, float> $horasPorDia */
        $horasPorDia = [];

        foreach ($bloquesPorDia as $dia => $bloques) {
            if ($bloques === []) {
                $horasPorDia[$dia] = 0.0;

                continue;
            }

            usort(
                $bloques,
                static fn (array $a, array $b): int => $a['inicio'] <=> $b['inicio']
            );

            $conflictosDia = $this->detectarTraslapesDia($dia, $bloques);
            foreach ($conflictosDia as $conflicto) {
                $conflictosHorario[] = $conflicto;
            }

            $minutosDia = 0;
            foreach ($bloques as $bloque) {
                $minutosDia += ($bloque['fin'] - $bloque['inicio']);
            }

            $horasDia = round($minutosDia / 60, 2);
            $horasPorDia[$dia] = $horasDia;
            $minutosSemanales += $minutosDia;

            if ($horasDia > self::MAX_HORAS_DIA_OBSERVACION) {
                $observaciones[] = sprintf(
                    'El día %s supera las %.0f horas de clase (%.2f horas).',
                    $dia,
                    self::MAX_HORAS_DIA_OBSERVACION,
                    $horasDia
                );
            }

            $minutosMuertosTotales += $this->calcularMinutosMuertosDia($bloques);
        }

        $horasSemanales = round($minutosSemanales / 60, 2);
        $horasMuertasTotales = round($minutosMuertosTotales / 60, 2);

        if ($horasMuertasTotales > self::MAX_HORAS_MUERTAS_OBSERVACION) {
            $observaciones[] = sprintf(
                'La semana acumula %.2f horas muertas (límite recomendado: %.0f horas).',
                $horasMuertasTotales,
                self::MAX_HORAS_MUERTAS_OBSERVACION
            );
        }

        $estatusFinal = 'APROBADO';
        if ($erroresReglas !== [] || $conflictosHorario !== []) {
            $estatusFinal = 'RECHAZADO';
        } elseif ($observaciones !== []) {
            $estatusFinal = 'APROBADO_CON_OBSERVACIONES';
        }

        return [
            'estatus' => $estatusFinal,
            'resumen' => [
                'total_materias' => count($materias),
                'creditos_totales' => $creditosTotales,
                'limite_creditos' => $limites,
                'horas_semanales' => $horasSemanales,
                'horas_muertas_totales' => $horasMuertasTotales,
                'horas_por_dia' => $horasPorDia,
            ],
            'conflictos_horario' => $conflictosHorario,
            'errores_reglas' => array_values(array_unique($erroresReglas)),
            'observaciones' => $observaciones,
        ];
    }

    /**
     * @return array{minimo: int, maximo: int}
     */
    private function obtenerLimitesCreditos(string $estatusAlumno): array
    {
        if ($estatusAlumno === 'irregular') {
            return [
                'minimo' => self::MIN_CREDITOS_IRREGULAR,
                'maximo' => self::MAX_CREDITOS_IRREGULAR,
            ];
        }

        return [
            'minimo' => self::MIN_CREDITOS_REGULAR,
            'maximo' => self::MAX_CREDITOS_REGULAR,
        ];
    }

    private function validarBloque(
        string $clave,
        string $dia,
        int $inicio,
        int $fin,
        string $rango
    ): ?string {
        if ($fin <= $inicio) {
            return "En {$clave} ({$dia} {$rango}), la hora de fin debe ser mayor que la de inicio.";
        }

        if ($inicio < self::HORA_INICIO_MIN || $fin > self::HORA_FIN_MAX) {
            return "En {$clave} ({$dia} {$rango}), el horario debe estar entre 07:00 y 21:00.";
        }

        $duracion = $fin - $inicio;
        if ($duracion < self::DURACION_MIN_MINUTOS || $duracion > self::DURACION_MAX_MINUTOS) {
            return sprintf(
                'En %s (%s %s), la duración (%d min) debe estar entre %d y %d minutos.',
                $clave,
                $dia,
                $rango,
                $duracion,
                self::DURACION_MIN_MINUTOS,
                self::DURACION_MAX_MINUTOS
            );
        }

        return null;
    }

    /**
     * @param  list<array{clave: string, inicio: int, fin: int, rango: string}>  $bloquesOrdenados
     * @return list<ConflictoHorario>
     */
    private function detectarTraslapesDia(string $dia, array $bloquesOrdenados): array
    {
        $conflictos = [];
        $total = count($bloquesOrdenados);

        for ($i = 0; $i < $total; $i++) {
            for ($j = $i + 1; $j < $total; $j++) {
                $bloqueA = $bloquesOrdenados[$i];
                $bloqueB = $bloquesOrdenados[$j];

                if ($bloqueB['inicio'] >= $bloqueA['fin']) {
                    break;
                }

                $traslape = min($bloqueA['fin'], $bloqueB['fin']) - max($bloqueA['inicio'], $bloqueB['inicio']);
                if ($traslape > 0) {
                    $conflictos[] = [
                        'dia' => $dia,
                        'materia_a' => $bloqueA['clave'],
                        'materia_b' => $bloqueB['clave'],
                        'rango_a' => $bloqueA['rango'],
                        'rango_b' => $bloqueB['rango'],
                        'minutos_traslape' => $traslape,
                    ];
                }
            }
        }

        return $conflictos;
    }

    /**
     * @param  list<array{clave: string, inicio: int, fin: int, rango: string}>  $bloquesOrdenados
     */
    private function calcularMinutosMuertosDia(array $bloquesOrdenados): int
    {
        if (count($bloquesOrdenados) < 2) {
            return 0;
        }

        $minutosMuertos = 0;
        $finMaximoActual = $bloquesOrdenados[0]['fin'];

        $total = count($bloquesOrdenados);
        for ($i = 1; $i < $total; $i++) {
            $inicioActual = $bloquesOrdenados[$i]['inicio'];
            $finActual = $bloquesOrdenados[$i]['fin'];

            if ($inicioActual > $finMaximoActual) {
                $minutosMuertos += ($inicioActual - $finMaximoActual);
            }

            if ($finActual > $finMaximoActual) {
                $finMaximoActual = $finActual;
            }
        }

        return $minutosMuertos;
    }
}
