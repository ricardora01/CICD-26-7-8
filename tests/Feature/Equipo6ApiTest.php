<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Pruebas FUNCIONALES de la API del Equipo 6.
 * Hacen peticiones reales al endpoint y revisan el código HTTP y el JSON.
 *
 * Cada integrante pega sus pruebas SOLO dentro de su sección (abajo).
 * No modifiques las secciones de otros integrantes.
 */
class Equipo6ApiTest extends TestCase
{
    private const ENDPOINT = '/api/equipo-6/procesar';

    // Caso base (líder): petición válida
    public function test_peticion_valida_devuelve_200_y_estructura_completa(): void
    {
        $response = $this->postJson(self::ENDPOINT, [
            'materia' => 'Seguridad en TI',
            'unidades' => [
                ['calificacion' => 80, 'ponderacion' => 50],
                ['calificacion' => 90, 'ponderacion' => 50],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.materia', 'Seguridad en TI')
            ->assertJsonPath('data.estatus', 'ACREDITADA')
            ->assertJsonPath('data.calificacion_final', 85)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'materia', 'estatus', 'calificacion_final', 'promedio_ponderado',
                    'nivel_desempeno', 'total_unidades', 'unidades_acreditadas',
                    'unidades_pendientes',
                    'detalle' => [['unidad', 'calificacion', 'ponderacion', 'aporte', 'acreditada', 'puntos_faltantes']],
                ],
            ]);
    }

    // ==================================================================
    // INTEGRANTE 1: Kim Du Ri Carrillo Chávez (líder, @KimCarrillo)
    // Matrícula: 23150326
    // Pega tus pruebas debajo de esta línea.

    // Fin de las pruebas del integrante 1.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 2: Miguel Ángel Díaz Vera (@miguedv7)
    // Matrícula: ________
    // Pega tus pruebas debajo de esta línea.

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
    // [Válido] Materia opcional y con acentos
    public function test_materia_es_opcional_y_acepta_acentos(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => [['calificacion' => 90, 'ponderacion' => 100]],
        ])
            ->assertOk()
            ->assertJsonPath('data.materia', null);

        $this->postJson(self::ENDPOINT, [
            'materia' => 'Programación Web',
            'unidades' => [['calificacion' => 90, 'ponderacion' => 100]],
        ])
            ->assertOk()
            ->assertJsonPath('data.materia', 'Programación Web');
    }

    // [Inválido] Materia muy corta, muy larga o que no es texto
    public function test_materia_con_longitud_o_tipo_incorrecto_es_rechazada(): void
    {
        foreach (['ab', str_repeat('a', 101), 12345] as $materia) {
            $this->postJson(self::ENDPOINT, [
                'materia' => $materia,
                'unidades' => [['calificacion' => 80, 'ponderacion' => 100]],
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['materia']);
        }
    }

    // [Abuso] JSON malformado no expone información interna
    public function test_json_malformado_no_expone_informacion_interna(): void
    {
        $response = $this->call(
            'POST',
            self::ENDPOINT,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            '{"unidades": [ {"calificacion": 80,'
        );

        $response->assertStatus(422)->assertJsonPath('success', false);

        foreach (['trace', 'exception', 'vendor', 'Stack'] as $palabra) {
            $this->assertStringNotContainsString($palabra, $response->getContent());
        }
    }

    // Fin de las pruebas del integrante 6.
    // ==================================================================
}
