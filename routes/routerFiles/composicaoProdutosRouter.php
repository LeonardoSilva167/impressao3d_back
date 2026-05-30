<?php

use App\Http\Controllers\ProdutoComposicaoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                      [ProdutoComposicaoController::class, 'listarLookupsProdutoComposicao']);
Route::get('/carregar-composicao',          [ProdutoComposicaoController::class, 'carregarProdutoComposicao']);
Route::get('/carregar',                     [ProdutoComposicaoController::class, 'carregarProdutoComposicao']);
Route::get('/configurar-parte/{id}/{idParte}', [ProdutoComposicaoController::class, 'configurarParteProdutoComposicao']);
Route::get('/{id}/parte/{idParte}/configurar', [ProdutoComposicaoController::class, 'configurarParteProdutoComposicao']);
Route::get('/listar',                       [ProdutoComposicaoController::class, 'listarProdutoComposicao']);
Route::get('/listar/{id}',                  [ProdutoComposicaoController::class, 'listarProdutoComposicaoId']);
Route::post('/cadastrar',                   [ProdutoComposicaoController::class, 'createProdutoComposicao']);
Route::put('/editar',                       [ProdutoComposicaoController::class, 'editProdutoComposicao']);
Route::delete('/excluir/{id}',              [ProdutoComposicaoController::class, 'deleteProdutoComposicao']);
Route::put('/salvar-cores-parte',           [ProdutoComposicaoController::class, 'salvarCoresParteProdutoComposicao']);
Route::post('/gerar-variacoes/{id}',        [ProdutoComposicaoController::class, 'gerarVariacoes']);
Route::post('/confirmar-variacoes',         [ProdutoComposicaoController::class, 'confirmarVariacoes']);
Route::put('/salvar-filamentos',            [ProdutoComposicaoController::class, 'salvarFilamentos']);
Route::get('/composicao-produtos-list',     [ProdutoComposicaoController::class, 'listarProdutoComposicaoAsync']);
