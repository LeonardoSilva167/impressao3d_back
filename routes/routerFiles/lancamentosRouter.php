<?php

use App\Http\Controllers\LancamentosController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [LancamentosController::class, 'listarLookupsLancamentos']);
Route::get('/listar', [LancamentosController::class, 'listarLancamentos']);
Route::get('/listar/{id}', [LancamentosController::class, 'listarDespesaId']);
Route::post('/cadastrar', [LancamentosController::class, 'createLancamentos']);
Route::put('/editar', [LancamentosController::class, 'editLancamentos']);
Route::delete('/excluir/{id}', [LancamentosController::class, 'deleteLancamentos']);
Route::get('/lancamentos-list', [LancamentosController::class, 'listarLancamentosAsync']);