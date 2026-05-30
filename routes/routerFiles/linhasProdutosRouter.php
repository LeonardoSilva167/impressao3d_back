<?php

use App\Http\Controllers\LinhaProdutoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                      [LinhaProdutoController::class, 'listarLookupsLinhaProduto']);
Route::get('/listar',                       [LinhaProdutoController::class, 'listarLinhaProduto']);
Route::get('/listar/{id}',                  [LinhaProdutoController::class, 'listarLinhaProdutoId']);
Route::post('/cadastrar',                   [LinhaProdutoController::class, 'createLinhaProduto']);
Route::put('/editar',                       [LinhaProdutoController::class, 'editLinhaProduto']);
Route::delete('/excluir/{id}',              [LinhaProdutoController::class, 'deleteLinhaProduto']);
Route::get('/linhas-produtos-list',     [LinhaProdutoController::class, 'listarLinhaProdutoAsync']);
