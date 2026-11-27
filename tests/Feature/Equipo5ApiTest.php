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

    public function test_api_calcula_ingreso_por_persona(): void
    {
        $datos = $this->datosValidos();

        $datos['ingreso_familiar'] = 12000;
        $datos['integrantes_familia'] = 4;

        $response = $this->postJson(
            self::ENDPOINT,
            $datos
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.ingreso_por_persona',
                3000
            );
    }

    public function test_api_redondea_ingreso_por_persona(): void
    {
        $datos = $this->datosValidos();

        $datos['ingreso_familiar'] = 10000;
        $datos['integrantes_familia'] = 3;

        $response = $this->postJson(
            self::ENDPOINT,
            $datos
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.ingreso_por_persona',
                3333.33
            );
    }

    public function test_api_rechaza_cero_integrantes(): void
    {
        $datos = $this->datosValidos();

        $datos['integrantes_familia'] = 0;

        $response = $this->postJson(
            self::ENDPOINT,
            $datos
        );

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors([
                'integrantes_familia',
            ]);
    }

    public function test_api_devuelve_beca_completa(): void
    {
        $response = $this->postJson(
            self::ENDPOINT,
            [
                'promedio' => 98,
                'ingreso_familiar' => 6000,
                'integrantes_familia' => 4,
                'asistencia' => 95,
                'materias_reprobadas' => 0,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.elegible', true)
            ->assertJsonPath('data.tipo_beca', 'completa')
            ->assertJsonPath('data.monto_mensual', 3000);
    }

    public function test_api_devuelve_beca_parcial(): void
    {
        $response = $this->postJson(
            self::ENDPOINT,
            [
                'promedio' => 92,
                'ingreso_familiar' => 10500,
                'integrantes_familia' => 3,
                'asistencia' => 95,
                'materias_reprobadas' => 0,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.elegible', true)
            ->assertJsonPath('data.tipo_beca', 'parcial')
            ->assertJsonPath('data.monto_mensual', 2000);
    }

    public function test_api_devuelve_beca_basica(): void
    {
        $response = $this->postJson(
            self::ENDPOINT,
            [
                'promedio' => 85,
                'ingreso_familiar' => 12000,
                'integrantes_familia' => 3,
                'asistencia' => 90,
                'materias_reprobadas' => 0,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.elegible', true)
            ->assertJsonPath('data.tipo_beca', 'basica')
            ->assertJsonPath('data.monto_mensual', 1000);
    }

    public function test_la_api_rechaza_beca_por_asistencia_insuficiente(): void
    {
        $datos = $this->datosValidos();
        $datos['asistencia'] = 75;

        $response = $this->postJson(
            self::ENDPOINT,
            $datos
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.elegible', false)
            ->assertJsonPath('data.tipo_beca', 'rechazada')
            ->assertJsonPath('data.monto_mensual', 0)
            ->assertJsonPath('data.motivos', [
                'La asistencia es inferior al 85%.',
            ]);
    }

    public function test_la_api_rechaza_beca_por_ingreso_elevado(): void
    {
        $datos = $this->datosValidos();
        $datos['ingreso_familiar'] = 24000;

        $response = $this->postJson(
            self::ENDPOINT,
            $datos
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.elegible', false)
            ->assertJsonPath('data.tipo_beca', 'rechazada')
            ->assertJsonPath('data.monto_mensual', 0)
            ->assertJsonPath('data.ingreso_por_persona', 6000)
            ->assertJsonPath('data.motivos', [
                'El ingreso por persona supera el límite permitido.',
            ]);
    }

    public function test_la_api_devuelve_todos_los_motivos_de_rechazo(): void
    {
        $datos = $this->datosValidos();
        $datos['promedio'] = 70;
        $datos['ingreso_familiar'] = 24000;
        $datos['asistencia'] = 75;
        $datos['materias_reprobadas'] = 2;

        $response = $this->postJson(
            self::ENDPOINT,
            $datos
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.elegible', false)
            ->assertJsonPath('data.tipo_beca', 'rechazada')
            ->assertJsonPath('data.monto_mensual', 0)
            ->assertJsonCount(4, 'data.motivos')
            ->assertJsonPath('data.motivos', [
                'El promedio es inferior a 80.',
                'La asistencia es inferior al 85%.',
                'El estudiante tiene materias reprobadas.',
                'El ingreso por persona supera el límite permitido.',
            ]);
    }
}