<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Poliza;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log; // Agregar esta línea al inicio del archivo con los otros imports


class PolizaController extends Controller
{
    // Crear una nueva póliza
    public function store(Request $request, $clientId)
    {
        $request->validate([
            'tipo_seguro' => 'required|string|max:255',
            'prima_neta' => 'required|numeric',
            'asegurado' => 'required|string|max:255',
            'vigencia_de' => 'required|date',
            'vigencia_hasta' => 'required|date',
            'periodicidad_pago' => 'required|string|max:255',
            'archivo_pdf' => 'nullable|file|mimes:pdf'
        ]);

        $archivo_pdf_url = null;
        if ($request->hasFile('archivo_pdf')) {
            $archivo_pdf_url = $request->file('archivo_pdf')->store('polizas', 'public');
        }

        $poliza = Poliza::create([
            'tipo_seguro' => $request->tipo_seguro,
            'prima_neta' => $request->prima_neta,
            'asegurado' => $request->asegurado,
            'aseguradora' => 'AXXA ASEGURADORA DE S.A de C.V', // Valor por defecto
            'vigencia_de' => $request->vigencia_de,
            'vigencia_hasta' => $request->vigencia_hasta,
            'periodicidad_pago' => $request->periodicidad_pago,
            'archivo_pdf' => $archivo_pdf_url,
            'clients_id' => $clientId,
        ]);

        return response()->json(['message' => 'Póliza agregada correctamente', 'poliza' => $poliza], 201);
    }


    // Obtener una póliza por ID
    public function show($policyId)
    {
        $policy = Poliza::with('cliente') // Puedes incluir aquí la relación que necesites, como `client` o cualquier otra
            ->find($policyId);

        if (!$policy) {
            return response()->json(['message' => 'Policy not found'], 404);
        }

        return response()->json($policy);
    }

    // Editar una póliza existente
    public function update(Request $request, $clientId, $id)
    {
        $poliza = Poliza::where('clients_id', $clientId)->findOrFail($id);

        $poliza->update($request->only([
            'tipo_seguro',
            'prima_neta',
            'asegurado',
            'vigencia_de',
            'vigencia_hasta',
            'periodicidad_pago'
        ]));

        return response()->json(['message' => 'Póliza actualizada correctamente', 'poliza' => $poliza], 200);
    }

