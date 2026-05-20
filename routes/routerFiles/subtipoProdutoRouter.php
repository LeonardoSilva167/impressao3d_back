<?php

use App\Http\Controllers\SubtipoProdutoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [SubtipoProdutoController::class, 'listarLookupSubtipoProduto']);
Route::get('/listar', [SubtipoProdutoController::class, 'listarSubtipoProduto']);
Route::get('/listar/{id}', [SubtipoProdutoController::class, 'listarDespesaId']);
Route::post('/cadastrar', [SubtipoProdutoController::class, 'createSubtipoProduto']);
Route::put('/editar', [SubtipoProdutoController::class, 'editSubtipoProduto']);
Route::delete('/excluir/{id}', [SubtipoProdutoController::class, 'deleteSubtipoProduto']);
Route::get('/subtipo-produto-list', [SubtipoProdutoController::class, 'listarSubtipoProdutoAsync']);