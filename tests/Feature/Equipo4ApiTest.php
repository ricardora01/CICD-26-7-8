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

    // === SECCION INTEGRANTE 2 (23150315) ===

    // === SECCION INTEGRANTE 3 (23150304) ===

    // === SECCION INTEGRANTE 4 (23150313) ===

    // === SECCION INTEGRANTE 5 (23150364) ===

    // === SECCION INTEGRANTE 6 (23150357) ===
}