    public function updateWithoutClient(Request $request, $id)
    {
        try {
            $request->validate([
                'tipo_seguro' => 'sometimes|required|string|max:255',
                'prima_neta' => 'sometimes|required|numeric',
                'asegurado' => 'sometimes|required|string|max:255',
                'vigencia_de' => 'sometimes|required|date',
                'vigencia_hasta' => 'sometimes|required|date',
                // 'periodicidad_pago' => 'sometimes|required|string|max:255'
            ]);
    
            $poliza = Poliza::findOrFail($id);
    
            $updateData = $request->only([
                'tipo_seguro',
                'prima_neta',
                'asegurado',
                'vigencia_de',
                'vigencia_hasta',
                // 'periodicidad_pago'
            ]);
    
            // Log para depuración
            Log::info('Update Policy Data:', $updateData);
    
            $poliza->update($updateData);
    
            return response()->json([
                'message' => 'Póliza actualizada correctamente', 
                'poliza' => $poliza
            ], 200);
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Manejar errores de validación
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
    
        } catch (\Exception $e) {
            // Log del error completo
            Log::error('Error updating policy: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
    
            // Devolver detalles del error para depuración
            return response()->json([
                'message' => 'Error al actualizar la póliza',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar una póliza
    public function destroy($clientId, $id)
    {
        $poliza = Poliza::where('clients_id', $clientId)->findOrFail($id);
        $poliza->delete();

        return response()->json(['message' => 'Póliza eliminada correctamente'], 200);
    }

    // Obtener pólizas de un cliente específico
    public function getPolizasByCliente(Request $request, $clientId)
    {
        $limit = $request->query('limit', 5); // límite de elementos por página
        $polizas = Poliza::where('clients_id', $clientId)->paginate($limit);

        return response()->json([
            'polizas' => $polizas->items(),
            'totalPages' => $polizas->lastPage(),
            'currentPage' => $polizas->currentPage(),
            'totalItems' => $polizas->total()
        ]);
    }


    // Obtener todas las pólizas por usuario
    public function getPolizasByUser($user_id)
    {
        $cliente = Client::where('user_id', $user_id)->first();

        if (!$cliente) {
            return response()->json(['error' => 'Cliente no encontrado para este usuario.'], 404);
        }

        $polizas = Poliza::where('clients_id', $cliente->id)->paginate(5); // Cambia '5' al número de registros por página que prefieras
        return response()->json($polizas);
    }

    // Buscar pólizas por usuario con términos de búsqueda
    public function searchPolizasByUser(Request $request, $user_id)
    {
        $searchTerm = $request->query('search', '');
        $polizas = Poliza::where('user_id', $user_id)
            ->where(function ($query) use ($searchTerm) {
                $query->where('tipo_seguro', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('asegurado', 'LIKE', "%{$searchTerm}%");
            })
            ->get();
        return response()->json($polizas);
    }

    // Contar el total de pólizas por cliente
    public function countPolizasByCliente($clientId)
    {
        $total = Poliza::where('clients_id', $clientId)->count();
        return response()->json(['total' => $total]);
    }

    // Descargar todas las pólizas en formato Excel
    public function downloadAllPolicies($userId)
    {
        try {
            $cliente = Client::where('user_id', $userId)->first();
            $policies = $cliente ? Poliza::where('clients_id', $cliente->id)->get() : collect();


            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Pólizas');

            // Encabezados
            $headers = [
                'A1' => 'ID',
                'B1' => 'Tipo de Seguro',
                'C1' => 'Prima Neta',
                'D1' => 'Asegurado',
                'E1' => 'Vigencia Desde',
                'F1' => 'Vigencia Hasta',
                'G1' => 'Periodicidad de Pago',
                'H1' => 'Archivo PDF',
                'I1' => 'ID Cliente'
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }

            // Datos
            $row = 2;
            foreach ($policies as $policy) {
                $sheet->setCellValue('A' . $row, $policy->id);
                $sheet->setCellValue('B' . $row, $policy->tipo_seguro);
                $sheet->setCellValue('C' . $row, $policy->prima_neta);
                $sheet->setCellValue('D' . $row, $policy->asegurado);
                $sheet->setCellValue('E' . $row, $policy->vigencia_de);
                $sheet->setCellValue('F' . $row, $policy->vigencia_hasta);
                $sheet->setCellValue('G' . $row, $policy->periodicidad_pago);
                $sheet->setCellValue('H' . $row, $policy->archivo_pdf);
                $sheet->setCellValue('I' . $row, $policy->clients_id);
                $row++;
            }

            $fileName = 'polizas_' . date('Y-m-d_H-i-s') . '.xlsx';

            return response()->streamDownload(function () use ($spreadsheet) {
                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');
            }, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar el Excel: ' . $e->getMessage()], 500);
        }
    }

    // Obtener todas las pólizas con detalles
    public function getPolizasWithDetails(Request $request)
    {
        $limit = $request->query('limit', 5);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $limit;

        $polizas = Poliza::with('cliente')  // Asegúrate de tener la relación 'cliente' en el modelo
            ->offset($offset)
            ->limit($limit)
            ->get();

        return response()->json($polizas);
    }

    // Obtener todas las pólizas con paginación
    public function getAllPolizas(Request $request)
    {
        $limit = $request->query('limit', 5);
        $page = $request->query('page', 1);
        $offset = ($page - 1) * $limit;

        $polizas = Poliza::offset($offset)->limit($limit)->get();
        $total = Poliza::count();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'polizas' => $polizas,
            'totalPages' => $totalPages,
        ]);
    }
}
