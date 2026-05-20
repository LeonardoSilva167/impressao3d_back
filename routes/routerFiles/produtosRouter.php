<?php

use App\Http\Controllers\ProdutosController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ProdutosController::class, 'listarLookupsProdutos']);
Route::get('/listar', [ProdutosController::class, 'listarProdutos']);
Route::get('/listar/{id}', [ProdutosController::class, 'listarDespesaId']);
Route::post('/cadastrar', [ProdutosController::class, 'createProdutos']);
Route::put('/editar', [ProdutosController::class, 'editProdutos']);
Route::delete('/excluir/{id}', [ProdutosController::class, 'deleteProdutos']);
Route::get('/produtos-list', [ProdutosController::class, 'listarProdutosAsync']);