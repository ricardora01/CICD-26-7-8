<?php

namespace Tests\Feature;

use Tests\TestCase;

class Equipo7ApiTest extends TestCase
{
    // 22151137
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

    // 22151201
    public function test_endpoint_acepta_limite_minimo_de_creditos_para_alumno_irregular(): void
    {
        $payload = [
            'estatus_alumno' => 'irregular',
            'materias' => [
                [
                    'clave' => 'ISC-403',
                    'creditos' => 8,
                    'tipo' => 'obligatoria',
                    'horarios' => [
                        ['dia' => 'viernes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/equipo-7/validar-horario', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('estatus', 'APROBADO')
            ->assertJsonPath('resumen.creditos_totales', 8)
            ->assertJsonPath('resumen.limite_creditos.minimo', 8)
            ->assertJsonPath('resumen.limite_creditos.maximo', 24);
    }

    // 22151184
    public function test_endpoint_rechaza_clave_con_html_y_metodo_get_no_permitido(): void
    {
        $payload = [
            'estatus_alumno' => 'regular',
            'materias' => [
                [
                    'clave' => '<script>alert(1)</script>',
                    'creditos' => 6,
                    'tipo' => 'obligatoria',
                    'horarios' => [
                        ['dia' => 'lunes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/equipo-7/validar-horario', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['materias.0.clave']);

        $this->getJson('/api/equipo-7/validar-horario')
            ->assertStatus(405);
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

    // 22151231
    public function test_endpoint_rechaza_campos_adicionales_con_http_422(): void
    {
        $payload = [
            'estatus_alumno' => 'regular',
            'campo_extra_malicioso' => '<script>alert(1)</script>',
            'materias' => [
                [
                    'clave' => 'ISC-301',
                    'creditos' => 6,
                    'tipo' => 'obligatoria',
                    'horarios' => [
                        ['dia' => 'lunes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/equipo-7/validar-horario', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payload']);
    }
}
