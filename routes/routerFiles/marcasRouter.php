<?php

use App\Http\Controllers\MarcasController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [MarcasController::class, 'listarLookupsMarcas']);
Route::get('/listar', [MarcasController::class, 'listarMarcas']);
Route::get('/listar/{id}', [MarcasController::class, 'listarDespesaId']);
Route::post('/cadastrar', [MarcasController::class, 'createMarcas']);
Route::put('/editar', [MarcasController::class, 'editMarcas']);
Route::delete('/excluir/{id}', [MarcasController::class, 'deleteMarcas']);
Route::get('/marcas-list', [MarcasController::class, 'listarMarcasAsync']);