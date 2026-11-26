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

    // === SECCION INTEGRANTE 2 (23150315) ===

    // === SECCION INTEGRANTE 3 (23150304) ===

    // === SECCION INTEGRANTE 4 (23150313) ===

    // === SECCION INTEGRANTE 5 (23150364) ===

    // === SECCION INTEGRANTE 6 (23150357) ===
}
