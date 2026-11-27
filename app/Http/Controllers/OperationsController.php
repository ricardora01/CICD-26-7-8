<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    private const MAX_BYTES = 2048;

    private const CAMPOS_PERMITIDOS = [
        'tipo_espacio',
        'personas',
        'requiere_proyector',
        'requiere_computadoras',
        'requiere_accesibilidad',
        'espacio',
    ];

    private const LIMITE_POR_TIPO = [
        'sala' => 60,
        'laboratorio' => 40,
        'auditorio' => 500,
    ];

    public function addition(int $a, int $b): int
    {
        return $a + $b;
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'length' => ['sometimes', 'integer', 'min:12', 'max:128'],
        ]);

        $length = $validated['length'] ?? 20;

        $password = Str::password(
            length: $length,
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false
        );

        return response()->json([
            'success' => true,
            'password' => $password,
            'length' => strlen($password),
        ]);
    }

    public function evaluarEspacio(Request $request): JsonResponse
    {
        $rechazar = $this->checarTamano($request);
        if ($rechazar !== null) {
            return $rechazar;
        }

        $datos = $this->checarDatos($request);

        return response()->json($this->evaluar($datos));
    }

    private function checarTamano(Request $request): ?JsonResponse
    {
        if (! $request->isJson()) {
            return response()->json(['message' => 'El formato no es JSON.'], 415);
        }

        $contenido = $request->getContent();

        if (strlen($contenido) > self::MAX_BYTES) {
            return response()->json(['message' => 'El tamaño excede el permitido.'], 413);
        }

        $decodificado = json_decode($contenido, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decodificado)) {
            return response()->json(['message' => 'El cuerpo de la solicitud no es un JSON válido.'], 422);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function checarDatos(Request $request): array
    {
        $datos = $request->json()->all();
        $validarInt = fn ($campo, $valor, $fail) => is_int($valor) || $fail("{$campo} debe ser un número entero.");
        $validarBool = fn ($campo, $valor, $fail) => is_bool($valor) || $fail("{$campo} debe ser true o false.");

        $validarDatos = Validator::make($datos, [
            'tipo_espacio' => ['bail', 'required', Rule::in(array_keys(self::LIMITE_POR_TIPO))],
            'personas' => ['bail', 'required', 'integer', $validarInt, 'between:1,500'],
            'requiere_proyector' => ['bail', 'required', $validarBool],
            'requiere_computadoras' => ['bail', 'required', $validarBool],
            'requiere_accesibilidad' => ['bail', 'required', $validarBool],
            'espacio' => ['bail', 'required', 'array:capacidad,tiene_proyector,computadoras_disponibles,es_accesible'],
            'espacio.capacidad' => ['bail', 'required', 'integer', $validarInt, 'between:1,10000'],
            'espacio.tiene_proyector' => ['bail', 'required', $validarBool],
            'espacio.computadoras_disponibles' => ['bail', 'required', 'integer', $validarInt, 'between:0,10000'],
            'espacio.es_accesible' => ['bail', 'required', $validarBool],
        ]);

        $validarDatos->after(function () use ($validarDatos, $datos) {
            foreach (array_diff(array_keys($datos), self::CAMPOS_PERMITIDOS) as $campo) {
                $validarDatos->errors()->add((string) $campo, 'Este campo no está permitido.');
            }
        });

        return $validarDatos->validate();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function evaluar(array $datos): array
    {
        $tipo = $datos['tipo_espacio'];
        $personas = $datos['personas'];
        $espacio = $datos['espacio'];
        $limite = self::LIMITE_POR_TIPO[$tipo];
        $computadoras = $espacio['computadoras_disponibles'];

        $reglas = [
            [
                'aplica' => true,
                'cumple' => $espacio['capacidad'] >= $personas,
                'ok' => "Capacidad suficiente: {$espacio['capacidad']} lugares para {$personas} asistentes.",
                'falla' => "Capacidad insuficiente: el espacio admite {$espacio['capacidad']} personas y se requieren {$personas}.",
            ],
            [
                'aplica' => $datos['requiere_proyector'],
                'cumple' => $espacio['tiene_proyector'],
                'ok' => 'Cuenta con proyector.',
                'falla' => 'Se requiere proyector y el espacio no cuenta con uno.',
            ],
            [
                'aplica' => $datos['requiere_computadoras'],
                'cumple' => $computadoras >= $personas,
                'ok' => "Computadoras suficientes: {$computadoras} para {$personas} asistentes.",
                'falla' => "Computadoras insuficientes: hay {$computadoras} y se requieren {$personas}.",
            ],
            [
                'aplica' => $datos['requiere_accesibilidad'],
                'cumple' => $espacio['es_accesible'],
                'ok' => 'El espacio es accesible.',
                'falla' => 'Se requiere accesibilidad y el espacio no es accesible.',
            ],
            [
                'aplica' => true,
                'cumple' => $personas <= $limite,
                'ok' => "Un espacio tipo {$tipo} es adecuado para {$personas} personas.",
                'falla' => "Un espacio tipo {$tipo} admite actividades de hasta {$limite} personas.",
            ],
            [
                'aplica' => $datos['requiere_computadoras'],
                'cumple' => $tipo === 'laboratorio',
                'ok' => 'Es un laboratorio, adecuado para actividades con computadoras.',
                'falla' => 'Las actividades que requieren computadoras deben realizarse en un laboratorio.',
            ],
        ];

        $cumplidos = [];
        $faltantes = [];

        foreach ($reglas as $regla) {
            if (! $regla['aplica']) {
                continue;
            }

            if ($regla['cumple']) {
                $cumplidos[] = $regla['ok'];
            } else {
                $faltantes[] = $regla['falla'];
            }
        }

        return [
            'dictamen' => $faltantes === [] ? 'ADECUADO' : 'NO_ADECUADO',
            'tipo_espacio' => $tipo,
            'personas' => $personas,
            'capacidad' => $espacio['capacidad'],
            'requisitos_cumplidos' => $cumplidos,
            'requisitos_faltantes' => $faltantes,
        ];
    }
}
