<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected $searchService;
    
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function searchClients(Request $request, $userId)
    {
        try {
            $result = $this->searchService->searchClients(
                $userId,
                $request->query('q', ''),
                $request->query('page', 1),
                $request->query('limit', 5)
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchPolicies(Request $request, $userId)
    {
        try {
            $result = $this->searchService->searchPolicies(
                $userId,
                $request->query('q', ''),
                $request->query('page', 1),
                $request->query('limit', 5)
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function search(Request $request, $userId)
    {
        try {
            $result = $this->searchService->searchAll(
                $userId,
                $request->query('q', ''),
                $request->query('page', 1),
                $request->query('limit', 5)
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
