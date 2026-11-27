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

    // =====================================================================
    // Pruebas de Diego Carmona (22151225)
    // Módulo: Manejo de errores 400 (aborts) y abuso de la API
    // =====================================================================

    /**
     * Itinerario válido que reutilizan mis pruebas: un vuelo nacional MEX -> CUN
     * dentro de 10 días (la regla exige "after:now") y una maleta de 20 kg.
     * El nombre lleva mi matrícula para no chocar con helpers de otros integrantes
     * cuando se fusionen las ramas en este mismo archivo.
     */
    private function itinerarioValido22151225(): array
    {
        $salida = now()->addDays(10)->setTime(8, 0);

        return [
            'passenger_class' => 'Economy',
            'segments' => [
                [
                    'origin' => 'MEX',
                    'destination' => 'CUN',
                    'departure_time' => $salida->format('Y-m-d H:i:s'),
                    'arrival_time' => $salida->copy()->addHours(2)->format('Y-m-d H:i:s'),
                    'type' => 'National',
                ],
            ],
            'baggage' => [
                ['weight_kg' => 20],
            ],
        ];
    }

    public function test_get_con_datos_en_la_url_devuelve_405_e_indica_que_solo_se_permite_post(): void
    {
        // Intento de abuso: mandar el itinerario completo por query string con GET.
        $url = '/api/equipo-3/validar-vuelo?'.http_build_query($this->itinerarioValido22151225());

        $response = $this->getJson($url);

        $response->assertStatus(405);
        // Laravel avisa en el encabezado "Allow" cuál es el único método aceptado.
        $response->assertHeader('Allow', 'POST');
        // Nunca debe llegar a generar una cotización.
        $response->assertJsonMissingPath('status');
        $response->assertJsonMissingPath('data');
    }

    public function test_metodos_put_patch_y_delete_tambien_devuelven_405(): void
    {
        foreach (['PUT', 'PATCH', 'DELETE'] as $metodo) {
            $response = $this->json($metodo, '/api/equipo-3/validar-vuelo', $this->itinerarioValido22151225());

            $response->assertStatus(405);
            $response->assertHeader('Allow', 'POST');
            $response->assertJsonMissingPath('data');
        }
    }

    public function test_peso_de_maleta_enviado_como_texto_devuelve_422(): void
    {
        $payload = $this->itinerarioValido22151225();
        $payload['baggage'][0]['weight_kg'] = 'veinte kilos';

        $response = $this->postJson('/api/equipo-3/validar-vuelo', $payload);

        $response->assertStatus(422);
        // El error apunta exactamente al campo culpable: la primera maleta.
        $response->assertJsonValidationErrors(['baggage.0.weight_kg']);
        $response->assertJsonMissingPath('data');
    }

    public function test_peso_de_maleta_enviado_como_arreglo_devuelve_422_y_no_un_error_500(): void
    {
        // Confusión de tipos: en lugar de un número mandan un arreglo.
        // Si no se validara, la comparación "$weight > 23" podría romper la API (500).
        $payload = $this->itinerarioValido22151225();
        $payload['baggage'][0]['weight_kg'] = [20, 30];

        $response = $this->postJson('/api/equipo-3/validar-vuelo', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['baggage.0.weight_kg']);
    }

    public function test_campos_extra_maliciosos_son_ignorados_y_no_alteran_la_cotizacion(): void
    {
        $payload = $this->itinerarioValido22151225();
        // Maleta 1: 25 kg -> 2 kg de exceso x $150 = $300 (es la maleta gratis de Economy).
        // Maleta 2: 10 kg -> maleta extra = $800.
        $payload['baggage'] = [
            ['weight_kg' => 25, 'cost' => 0, 'reason' => '<script>alert("xss")</script>'],
            ['weight_kg' => 10, 'cost' => 0],
        ];
        // Campos que el cliente NO debería poder controlar.
        $payload['total_to_pay'] = 0;
        $payload['additional_charges'] = -1100;
        $payload['discount'] = 100;
        $payload['is_admin'] = true;
        $payload['role'] = "admin'; DROP TABLE users; --";

        $response = $this->postJson('/api/equipo-3/validar-vuelo', $payload);

        // La API no explota: responde 200 y calcula todo del lado del servidor.
        $response->assertStatus(200);
        $response->assertJsonPath('status', 'success');
        $response->assertJsonPath('data.additional_charges', 0);
        $response->assertJsonPath('data.baggage_breakdown.0.cost', 300);
        $response->assertJsonPath('data.baggage_breakdown.1.cost', 800);
        $response->assertJsonPath('data.total_to_pay', 1100);

        // Los campos inyectados no aparecen en la respuesta.
        $response->assertJsonMissingPath('data.discount');
        $response->assertJsonMissingPath('data.is_admin');
        $response->assertJsonMissingPath('data.role');
        $response->assertDontSee('<script>', false);
        $response->assertDontSee('DROP TABLE', false);
    }

    public function test_error_de_negocio_responde_400_limpio_sin_exponer_informacion_interna(): void
    {
        // Forzamos modo debug para demostrar que, aun así, el abort(400)
        // no filtra la traza, archivos ni líneas del código.
        config(['app.debug' => true]);

        $payload = $this->itinerarioValido22151225();
        $llegada = now()->addDays(10)->setTime(10, 0);
        // Segundo vuelo sale de GDL, pero el primero aterrizó en CUN: conexión imposible.
        $payload['segments'][] = [
            'origin' => 'GDL',
            'destination' => 'MTY',
            'departure_time' => $llegada->copy()->addHours(3)->format('Y-m-d H:i:s'),
            'arrival_time' => $llegada->copy()->addHours(5)->format('Y-m-d H:i:s'),
            'type' => 'National',
        ];

        $response = $this->postJson('/api/equipo-3/validar-vuelo', $payload);

        $response->assertStatus(400);
        $response->assertJsonPath('status', 'error');
        $response->assertJsonPath('error_type', 'BUSINESS_RULE_VIOLATION');
        // Solo 3 claves: status, error_type y message. Nada más.
        $response->assertJsonCount(3);
        $response->assertJsonMissingPath('exception');
        $response->assertJsonMissingPath('file');
        $response->assertJsonMissingPath('line');
        $response->assertJsonMissingPath('trace');
        $response->assertJsonMissingPath('data');
    }
}
