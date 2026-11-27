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

    public function test_calcula_ingreso_por_persona(): void
    {
        $resultado = $this->servicio->evaluar(
            92,
            12000,
            4,
            90,
            0
        );

        $this->assertSame(
            3000.0,
            $resultado['ingreso_por_persona']
        );
    }

    public function test_redondea_ingreso_por_persona(): void
    {
        $resultado = $this->servicio->evaluar(
            92,
            10000,
            3,
            90,
            0
        );

        $this->assertSame(
            3333.33,
            $resultado['ingreso_por_persona']
        );
    }

    public function test_rechaza_division_entre_cero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->expectExceptionMessage(
            'Los datos proporcionados no son válidos.'
        );

        $this->servicio->evaluar(
            98,
            6000,
            0,
            95,
            0
        );
    }

    public function test_clasifica_beca_completa(): void
    {
        $resultado = $this->servicio->evaluar(
            98,
            6000,
            4,
            95,
            0
        );

        $this->assertTrue($resultado['elegible']);
        $this->assertSame('completa', $resultado['tipo_beca']);
        $this->assertSame(3000, $resultado['monto_mensual']);
    }

    public function test_clasifica_beca_parcial(): void
    {
        $resultado = $this->servicio->evaluar(
            92,
            10500,
            3,
            95,
            0
        );

        $this->assertTrue($resultado['elegible']);
        $this->assertSame('parcial', $resultado['tipo_beca']);
        $this->assertSame(2000, $resultado['monto_mensual']);
    }

    public function test_clasifica_beca_basica(): void
    {
        $resultado = $this->servicio->evaluar(
            85,
            12000,
            3,
            90,
            0
        );

        $this->assertTrue($resultado['elegible']);
        $this->assertSame('basica', $resultado['tipo_beca']);
        $this->assertSame(1000, $resultado['monto_mensual']);
    }

    public function test_rechaza_beca_por_promedio_insuficiente(): void
    {
        $resultado = $this->servicio->evaluar(
            75,
            6000,
            4,
            95,
            0
        );

        $this->assertFalse($resultado['elegible']);
        $this->assertSame('rechazada', $resultado['tipo_beca']);
        $this->assertSame(0, $resultado['monto_mensual']);
        $this->assertSame([
            'El promedio es inferior a 80.',
        ], $resultado['motivos']);
    }

    public function test_rechaza_beca_por_materias_reprobadas(): void
    {
        $resultado = $this->servicio->evaluar(
            98,
            6000,
            4,
            95,
            2
        );

        $this->assertFalse($resultado['elegible']);
        $this->assertSame('rechazada', $resultado['tipo_beca']);
        $this->assertSame(0, $resultado['monto_mensual']);
        $this->assertSame([
            'El estudiante tiene materias reprobadas.',
        ], $resultado['motivos']);
    }

    public function test_acumula_todos_los_motivos_de_inelegibilidad(): void
    {
        $resultado = $this->servicio->evaluar(
            70,
            24000,
            4,
            75,
            2
        );

        $this->assertFalse($resultado['elegible']);
        $this->assertSame('rechazada', $resultado['tipo_beca']);
        $this->assertSame(0, $resultado['monto_mensual']);
        $this->assertCount(4, $resultado['motivos']);
        $this->assertSame([
            'El promedio es inferior a 80.',
            'La asistencia es inferior al 85%.',
            'El estudiante tiene materias reprobadas.',
            'El ingreso por persona supera el límite permitido.',
        ], $resultado['motivos']);
    }
}
