<?php

namespace App\Http\Controllers;

use App\Models\Nota;
use Illuminate\Http\Request;

class NotaController extends Controller
{
    // Agregar una nota
    public function agregarNota(Request $request, $clientsId)
    {
        $request->validate([
            'contenido' => 'required|string',
        ]);

        try {
            // Crear la nota
            $nota = Nota::create([
                'clients_id' => $clientsId,
                'contenido' => $request->contenido,
            ]);

            return response()->json([
                'message' => 'Nota agregada exitosamente',
                'nota' => $nota,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al agregar la nota. Por favor, intenta de nuevo.',
            ], 500);
        }
    }

    // Obtener todas las notas de un cliente
    public function obtenerNotasPorCliente($clientsId)
    {
        try {
            $notas = Nota::where('clients_id', $clientsId)->orderBy('created_at', 'desc')->get();

            if ($notas->isEmpty()) {
                return response()->json(['message' => 'No se encontraron notas para este cliente'], 404);
            }

            return response()->json($notas, 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener las notas. Por favor, intenta de nuevo.',
            ], 500);
        }
    }
}
