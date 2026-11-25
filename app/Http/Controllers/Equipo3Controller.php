<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Equipo3Controller extends Controller
{
    public function procesarVuelo(Request $request): JsonResponse
    {
        // 1. Validaciones Estrictas
        $validated = $request->validate([
            'passenger_class' => 'required|string|in:Economy,Executive',
            'segments' => 'required|array|min:1',
            'segments.*.origin' => 'required|string|size:3',
            'segments.*.destination' => 'required|string|size:3',
            'segments.*.departure_time' => 'required|date_format:Y-m-d H:i:s|after:now',
            'segments.*.arrival_time' => 'required|date_format:Y-m-d H:i:s|after:segments.*.departure_time',
            'segments.*.type' => 'required|string|in:National,International',
            'baggage' => 'nullable|array',
            'baggage.*.weight_kg' => 'required|numeric|min:0',
        ]);

        // 2. Delegar a los métodos privados
        $this->validarConexiones($validated['segments']);

        $baggageData = $this->calcularEquipaje($validated['baggage'] ?? [], $validated['passenger_class']);
        $additionalCharges = $this->calcularFatiga($validated['segments']);

        // 3. Cálculo Final
        $totalToPay = $baggageData['cost'] + $additionalCharges;

        return response()->json([
            'status' => 'success',
            'message' => 'Itinerario válido. Cotización generada.',
            'data' => [
                'is_valid' => true,
                'total_layovers' => count($validated['segments']) - 1,
                'baggage_breakdown' => $baggageData['breakdown'],
                'additional_charges' => $additionalCharges,
                'total_to_pay' => round($totalToPay, 2),
            ],
        ], 200);
    }

    // --- MÓDULO 1: CONEXIONES Y TIEMPOS ---
    private function validarConexiones(array $segments): void
    {
        for ($i = 1; $i < count($segments); $i++) {
            $prevFlight = $segments[$i - 1];
            $currentFlight = $segments[$i];

            if ($prevFlight['destination'] !== $currentFlight['origin']) {
                abort(response()->json([
                    'status' => 'error',
                    'error_type' => 'BUSINESS_RULE_VIOLATION',
                    'message' => 'Error de conexión: El destino del vuelo anterior no coincide con el origen del siguiente.',
                ], 400));
            }

            $arrivalTime = Carbon::parse($prevFlight['arrival_time']);
            $departureTime = Carbon::parse($currentFlight['departure_time']);
            $minutesBetween = $arrivalTime->diffInMinutes($departureTime);

            $isInternational = ($prevFlight['type'] === 'International' || $currentFlight['type'] === 'International');
            $minConnectionTime = $isInternational ? 90 : 45;

            if ($minutesBetween < $minConnectionTime) {
                abort(response()->json([
                    'status' => 'error',
                    'error_type' => 'BUSINESS_RULE_VIOLATION',
                    'message' => "Tiempo de conexión insuficiente. Mínimo requerido: {$minConnectionTime} min. Tiempo actual: {$minutesBetween} min.",
                ], 400));
            }
        }
    }

    // --- MÓDULO 2: EQUIPAJE Y PESO ---
    private function calcularEquipaje(array $baggage, string $passengerClass): array
    {
        $baggageCost = 0.00;
        $baggageBreakdown = [];
        $freeBags = ($passengerClass === 'Executive') ? 3 : 1;

        foreach ($baggage as $index => $bag) {
            $bagNumber = $index + 1;
            $weight = $bag['weight_kg'];
            $cost = 0.00;
            $reason = 'Incluida (Maleta gratis)';

            if ($bagNumber > $freeBags) {
                $cost += 800;
                $reason = 'Cobro por maleta extra ($800)';
            }

            if ($weight > 23) {
                $excessWeight = ceil($weight - 23);
                $cost += ($excessWeight * 150);
                $reason .= $cost > 0 && $bagNumber <= $freeBags ? "Exceso de peso ({$excessWeight}kg)" : " + Exceso de peso ({$excessWeight}kg)";
            }

            $baggageCost += $cost;
            $baggageBreakdown[] = [
                'bag_index' => $bagNumber,
                'weight' => $weight,
                'cost' => $cost,
                'reason' => $reason,
            ];
        }

        return ['cost' => $baggageCost, 'breakdown' => $baggageBreakdown];
    }

    // --- MÓDULO 3: FATIGA Y ESCALAS ---
    private function calcularFatiga(array $segments): float
    {
        // Más de 2 escalas = 3 o más vuelos
        return (count($segments) >= 3) ? 1000.00 : 0.00;
    }
}
