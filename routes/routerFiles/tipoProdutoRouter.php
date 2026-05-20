<?php

use App\Http\Controllers\TipoProdutoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [TipoProdutoController::class, 'listarLookupsTipoProduto']);
Route::get('/listar', [TipoProdutoController::class, 'listarTipoProduto']);
Route::get('/listar/{id}', [TipoProdutoController::class, 'listarDespesaId']);
Route::post('/cadastrar', [TipoProdutoController::class, 'createTipoProduto']);
Route::put('/editar', [TipoProdutoController::class, 'editTipoProduto']);
Route::delete('/excluir/{id}', [TipoProdutoController::class, 'deleteTipoProduto']);
Route::get('/tipo-produto-list', [TipoProdutoController::class, 'listarTipoProdutoAsync']);