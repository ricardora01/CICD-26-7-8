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

    public function test_hora_a_minutos_lanza_excepcion_con_formato_invalido(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->horaAMinutos('25:99');
    }
}
