<?php

use App\Http\Controllers\ProdutoBaseController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',              [ProdutoBaseController::class, 'listarLookupsProdutoBase']);
Route::get('/listar',               [ProdutoBaseController::class, 'listarProdutoBase']);
Route::get('/listar/{id}',          [ProdutoBaseController::class, 'listarProdutoBaseId']);
Route::post('/cadastrar',           [ProdutoBaseController::class, 'createProdutoBase']);
Route::put('/editar',               [ProdutoBaseController::class, 'editProdutoBase']);
Route::delete('/excluir/{id}',      [ProdutoBaseController::class, 'deleteProdutoBase']);
Route::get('/produtos-list',        [ProdutoBaseController::class, 'listarProdutoBaseAsync']);
