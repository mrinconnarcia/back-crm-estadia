<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Requests\ClientRequest;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    public function store(ClientRequest $request, $userId)
    {
        $client = Client::create($request->validated() + ['user_id' => $userId]);
        return response()->json(['message' => 'Cliente agregado exitosamente', 'client' => $client], 201);
    }

    public function update(ClientRequest $request, $userId, $id)
    {
        $client = Client::where('user_id', $userId)->findOrFail($id);
        $client->update($request->validated());
        return response()->json([
            'message' => 'Cliente actualizado exitosamente',
            'client' => $client
        ]);
    }

    public function index($userId, Request $request)
    {
        $clients = Client::where('user_id', $userId)
            ->paginate($request->query('limit', 5));
        return response()->json($clients);
    }

    public function show($userId, $id)
    {
        $client = Client::where('user_id', $userId)->findOrFail($id);
        return response()->json($client);
    }

    public function downloadAllClients($userId)
    {
        try {
            $clients = Client::where('user_id', $userId)->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Clientes');

            // Encabezados
            $headers = [
                'A1' => 'ID',
                'B1' => 'Nombre',
                'C1' => 'Apellidos',
                'D1' => 'Teléfono',
                'E1' => 'Correo',
                'F1' => 'Fecha de Nacimiento',
                'G1' => 'ID Usuario'
            ];

            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
                $sheet->getStyle($cell)->getFont()->setBold(true);
            }

            // Datos
            $row = 2;
            foreach ($clients as $client) {
                $sheet->setCellValue('A' . $row, $client->id);
                $sheet->setCellValue('B' . $row, $client->nombre);
                $sheet->setCellValue('C' . $row, $client->apellidos);
                $sheet->setCellValue('D' . $row, $client->telefono);
                $sheet->setCellValue('E' . $row, $client->correo);
                $sheet->setCellValue('F' . $row, $client->fecha_nacimiento);
                $sheet->setCellValue('G' . $row, $client->user_id);
                $row++;
            }

            $fileName = 'clientes_' . date('Y-m-d_H-i-s') . '.xlsx';

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
}
