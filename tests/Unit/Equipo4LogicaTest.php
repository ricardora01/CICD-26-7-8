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

    public function test_el_controlador_se_inicializa_correctamente(): void
    {
        $this->assertInstanceOf(Equipo4Controller::class, $this->controller);
    }

    // === SECCION INTEGRANTE 1 (LIDER - 23150306) ===

    public function test_carga_maxima_alumno_regular_sin_reprobadas_es_36(): void
    {
        $this->assertSame(36, $this->controller->calcularCargaMaxima(82.0, 0));
    }

    public function test_cuota_semestral_sin_beca_ni_reprobadas_es_3500(): void
    {
        $this->assertSame(3500.0, $this->controller->calcularCuotaSemestral(false, 0));
    }

    public function test_alumno_regular_sin_adeudo_no_tiene_bloqueos(): void
    {
        $this->assertNull($this->controller->verificarBloqueos(false, 92.0, 0));
    }

    // === SECCION INTEGRANTE 2 (23150315) ===

    // === SECCION INTEGRANTE 3 (23150304) ===

    // === SECCION INTEGRANTE 4 (23150313) ===

    // === SECCION INTEGRANTE 5 (23150364) ===

    // === SECCION INTEGRANTE 6 (23150357) ===
    public function test_alumno_con_adeudo_se_bloquea_aunque_tenga_asistencia_perfecta(): void
    {
        $this->assertSame(
            'El alumno tiene un adeudo pendiente.',
            $this->controller->verificarBloqueos(true, 100.0, 0)
        );
    }
}
