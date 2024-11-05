<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\polizaController;
use App\Http\Controllers\userController;
use App\Http\Controllers\ClienteController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/forgot-password', [UserController::class, 'requestPasswordReset']);
Route::post('/reset-password', [UserController::class, 'resetPassword']);
Route::middleware('auth:sanctum')->put('/update/{id}', [UserController::class, 'updateUser']);


Route::get('/polizas', [polizaController::class, 'index']);
Route::get('/polizas/{id}', [polizaController::class, 'show']);

Route::post('/polizas', [polizaController::class, 'store']);

Route::put('/polizas/{id}', [polizaController::class, 'update']);	
Route::delete('/polizas/{id}', [polizaController::class, 'destroy']);

// usuarios

// Route::get('/usuarios', [userController::class, 'index']);
// Route::get('/usuarios/{id}', [userController::class, 'show']);

// Route::post('/usuarios', [userController::class, 'store']);


//clientes

Route::get('/clientes', [ClienteController::class, 'index']);
Route::get('/clientes/{id}', [ClienteController::class, 'show']);
Route::post('/clientes', [ClienteController::class, 'store']);
