<?php

use App\Http\Controllers\ProdutoVariacaoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                      [ProdutoVariacaoController::class, 'listarLookupsProdutoVariacao']);
Route::get('/listar',                       [ProdutoVariacaoController::class, 'listarProdutoVariacao']);
Route::get('/listar/{id}',                  [ProdutoVariacaoController::class, 'listarProdutoVariacaoId']);
Route::post('/cadastrar',                   [ProdutoVariacaoController::class, 'createProdutoVariacao']);
Route::put('/editar',                       [ProdutoVariacaoController::class, 'editProdutoVariacao']);
Route::delete('/excluir/{id}',              [ProdutoVariacaoController::class, 'deleteProdutoVariacao']);
Route::get('/produto-variacoes-list',       [ProdutoVariacaoController::class, 'listarProdutoVariacaoAsync']);
