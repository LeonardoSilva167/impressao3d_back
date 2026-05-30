<?php

use App\Http\Controllers\CategoriaProdutoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                      [CategoriaProdutoController::class, 'listarLookupsCategoriaProduto']);
Route::get('/listar',                       [CategoriaProdutoController::class, 'listarCategoriaProduto']);
Route::get('/listar/{id}',                  [CategoriaProdutoController::class, 'listarCategoriaProdutoId']);
Route::post('/cadastrar',                   [CategoriaProdutoController::class, 'createCategoriaProduto']);
Route::put('/editar',                       [CategoriaProdutoController::class, 'editCategoriaProduto']);
Route::delete('/excluir/{id}',              [CategoriaProdutoController::class, 'deleteCategoriaProduto']);
Route::get('/categorias-produtos-list',     [CategoriaProdutoController::class, 'listarCategoriaProdutoAsync']);
