<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Poliza;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class polizaController extends Controller
{
    //retornar en json

    public function index()
    {
        $polizas = Poliza::all();

        if (count($polizas) == 0) {
            return (object) [
                'data' => [],
                'status' => 'error',
                'message' => 'No hay polizas'
            ];
        }

        return json_encode([
            'data' => $polizas,
            'status' => 'success',
            'message' => 'Polizas encontradas'
        ]);
    }

    public function show($id)
    {
        $poliza = Poliza::find($id);

        if (!$poliza) {
            return (object) [
                'data' => [],
                'status' => 'error',
                'message' => 'No se encontro la poliza'
            ];
        }

        return (object) [
            'data' => $poliza,
            'status' => 'success',
            'message' => 'Poliza encontrada'
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'cliente_id' => 'required|exists:clientes,id' // Verifica que el cliente existe
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $poliza = new Poliza();
        $poliza->nombre = $request->nombre;
        $poliza->direccion = $request->direccion;
        $poliza->telefono = $request->telefono;
        $poliza->email = $request->email;
        $poliza->cliente_id = $request->cliente_id; // Asocia la póliza al cliente
        $poliza->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Poliza creada y asociada al cliente',
            'data' => $poliza
        ], 201);
    }


    public function update($id, Request $request)
    {
        $poliza = Poliza::find($id);

        if (!$poliza) {
            return (object) [
                'data' => [],
                'status' => 'error',
                'message' => 'No se encontro la poliza'
            ];
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'required',
            'max:255',
            'telefono' => 'required',
            'max:255',
            'direccion' => 'required',
            'max:255',
            'email' => 'required|email',
            'max:255'
        ]);

        if ($validator->fails()) {
            return (object) [
                'data' => [],
                'status' => 'error',
                'message' => 'Error al actualizar poliza'
            ];
        }

        $poliza->nombre = $request->nombre;
        $poliza->telefono = $request->telefono;
        $poliza->direccion = $request->direccion;
        $poliza->email = $request->email;
        $poliza->save();

        return (object) [
            'data' => $poliza,
            'status' => 'success',
            'message' => 'Poliza actualizada'
        ];
    }

    public function destroy($id)
    {
        $poliza = Poliza::find($id);

        if (!$poliza) {
            return (object) [
                'data' => [],
                'status' => 'error',
                'message' => 'No se encontro la poliza'
            ];
        }

        $poliza->delete();

        return (object) [
            'data' => $poliza,
            'status' => 'success',
            'message' => 'Poliza eliminada'
        ];
    }
}
