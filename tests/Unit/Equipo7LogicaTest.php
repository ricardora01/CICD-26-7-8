<?php

namespace Tests\Unit;

use App\Services\Equipo7HorarioService;
use PHPUnit\Framework\TestCase;

class Equipo7LogicaTest extends TestCase
{
    private Equipo7HorarioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new Equipo7HorarioService;
    }

    // 22151137
    public function test_aprueba_carga_valida_sin_traslapes(): void
    {
        $materias = [
            [
                'clave' => 'ISC-101',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'lunes', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
                    ['dia' => 'miercoles', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'],
                ],
            ],
            [
                'clave' => 'ISC-102',
                'creditos' => 6,
                'tipo' => 'optativa',
                'horarios' => [
                    ['dia' => 'lunes', 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
                    ['dia' => 'miercoles', 'hora_inicio' => '10:00', 'hora_fin' => '12:00'],
                ],
            ],
        ];

        $resultado = $this->service->evaluar('regular', $materias);

        $this->assertSame('APROBADO', $resultado['estatus']);
        $this->assertSame(12, $resultado['resumen']['creditos_totales']);
        $this->assertSame([], $resultado['conflictos_horario']);
        $this->assertSame([], $resultado['errores_reglas']);
        $this->assertEquals(8.0, $resultado['resumen']['horas_semanales']);
    }

    // 22151201

    public function test_permite_clases_consecutivas_y_limites_exactos_de_horario_y_duracion(): void
    {
        $materias = [
            [
                'clave' => 'ISC-401',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'lunes', 'hora_inicio' => '07:00', 'hora_fin' => '07:50'],
                    ['dia' => 'lunes', 'hora_inicio' => '07:50', 'hora_fin' => '11:50'],
                ],
            ],
            [
                'clave' => 'ISC-402',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'martes', 'hora_inicio' => '17:00', 'hora_fin' => '21:00'],
                ],
            ],
        ];

        $resultado = $this->service->evaluar('regular', $materias);

        $this->assertSame('APROBADO', $resultado['estatus']);
        $this->assertSame([], $resultado['conflictos_horario']);
        $this->assertSame([], $resultado['errores_reglas']);
        $this->assertEquals(0.0, $resultado['resumen']['horas_muertas_totales']);
    }

    public function test_detecta_traslape_entre_dos_materias_el_mismo_dia(): void
    {
        $materias = [
            [
                'clave' => 'ISC-201',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'martes', 'hora_inicio' => '09:00', 'hora_fin' => '11:00'],
                ],
            ],
            [
                'clave' => 'ISC-202',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'martes', 'hora_inicio' => '10:30', 'hora_fin' => '12:30'],
                ],
            ],
        ];

        $resultado = $this->service->evaluar('regular', $materias);

        $this->assertSame('RECHAZADO', $resultado['estatus']);
        $this->assertCount(1, $resultado['conflictos_horario']);
        $this->assertSame(30, $resultado['conflictos_horario'][0]['minutos_traslape']);
        $this->assertSame('martes', $resultado['conflictos_horario'][0]['dia']);
    }

    // 22151184
    public function test_genera_observacion_por_exceso_de_horas_muertas_semanales(): void
    {
        $materias = [
            [
                'clave' => 'ISC-501',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'lunes', 'hora_inicio' => '07:00', 'hora_fin' => '09:00'],
                ],
            ],
            [
                'clave' => 'ISC-502',
                'creditos' => 6,
                'tipo' => 'obligatoria',
                'horarios' => [
                    ['dia' => 'lunes', 'hora_inicio' => '13:00', 'hora_fin' => '15:00'],
                ],
            ],
        ];

        $resultado = $this->service->evaluar('regular', $materias);

        $this->assertSame('APROBADO_CON_OBSERVACIONES', $resultado['estatus']);
        $this->assertEquals(4.0, $resultado['resumen']['horas_muertas_totales']);
        $this->assertNotEmpty($resultado['observaciones']);
    }

    //22151231
    public function test_hora_a_minutos_lanza_excepcion_con_formato_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->horaAMinutos('25:99');
    }
}
