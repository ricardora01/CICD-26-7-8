<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class Equipo5ApiTest extends TestCase
{
    protected const ENDPOINT = '/api/equipo-5/evaluar-beca';

    /**
     * @return array<string, int|float>
     */
    protected function datosValidos(): array
    {
        return [
            'promedio' => 98,
            'ingreso_familiar' => 6000,
            'integrantes_familia' => 4,
            'asistencia' => 95,
            'materias_reprobadas' => 0,
        ];
    }

    public function test_la_api_devuelve_una_respuesta_json_estructurada(): void
    {
        $response = $this->postJson(
            self::ENDPOINT,
            $this->datosValidos()
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'elegible',
                    'tipo_beca',
                    'monto_mensual',
                    'ingreso_por_persona',
                    'motivos',
                ],
            ]);
    }
}
