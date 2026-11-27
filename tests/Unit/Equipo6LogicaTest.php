<?php

namespace Tests\Unit;

use App\Http\Controllers\Equipo6Controller;
use PHPUnit\Framework\TestCase;

/**
 * Pruebas UNITARIAS del Equipo 6.
 * Prueban la lógica del controlador directamente, sin HTTP.
 *
 * Cada integrante pega sus pruebas SOLO dentro de su sección (abajo).
 * No modifiques las secciones de otros integrantes.
 */
class Equipo6LogicaTest extends TestCase
{
    private Equipo6Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new Equipo6Controller;
    }

    // Caso base (líder): todas las unidades aprobadas
    public function test_materia_con_todas_las_unidades_aprobadas_queda_acreditada(): void
    {
        $resultado = $this->controller->evaluar([
            ['calificacion' => 80.0, 'ponderacion' => 50],
            ['calificacion' => 90.0, 'ponderacion' => 50],
        ]);

        $this->assertSame('ACREDITADA', $resultado['estatus']);
        $this->assertSame(85, $resultado['calificacion_final']);
        $this->assertSame(85.0, $resultado['promedio_ponderado']);
        $this->assertSame('Notable', $resultado['nivel_desempeno']);
        $this->assertSame([], $resultado['unidades_pendientes']);
    }

    // ==================================================================
    // INTEGRANTE 1: Kim Du Ri Carrillo Chávez (líder, @KimCarrillo)
    // Matrícula: 23150326
    // Pega tus pruebas debajo de esta línea.

    // [Válido] Una sola unidad reprobada impide acreditar la materia
    public function test_una_unidad_reprobada_deja_la_materia_no_acreditada(): void
    {
        $resultado = $this->controller->evaluar([
            ['calificacion' => 95.0, 'ponderacion' => 50],
            ['calificacion' => 60.0, 'ponderacion' => 50],
        ]);

        $this->assertSame('NO ACREDITADA', $resultado['estatus']);
        $this->assertNull($resultado['calificacion_final']);
        $this->assertSame(77.5, $resultado['promedio_ponderado']);
        $this->assertSame([2], $resultado['unidades_pendientes']);
        $this->assertSame('Desempeño insuficiente', $resultado['nivel_desempeno']);
    }

    // [Límite] 70 acredita y 69.99 no
    public function test_unidad_acredita_exactamente_en_70_y_no_en_69_99(): void
    {
        $this->assertTrue($this->controller->unidadAcreditada(70.0));
        $this->assertFalse($this->controller->unidadAcreditada(69.99));
    }

    // Fin de las pruebas del integrante 1.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 2: Miguel Ángel Díaz Vera (@miguedv7)
    // Matrícula: 23150309
    // Pega tus pruebas debajo de esta línea.

    // [Límite] Frontera entre Notable y Excelente
    public function test_nivel_cambia_de_notable_a_excelente_en_95(): void
    {
        $this->assertSame('Notable', $this->controller->nivelDesempeno(94));
        $this->assertSame('Excelente', $this->controller->nivelDesempeno(95));
        $this->assertSame('Excelente', $this->controller->nivelDesempeno(100));
    }

    // Fin de las pruebas del integrante 2.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 3: Stephanie Madera Sanchez (@fanymadera-sanchez)
    // Matrícula: ________
    // Pega tus pruebas debajo de esta línea.

    // Fin de las pruebas del integrante 3.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 4: Gabriel Porto Camarillo (@porto2315)
    // Matrícula: ________
    // Pega tus pruebas debajo de esta línea.

    // Fin de las pruebas del integrante 4.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 5: Brenda Buenrostro Valencia (@BrendaBV03)
    // Matrícula: ________
    // Pega tus pruebas debajo de esta línea.

    // Fin de las pruebas del integrante 5.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 6: Santiago David Quiroz De León (@santiagodavidquirozdeleon)
    // Matrícula: 23150356
    // Pega tus pruebas debajo de esta línea.
    // [Límite] Fronteras de Suficiente, Bueno y Notable
    public function test_fronteras_de_suficiente_bueno_y_notable(): void
    {
        $this->assertSame('Suficiente', $this->controller->nivelDesempeno(70));
        $this->assertSame('Suficiente', $this->controller->nivelDesempeno(74));
        $this->assertSame('Bueno', $this->controller->nivelDesempeno(75));
        $this->assertSame('Bueno', $this->controller->nivelDesempeno(84));
        $this->assertSame('Notable', $this->controller->nivelDesempeno(85));
        $this->assertSame('Desempeño insuficiente', $this->controller->nivelDesempeno(null));
    }

    // Fin de las pruebas del integrante 6.
    // ==================================================================
}
