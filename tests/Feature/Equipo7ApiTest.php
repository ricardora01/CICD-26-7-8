<?php

namespace Tests\Feature;

use Tests\TestCase;

class Equipo7ApiTest extends TestCase
{
    public function test_endpoint_valida_horario_correcto_y_retorna_estructura_json(): void
    {
        $payload = [
            'estatus_alumno' => 'regular',
            'materias' => [
                [
                    'clave' => 'ISC-301',
                    'creditos' => 6,
                    'tipo' => 'obligatoria',
                    'horarios' => [
                        ['dia' => 'lunes', 'hora_inicio' => '07:00', 'hora_fin' => '09:00'],
                    ],
                ],
                [
                    'clave' => 'ISC-302',
                    'creditos' => 6,
                    'tipo' => 'laboratorio',
                    'horarios' => [
                        ['dia' => 'jueves', 'hora_inicio' => '14:00', 'hora_fin' => '16:00'],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/equipo-7/validar-horario', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'equipo',
                'estatus',
                'resumen' => [
                    'total_materias',
                    'creditos_totales',
                    'limite_creditos' => ['minimo', 'maximo'],
                    'horas_semanales',
                    'horas_muertas_totales',
                    'horas_por_dia',
                ],
                'conflictos_horario',
                'errores_reglas',
                'observaciones',
            ])
            ->assertJsonPath('equipo', 7)
            ->assertJsonPath('estatus', 'APROBADO');
    }

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
