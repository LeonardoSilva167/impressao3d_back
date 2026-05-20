<?php

use App\Http\Controllers\DespesaController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [DespesaController::class, 'listarLookupsDespesas']);
Route::get('/listar', [DespesaController::class, 'listarDespesas']);
Route::get('/listar/{id}', [DespesaController::class, 'listarDespesaId']);
Route::post('/cadastrar', [DespesaController::class, 'createDespesas']);
Route::put('/editar', [DespesaController::class, 'editDespesas']);
Route::delete('/excluir/{id}', [DespesaController::class, 'deleteDespesas']);
Route::get('/despesas-list', [DespesaController::class, 'listarDespesasAsync']);