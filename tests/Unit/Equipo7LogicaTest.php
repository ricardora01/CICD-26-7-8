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

    // 22151231
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
}
