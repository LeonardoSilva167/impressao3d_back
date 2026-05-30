<?php

use App\Http\Controllers\ModeloProdutoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                      [ModeloProdutoController::class, 'listarLookupsModeloProduto']);
Route::get('/listar',                       [ModeloProdutoController::class, 'listarModeloProduto']);
Route::get('/listar/{id}',                  [ModeloProdutoController::class, 'listarModeloProdutoId']);
Route::post('/cadastrar',                   [ModeloProdutoController::class, 'createModeloProduto']);
Route::put('/editar',                       [ModeloProdutoController::class, 'editModeloProduto']);
Route::delete('/excluir/{id}',              [ModeloProdutoController::class, 'deleteModeloProduto']);
Route::get('/modelos-produtos-list',     [ModeloProdutoController::class, 'listarModeloProdutoAsync']);
