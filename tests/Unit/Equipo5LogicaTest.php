<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Equipo5BecaService;
use PHPUnit\Framework\TestCase;

class Equipo5LogicaTest extends TestCase
{
    protected Equipo5BecaService $servicio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->servicio = new Equipo5BecaService;
    }

    public function test_el_servicio_devuelve_la_estructura_esperada(): void
    {
        $resultado = $this->servicio->evaluar(
            98,
            6000,
            4,
            95,
            0
        );

        $this->assertSame([
            'elegible',
            'tipo_beca',
            'monto_mensual',
            'ingreso_por_persona',
            'motivos',
        ], array_keys($resultado));

        $this->assertIsBool($resultado['elegible']);
        $this->assertIsString($resultado['tipo_beca']);
        $this->assertIsInt($resultado['monto_mensual']);
        $this->assertIsFloat($resultado['ingreso_por_persona']);
        $this->assertIsArray($resultado['motivos']);
    }
}
