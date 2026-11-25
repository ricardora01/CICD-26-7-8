<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Equipo3ApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba base de ejemplo. Los integrantes agregarán más aquí.
     */
    public function test_endpoint_requiere_metodo_post(): void
    {
        $response = $this->getJson('/api/equipo-3/validar-vuelo');

        // Espera 405 Method Not Allowed
        $response->assertStatus(405);
    }
}
