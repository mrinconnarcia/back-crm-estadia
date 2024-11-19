<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\polizaController;
use App\Http\Controllers\userController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PolizaController as ControllersPolizaController;
use App\Http\Controllers\SearchController;

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

Route::post('forgot-password', [userController::class, 'requestPasswordReset'])
    ->name('password.email');

Route::post('reset-password', [userController::class, 'resetPassword'])
    ->name('password.reset');
// O si prefieres mantener PUT pero enviar como POST
Route::match(['put', 'post'], '/update/{userId}', [UserController::class, 'update']); // Route::middleware('auth:sanctum')->group(function () {
//     Route::put('/update/{userId}', [UserController::class, 'updateUser']);
// });


// clientes
Route::prefix('users/{userId}/clients')->group(function () {
    Route::post('/add', [ClientController::class, 'store']);
    Route::put('/{id}', [ClientController::class, 'update']);
    Route::get('/', [ClientController::class, 'index']);
    Route::get('/{id}', [ClientController::class, 'show']);
    // Route::get('/download-excel', [ClientController::class, 'downloadAllClients']); 
});
Route::get('users/download-excel/{userId}', [ClientController::class, 'downloadAllClients']);


//polizas:
// Rutas para los clientes
Route::prefix('clientes/{clientId}')->group(function () {
    // Agregar póliza (con archivo)
    Route::post('/polizas', [PolizaController::class, 'store']);
    // Obtener póliza por id
    Route::get('/polizas/{id}', [PolizaController::class, 'show']);
    // Editar póliza
    Route::put('/polizas/{id}', [PolizaController::class, 'update']);
    // Eliminar póliza
    Route::delete('/polizas/{id}', [PolizaController::class, 'destroy']);
    // Obtener todas las pólizas de un cliente
    Route::get('/polizas', [PolizaController::class, 'getPolizasByCliente']);
    // Exportar pólizas de un cliente
    Route::get('/download-excel', [PolizaController::class, 'downloadAllPolicies']); // Cambiado
});
Route::get('policies/download-excel/{userId}', [PolizaController::class, 'downloadAllPolicies']); // Cambiado


// Rutas para los usuarios
Route::get('usuarios/{userId}/polizas/export', [PolizaController::class, 'downloadAllPolicies']);  // Exportar pólizas de un usuario
Route::get('/polizas', [PolizaController::class, 'getAllPolizas']); // Obtener todas las pólizas
Route::get('/polizas/{policyId}', [polizaController::class, 'show']);
Route::get('/polizas/user/{user_id}', [PolizaController::class, 'getPolizasByUser']);
Route::get('/user/{user_id}/polizas/search', [PolizaController::class, 'buscarPolizasPorUsuario']); // Buscar pólizas de un usuario
Route::put('/polizas/{id}', [PolizaController::class, 'updateWithoutClient']);


//notas
Route::prefix('clientes/{clienteId}')->group(function () {
    Route::post('/notas', [NotaController::class, 'agregarNota']);
    Route::get('/notas', [NotaController::class, 'obtenerNotasPorCliente']);
});


//pagos
Route::prefix('pagos')->group(function () {
    Route::get('polizas/{poliza_id}/pagos', [PagoController::class, 'obtenerPagosPorPoliza']);
    Route::get('usuarios/{userId}/pagos', [PagoController::class, 'obtenerPagosPorUsuario']);
    Route::get('poliza/{poliza_id}', [PagoController::class, 'obtenerPagosPorPolizaPorID']);
});


// routes/api.php (añade estas rutas)
Route::prefix('users/{userId}/search')->group(function () {
    Route::get('/clientes', [SearchController::class, 'searchClients']);
    Route::get('/polizas', [SearchController::class, 'searchPolicies']);
    Route::get('/buscar', [SearchController::class, 'search']);
});
