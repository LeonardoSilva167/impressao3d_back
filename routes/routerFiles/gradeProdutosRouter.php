<?php

use App\Http\Controllers\GradeProdutoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',              [GradeProdutoController::class, 'listarLookupsGradeProduto']);
Route::get('/carregar-dados',       [GradeProdutoController::class, 'carregarComposicaoGradeProduto']);
Route::get('/carregar-composicao',  [GradeProdutoController::class, 'carregarComposicaoGradeProduto']);
Route::get('/produto/{id}',         [GradeProdutoController::class, 'listarGradeProdutoId']);
Route::get('/listar',               [GradeProdutoController::class, 'listarGradeProduto']);
Route::get('/listar-grade/{id}',   [GradeProdutoController::class, 'listarGradeProdutoGradeId']);
Route::get('/listar/{id}',          [GradeProdutoController::class, 'listarGradeProdutoId']);
Route::post('/cadastrar',           [GradeProdutoController::class, 'createGradeProduto']);
Route::put('/editar',               [GradeProdutoController::class, 'editGradeProduto']);
Route::delete('/excluir/{id}',      [GradeProdutoController::class, 'deleteGradeProduto']);
Route::post('/preview-produtos',    [GradeProdutoController::class, 'previewProdutosGradeProduto']);
Route::post('/gerar-grade',         [GradeProdutoController::class, 'gerarGradeProduto']);
Route::post('/gerar-produtos/{id}', [GradeProdutoController::class, 'gerarProdutosGradeProduto']);
Route::get('/grades-produtos-list', [GradeProdutoController::class, 'listarGradeProdutoAsync']);
Route::get('/grade-produtos-list',  [GradeProdutoController::class, 'listarGradeProdutoAsync']);
