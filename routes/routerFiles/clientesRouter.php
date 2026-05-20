<?php

use App\Http\Controllers\ClientesController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ClientesController::class, 'listarLookupsClientes']);
Route::get('/listar', [ClientesController::class, 'listarClientes']);
Route::get('/listar/{id}', [ClientesController::class, 'listarClientesId']);
Route::post('/cadastrar', [ClientesController::class, 'createClientes']);
Route::put('/editar', [ClientesController::class, 'editClientes']);
Route::delete('/excluir/{id}', [ClientesController::class, 'deleteClientes']);
Route::get('/clientes-list', [ClientesController::class, 'listarClientesAsync']);