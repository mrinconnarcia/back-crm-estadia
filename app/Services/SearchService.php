<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Poliza;

class SearchService
{
    public function searchClients($userId, $searchTerm, $page = 1, $limit = 5)
    {
        $query = Client::where('user_id', $userId);
        
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('nombre', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('apellidos', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('correo', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('telefono', 'LIKE', "%{$searchTerm}%");
            });
        }

        $clients = $query->paginate($limit, ['*'], 'page', $page);

        return [
            'clients' => $clients->items(),
            'totalPages' => $clients->lastPage(),
            'currentPage' => $clients->currentPage(),
            'totalItems' => $clients->total()
        ];
    }

    public function searchPolicies($userId, $searchTerm, $page = 1, $limit = 5)
    {
        // Primero obtenemos el cliente asociado al usuario
        $cliente = Client::where('user_id', $userId)->first();
        
        if (!$cliente) {
            return [
                'policies' => [],
                'totalPages' => 0,
                'currentPage' => 1,
                'totalItems' => 0
            ];
        }

        $query = Poliza::where('clients_id', $cliente->id);
        
        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('tipo_seguro', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('asegurado', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('aseguradora', 'LIKE', "%{$searchTerm}%");
            });
        }

        $policies = $query->paginate($limit, ['*'], 'page', $page);

        return [
            'policies' => $policies->items(),
            'totalPages' => $policies->lastPage(),
            'currentPage' => $policies->currentPage(),
            'totalItems' => $policies->total()
        ];
    }

    public function searchAll($userId, $searchTerm, $page = 1, $limit = 5)
    {
        $clients = $this->searchClients($userId, $searchTerm, $page, $limit);
        $policies = $this->searchPolicies($userId, $searchTerm, $page, $limit);

        return [
            'clients' => $clients['clients'],
            'policies' => $policies['policies'],
            'totalClients' => $clients['totalItems'],
            'totalPolicies' => $policies['totalItems'],
            'currentPage' => $page,
            'totalPagesClients' => $clients['totalPages'],
            'totalPagesPolicies' => $policies['totalPages']
        ];
    }
}