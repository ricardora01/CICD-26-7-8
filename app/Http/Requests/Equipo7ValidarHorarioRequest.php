<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class Equipo7ValidarHorarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'estatus_alumno' => ['required', 'string', 'in:regular,irregular'],
            'materias' => ['required', 'array', 'min:1', 'max:8'],
            'materias.*.clave' => ['required', 'string', 'min:3', 'max:20', 'regex:/^[A-Za-z0-9-]+$/'],
            'materias.*.creditos' => ['required', 'integer', 'min:2', 'max:10'],
            'materias.*.tipo' => ['required', 'string', 'in:obligatoria,optativa,laboratorio'],
            'materias.*.horarios' => ['required', 'array', 'min:1', 'max:5'],
            'materias.*.horarios.*.dia' => ['required', 'string', 'in:lunes,martes,miercoles,jueves,viernes,sabado'],
            'materias.*.horarios.*.hora_inicio' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'materias.*.horarios.*.hora_fin' => ['required', 'string', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string, mixed> $input */
            $input = $this->all();

            $camposRaizPermitidos = ['estatus_alumno', 'materias'];
            $camposRaizExtra = array_diff(array_keys($input), $camposRaizPermitidos);
            if ($camposRaizExtra !== []) {
                $validator->errors()->add(
                    'payload',
                    'Se enviaron campos no permitidos en la solicitud.'
                );
            }

            if (! isset($input['materias']) || ! is_array($input['materias'])) {
                return;
            }

            $camposMateriaPermitidos = ['clave', 'creditos', 'tipo', 'horarios'];
            $camposHorarioPermitidos = ['dia', 'hora_inicio', 'hora_fin'];

            foreach ($input['materias'] as $indexMateria => $materia) {
                if (! is_array($materia)) {
                    continue;
                }

                $extraMateria = array_diff(array_keys($materia), $camposMateriaPermitidos);
                if ($extraMateria !== []) {
                    $validator->errors()->add(
                        "materias.{$indexMateria}",
                        'La materia contiene campos no permitidos.'
                    );
                }

                if (! isset($materia['horarios']) || ! is_array($materia['horarios'])) {
                    continue;
                }

                foreach ($materia['horarios'] as $indexHorario => $horario) {
                    if (! is_array($horario)) {
                        continue;
                    }

                    $extraHorario = array_diff(array_keys($horario), $camposHorarioPermitidos);
                    if ($extraHorario !== []) {
                        $validator->errors()->add(
                            "materias.{$indexMateria}.horarios.{$indexHorario}",
                            'El bloque de horario contiene campos no permitidos.'
                        );
                    }
                }
            }
        });
    }
}
