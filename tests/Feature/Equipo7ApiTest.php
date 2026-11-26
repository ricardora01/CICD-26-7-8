<?php

namespace Tests\Feature;

use Tests\TestCase;

class Equipo7ApiTest extends TestCase
{
    // 22151231
    public function test_endpoint_rechaza_carga_sin_materia_obligatoria_y_exceso_de_optativas(): void
    {
        $payload = [
            'estatus_alumno' => 'regular',
            'materias' => [
                [
                    'clave' => 'OPT-101',
                    'creditos' => 4,
                    'tipo' => 'optativa',
                    'horarios' => [['dia' => 'lunes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']],
                ],
                [
                    'clave' => 'OPT-102',
                    'creditos' => 4,
                    'tipo' => 'optativa',
                    'horarios' => [['dia' => 'martes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']],
                ],
                [
                    'clave' => 'OPT-103',
                    'creditos' => 4,
                    'tipo' => 'optativa',
                    'horarios' => [['dia' => 'miercoles', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']],
                ],
            ],
        ];

        $response = $this->postJson('/api/equipo-7/validar-horario', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('estatus', 'RECHAZADO');

        $this->assertNotEmpty($response->json('errores_reglas'));
    }
}
