<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Poliza;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('polizas')->get();
        return response()->json([
            'status' => 'success',
            'data' => $clientes,
        ], 200);
    }

    public function show($id)
    {
        $cliente = Cliente::with('polizas')->find($id);

        if (!$cliente) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cliente no encontrado',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $cliente,
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:clientes',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cliente = Cliente::create($request->all());

        return response()->json([
            'status' => 'success',
            'data' => $cliente,
        ], 201);
    }
}
