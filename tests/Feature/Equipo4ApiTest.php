<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class Equipo4ApiTest extends TestCase
{
    private string $endpoint = '/api/equipo-4/evaluar-reinscripcion';

    /**
     * Payload válido base que cada integrante puede reutilizar y sobrescribir.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payloadValido(array $overrides = []): array
    {
        return array_merge([
            'promedio_anterior' => 88.5,
            'materias_reprobadas' => 0,
            'creditos_acumulados' => 120,
            'creditos_solicitados' => 30,
            'asistencia_porcentaje' => 95.0,
            'tiene_beca' => false,
            'tiene_adeudo' => false,
            'ingles_aprobado' => false,
        ], $overrides);
    }

    // === SECCION INTEGRANTE 1 (LIDER - 23150306) ===

    // === SECCION INTEGRANTE 2 (23150315) ===

    public function test_alumno_excelente_con_beca_puede_inscribir_42_creditos_y_cumple_tramites(): void
    {
        $this->postJson($this->endpoint, $this->payloadValido([
            'promedio_anterior' => 94.0,
            'materias_reprobadas' => 0,
            'creditos_acumulados' => 210,
            'creditos_solicitados' => 42,
            'asistencia_porcentaje' => 95.0,
            'tiene_beca' => true,
            'tiene_adeudo' => false,
            'ingles_aprobado' => true,
        ]))
            ->assertOk()
            ->assertExactJson([
                'autorizado' => true,
                'motivo' => 'Reinscripción autorizada.',
                'detalle_academico' => [
                    'carga_maxima_permitida' => 42,
                    'creditos_solicitados' => 42,
                    'conserva_beca' => true,
                    'cuota_semestral' => 1750.0,
                    'servicio_social_disponible' => true,
                    'residencias_disponibles' => true,
                ],
            ]);
    }

    // === SECCION INTEGRANTE 3 ===

    // === SECCION INTEGRANTE 4 ===

    // === SECCION INTEGRANTE 5 ===

    // === SECCION INTEGRANTE 6 ===
}
