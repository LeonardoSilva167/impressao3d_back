<?php

use App\Http\Controllers\MarcaController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',         [MarcaController::class, 'listarLookupsMarca']);
Route::get('/listar',          [MarcaController::class, 'listarMarca']);
Route::get('/listar/{id}',     [MarcaController::class, 'listarMarcaId']);
Route::post('/cadastrar',      [MarcaController::class, 'createMarca']);
Route::put('/editar',          [MarcaController::class, 'editMarca']);
Route::delete('/excluir/{id}', [MarcaController::class, 'deleteMarca']);
Route::get('/marcas-list',     [MarcaController::class, 'listarMarcaAsync']);
