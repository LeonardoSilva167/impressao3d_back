<?php

use App\Http\Controllers\ContasBancariasController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ContasBancariasController::class, 'listarLookupsContasBancarias']);
Route::get('/listar', [ContasBancariasController::class, 'listarContasBancarias']);
Route::get('/listar/{id}', [ContasBancariasController::class, 'listarDespesaId']);
Route::post('/cadastrar', [ContasBancariasController::class, 'createContasBancarias']);
Route::put('/editar', [ContasBancariasController::class, 'editContasBancarias']);
Route::delete('/excluir/{id}', [ContasBancariasController::class, 'deleteContasBancarias']);
Route::get('/ContasBancarias-list', [ContasBancariasController::class, 'listarContasBancariasAsync']);