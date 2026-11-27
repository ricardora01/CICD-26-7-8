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

    // [Inválido] Calificación con texto
    public function test_calificacion_con_texto_es_rechazada_con_422(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => [['calificacion' => 'abc', 'ponderacion' => 100]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['unidades.0.calificacion']);
    }

    // [Abuso] Campos extra dentro de una unidad
    public function test_unidad_con_campo_extra_es_rechazada(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => [['calificacion' => 80, 'ponderacion' => 100, 'admin' => true]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['unidades.0']);
    }

    // Fin de las pruebas del integrante 1.
    // ==================================================================

    // ==================================================================
    // INTEGRANTE 2: Miguel Ángel Díaz Vera (@miguedv7)
    // Matrícula: 23150309
    // Pega tus pruebas debajo de esta línea.
    // [Válido] Tres unidades aprobadas con su aporte
    public function test_tres_unidades_aprobadas_calculan_aporte_y_nivel(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => [
                ['calificacion' => 80, 'ponderacion' => 30],
                ['calificacion' => 75, 'ponderacion' => 30],
                ['calificacion' => 82, 'ponderacion' => 40],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.estatus', 'ACREDITADA')
            ->assertJsonPath('data.promedio_ponderado', 79.3)
            ->assertJsonPath('data.calificacion_final', 79)
            ->assertJsonPath('data.nivel_desempeno', 'Bueno')
            ->assertJsonPath('data.detalle.0.aporte', 24)
            ->assertJsonPath('data.detalle.1.aporte', 22.5)
            ->assertJsonPath('data.detalle.2.aporte', 32.8);
    }

    // [Inválido] Calificaciones fuera de 0 a 100
    public function test_calificaciones_fuera_de_rango_son_rechazadas(): void
    {
        foreach ([-1, 101] as $calificacion) {
            $this->postJson(self::ENDPOINT, [
                'unidades' => [['calificacion' => $calificacion, 'ponderacion' => 100]],
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['unidades.0.calificacion' => 'La calificación debe estar entre 0 y 100.']);
        }
    }

    // [Abuso] Ponderaciones que no suman 100
    public function test_ponderaciones_que_suman_99_son_rechazadas(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => [
                ['calificacion' => 80, 'ponderacion' => 50],
                ['calificacion' => 80, 'ponderacion' => 49],
            ],
        ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('errors.unidades.0', 'La suma de las ponderaciones debe ser exactamente 100. Suma recibida: 99.');
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
    // Matrícula: 23150310
    // Pega tus pruebas debajo de esta línea.

    // [Límite] Calificaciones extremas 0 y 100
    public function test_calificaciones_extremas_0_y_100(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => [['calificacion' => 100, 'ponderacion' => 100]],
        ])
            ->assertOk()
            ->assertJsonPath('data.calificacion_final', 100)
            ->assertJsonPath('data.nivel_desempeno', 'Excelente');

        $this->postJson(self::ENDPOINT, [
            'unidades' => [['calificacion' => 0, 'ponderacion' => 100]],
        ])
            ->assertOk()
            ->assertJsonPath('data.estatus', 'NO ACREDITADA')
            ->assertJsonPath('data.detalle.0.puntos_faltantes', 70);
    }

    // [Inválido] Ponderación 0 o mayor a 100
    public function test_ponderacion_cero_o_mayor_a_100_es_rechazada(): void
    {
        foreach ([0, 101] as $ponderacion) {
            $this->postJson(self::ENDPOINT, [
                'unidades' => [['calificacion' => 80, 'ponderacion' => $ponderacion]],
            ])
                ->assertStatus(422)
                ->assertJsonValidationErrors(['unidades.0.ponderacion' => 'La ponderación debe estar entre 1 y 100.']);
        }
    }

    // [Abuso] Objeto en lugar de lista
    public function test_unidades_como_objeto_en_lugar_de_lista_son_rechazadas(): void
    {
        $this->postJson(self::ENDPOINT, [
            'unidades' => ['primera' => ['calificacion' => 80, 'ponderacion' => 100]],
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.unidades.0', 'Las unidades deben enviarse como una lista, no como un objeto.');
    }

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
