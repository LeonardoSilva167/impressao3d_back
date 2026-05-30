<?php

use App\Http\Controllers\ProdutoComposicaoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',              [ProdutoComposicaoController::class, 'listarLookupsProdutoComposicao']);
Route::get('/carregar-composicao',  [ProdutoComposicaoController::class, 'carregarProdutoComposicao']);
Route::get('/carregar',             [ProdutoComposicaoController::class, 'carregarProdutoComposicao']);
Route::get('/listar',               [ProdutoComposicaoController::class, 'listarProdutoComposicao']);
Route::get('/listar/{id}',          [ProdutoComposicaoController::class, 'listarProdutoComposicaoId']);
Route::post('/cadastrar',           [ProdutoComposicaoController::class, 'createProdutoComposicao']);
Route::put('/editar',               [ProdutoComposicaoController::class, 'editProdutoComposicao']);
Route::delete('/excluir/{id}',      [ProdutoComposicaoController::class, 'deleteProdutoComposicao']);
Route::get('/composicao-produtos-list', [ProdutoComposicaoController::class, 'listarProdutoComposicaoAsync']);
