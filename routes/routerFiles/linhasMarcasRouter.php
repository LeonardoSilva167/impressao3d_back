<?php

use App\Http\Controllers\LinhaMarcaController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',              [LinhaMarcaController::class, 'listarLookupsLinhaMarca']);
Route::get('/listar',               [LinhaMarcaController::class, 'listarLinhaMarca']);
Route::get('/listar/{id}',          [LinhaMarcaController::class, 'listarLinhaMarcaId']);
Route::post('/cadastrar',           [LinhaMarcaController::class, 'createLinhaMarca']);
Route::put('/editar',               [LinhaMarcaController::class, 'editLinhaMarca']);
Route::delete('/excluir/{id}',      [LinhaMarcaController::class, 'deleteLinhaMarca']);
Route::get('/linhas-marcas-list',   [LinhaMarcaController::class, 'listarLinhaMarcaAsync']);
