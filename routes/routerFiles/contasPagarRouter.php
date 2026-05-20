<?php

use App\Http\Controllers\ContasPagarController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ContasPagarController::class, 'listarLookupsContasPagar']);
Route::get('/listar', [ContasPagarController::class, 'listarContasPagar']);
Route::post('/cadastrar', [ContasPagarController::class, 'createContasPagar']);
Route::put('/editar', [ContasPagarController::class, 'editContasPagars']);
Route::delete('/excluir/{id}', [ContasPagarController::class, 'deleteContasPagar']);
