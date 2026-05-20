<?php

use App\Http\Controllers\ProdutoLinhasController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ProdutoLinhasController::class, 'listarLookupsProdutoLinhas']);
Route::get('/listar', [ProdutoLinhasController::class, 'listarProdutoLinhas']);
Route::get('/listar/{id}', [ProdutoLinhasController::class, 'listarDespesaId']);
Route::post('/cadastrar', [ProdutoLinhasController::class, 'createProdutoLinhas']);
Route::put('/editar', [ProdutoLinhasController::class, 'editProdutoLinhas']);
Route::delete('/excluir/{id}', [ProdutoLinhasController::class, 'deleteProdutoLinhas']);
Route::get('/produto-linhas-list', [ProdutoLinhasController::class, 'listarProdutoLinhasAsync']);