<?php
// app/Http/Controllers/PagoController.php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Models\Poliza;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function obtenerPagosPorPoliza(Request $request, $polizaId)
    {
        try {
            $limit = $request->query('limit', 5);
            $page = $request->query('page', 1);

            $poliza = Poliza::with('cliente')->findOrFail($polizaId);
            
            // Verificar y generar pagos si no existen
            if (!Pago::where('poliza_id', $polizaId)->exists()) {
                $this->generarPagosAutomaticos($poliza);
            }

            $pagos = Pago::with('poliza')
                ->where('poliza_id', $polizaId)
                ->orderBy('fecha_pago')
                ->paginate($limit);

            return response()->json([
                'poliza' => [
                    'asegurado' => $poliza->asegurado,
                    'prima_neta' => $poliza->prima_neta,
                    'periodicidad_pago' => $poliza->periodicidad_pago,
                    'vigencia_de' => $poliza->vigencia_de,
                    'vigencia_hasta' => $poliza->vigencia_hasta,
                ],
                'pagos' => $pagos->items(),
                'total_pages' => ceil($pagos->total() / $limit),
                'current_page' => $pagos->currentPage(),
                'total' => $pagos->total()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerPagosPorUsuario(Request $request, $userId)
    {
        try {
            $limit = $request->query('limit', 5);
            $page = $request->query('page', 1);

            // Obtener todas las pólizas del usuario a través de la relación
            $polizas = Poliza::whereHas('cliente', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })->get();

            // Generar pagos para cada póliza si no existen
            foreach ($polizas as $poliza) {
                if (!Pago::where('poliza_id', $poliza->id)->exists()) {
                    $this->generarPagosAutomaticos($poliza);
                }
            }

            // Obtener pagos con información relacionada
            $pagos = Pago::join('polizas', 'pagos.poliza_id', '=', 'polizas.id')
                ->join('clients', 'polizas.clients_id', '=', 'clients.id')
                ->where('clients.user_id', $userId)
                ->select([
                    'pagos.id',
                    'polizas.asegurado',
                    'pagos.monto',
                    'pagos.fecha_pago',
                    'pagos.status',
                    'pagos.created_at as emision_pago',
                    'polizas.id as poliza_id',
                    'polizas.prima_neta',
                    'polizas.periodicidad_pago',
                    'clients.nombre as cliente_nombre',
                    'clients.apellidos as cliente_apellidos'
                ])
                ->orderBy('pagos.fecha_pago', 'desc')
                ->paginate($limit);

            return response()->json([
                'pagos' => $pagos->items(),
                'total_pages' => ceil($pagos->total() / $limit),
                'current_page' => $pagos->currentPage(),
                'total' => $pagos->total()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener los pagos del usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerPagosPorPolizaPorID(Request $request, $polizaId)
    {
        try {
            $limit = $request->query('limit', 5);
            $page = $request->query('page', 1);

            $poliza = Poliza::with('cliente')->findOrFail($polizaId);
            
            // Verificar y generar pagos si no existen
            if (!Pago::where('poliza_id', $polizaId)->exists()) {
                $this->generarPagosAutomaticos($poliza);
            }

            $pagos = Pago::where('poliza_id', $polizaId)
                ->orderBy('fecha_pago')
                ->paginate($limit);

            // Calcula el total pendiente y pagado
            $totalPendiente = Pago::where('poliza_id', $polizaId)
                ->where('status', 'pendiente')
                ->sum('monto');

            $totalPagado = Pago::where('poliza_id', $polizaId)
                ->where('status', 'pagado')
                ->sum('monto');

            return response()->json([
                'poliza' => [
                    'id' => $poliza->id,
                    'asegurado' => $poliza->asegurado,
                    'prima_neta' => $poliza->prima_neta,
                    'periodicidad_pago' => $poliza->periodicidad_pago,
                    'vigencia_de' => $poliza->vigencia_de,
                    'vigencia_hasta' => $poliza->vigencia_hasta,
                    'cliente' => [
                        'nombre' => $poliza->cliente->nombre,
                        'apellidos' => $poliza->cliente->apellidos,
                    ]
                ],
                'resumen_pagos' => [
                    'total_pendiente' => $totalPendiente,
                    'total_pagado' => $totalPagado,
                    'total' => $poliza->prima_neta
                ],
                'pagos' => $pagos->items(),
                'total_pages' => ceil($pagos->total() / $limit)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la póliza y pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function generarPagosAutomaticos(Poliza $poliza)
    {
        try {
            DB::beginTransaction();

            $primaNeta = $poliza->prima_neta;
            $vigenciaDe = Carbon::parse($poliza->vigencia_de);
            $vigenciaHasta = Carbon::parse($poliza->vigencia_hasta);
            $periodicidadPago = $poliza->periodicidad_pago;

            $pagos = [];
            $fechaActual = $vigenciaDe->copy();
            $intervalo = $this->getIntervalo($periodicidadPago);

            if ($periodicidadPago === 'anual') {
                $pagos[] = [
                    'monto' => $primaNeta,
                    'fecha_pago' => $vigenciaHasta->toDateString(),
                    'poliza_id' => $poliza->id,
                    'status' => 'pendiente',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            } else {
                $totalPagos = $this->getTotalPagos($periodicidadPago);
                $montoPorPago = $primaNeta / $totalPagos;

                while ($fechaActual->lte($vigenciaHasta)) {
                    $pagos[] = [
                        'monto' => round($montoPorPago, 2),
                        'fecha_pago' => $fechaActual->toDateString(),
                        'poliza_id' => $poliza->id,
                        'status' => 'pendiente',
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $fechaActual->addDays($intervalo);
                }
            }

            foreach (array_chunk($pagos, 100) as $chunk) {
                Pago::insert($chunk);
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getIntervalo($periodicidad)
    {
        return match ($periodicidad) {
            'mensual' => 30,
            'quincenal' => 15,
            'semanal' => 7,
            'anual' => 365,
            default => throw new \InvalidArgumentException('Periodicidad de pago no válida')
        };
    }

    private function getTotalPagos($periodicidad)
    {
        return match ($periodicidad) {
            'mensual' => 12,
            'quincenal' => 24,
            'semanal' => 52,
            'anual' => 1,
            default => throw new \InvalidArgumentException('Periodicidad de pago no válida')
        };
    }
}