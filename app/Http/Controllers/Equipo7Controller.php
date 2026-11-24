<?php

namespace App\Http\Controllers;

use App\Http\Requests\Equipo7ValidarHorarioRequest;
use App\Services\Equipo7HorarioService;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Controlador de la API del Equipo 7: Validador de Horario y Carga Académica.
 *
 * @phpstan-import-type MateriaInput from Equipo7HorarioService
 */
class Equipo7Controller extends Controller
{
    public function __construct(
        private readonly Equipo7HorarioService $horarioService
    ) {}

    public function validarHorario(Equipo7ValidarHorarioRequest $request): JsonResponse
    {
        try {
            /** @var array{estatus_alumno: string, materias: list<MateriaInput>} $validated */
            $validated = $request->validated();

            $resultado = $this->horarioService->evaluar(
                $validated['estatus_alumno'],
                $validated['materias']
            );

            return response()->json([
                'equipo' => 7,
                ...$resultado,
            ], 200);
        } catch (Throwable) {
            return response()->json([
                'equipo' => 7,
                'estatus' => 'ERROR',
                'mensaje' => 'No fue posible procesar la validación del horario.',
            ], 500);
        }
    }
}
