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

    public function test_el_endpoint_responde_con_la_estructura_esperada(): void
    {
        $this->postJson($this->endpoint, $this->payloadValido())
            ->assertOk()
            ->assertJsonStructure([
                'autorizado',
                'motivo',
                'detalle_academico' => [
                    'carga_maxima_permitida',
                    'creditos_solicitados',
                    'conserva_beca',
                    'cuota_semestral',
                    'servicio_social_disponible',
                    'residencias_disponibles',
                ],
            ]);
    }

    // === SECCION INTEGRANTE 1 (LIDER - 23150306) ===

    public function test_alumno_regular_con_32_creditos_es_autorizado(): void
    {
        $response = $this->postJson($this->endpoint, [
            'promedio_anterior' => 82,
            'materias_reprobadas' => 0,
            'creditos_acumulados' => 120,
            'creditos_solicitados' => 32,
            'asistencia_porcentaje' => 92,
            'tiene_beca' => false,
            'tiene_adeudo' => false,
            'ingles_aprobado' => false,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'autorizado',
                'motivo',
                'detalle_academico' => [
                    'carga_maxima_permitida',
                    'creditos_solicitados',
                    'conserva_beca',
                    'cuota_semestral',
                    'servicio_social_disponible',
                    'residencias_disponibles',
                ],
            ])
            ->assertJson([
                'autorizado' => true,
                'detalle_academico' => [
                    'carga_maxima_permitida' => 36,
                    'creditos_solicitados' => 32,
                    'conserva_beca' => false,
                    'cuota_semestral' => 3500.0,
                    'servicio_social_disponible' => false,
                    'residencias_disponibles' => false,
                ],
            ]);
    }

    // === SECCION INTEGRANTE 2 (23150315) ===

    // === SECCION INTEGRANTE 3 (23150304) ===

    // === SECCION INTEGRANTE 4 (23150313) ===

    // === SECCION INTEGRANTE 5 (23150364) ===

    // === SECCION INTEGRANTE 6 (23150357) ===
    public function test_alumno_con_adeudo_no_es_autorizado_aunque_tenga_promedio_perfecto(): void
    {
        $this->postJson(
            $this->endpoint,
            $this->payloadValido([
                'promedio_anterior' => 100.0,
                'tiene_adeudo' => true,
            ])
        )
            ->assertOk()
            ->assertJsonPath('autorizado', false);
    }

    public function test_promedio_no_numerico_devuelve_422_con_error_sin_detalles_internos(): void
    {
        $this->postJson(
            $this->endpoint,
            $this->payloadValido([
                'promedio_anterior' => 'promedio-no-numerico',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['promedio_anterior'])
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('trace');
    }
}
