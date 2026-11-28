<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Controllers\Equipo4Controller;
use PHPUnit\Framework\TestCase;

class Equipo4LogicaTest extends TestCase
{
    private Equipo4Controller $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new Equipo4Controller;
    }

    // === SECCION INTEGRANTE 2 (23150315) ===

    public function test_alumno_excelente_sin_reprobadas_puede_inscribir_42_creditos(): void
    {
        $this->assertSame(42, $this->controller->calcularCargaMaxima(94.0, 0));
    }

    public function test_alumno_con_beca_conserva_el_beneficio_si_cumple_los_requisitos(): void
    {
        $this->assertTrue($this->controller->evaluarConservacionBeca(true, 92.0, 0, 95.0));
    }

    public function test_cuota_semestral_con_beca_y_sin_reprobadas_es_1750(): void
    {
        $this->assertSame(1750.0, $this->controller->calcularCuotaSemestral(true, 0));
    }

    // === SECCION INTEGRANTE 3 ===

    // === SECCION INTEGRANTE 4 ===

    // === SECCION INTEGRANTE 5 ===

    // === SECCION INTEGRANTE 6 ===
}
