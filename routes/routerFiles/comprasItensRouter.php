<?php

use App\Http\Controllers\CompraItemController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',              [CompraItemController::class, 'listarLookupsCompraItem']);
Route::get('/listar',               [CompraItemController::class, 'listarCompraItem']);
Route::get('/listar/{id}',          [CompraItemController::class, 'listarCompraItemId']);
Route::post('/cadastrar',           [CompraItemController::class, 'createCompraItem']);
Route::put('/editar',               [CompraItemController::class, 'editCompraItem']);
Route::delete('/excluir/{id}',      [CompraItemController::class, 'deleteCompraItem']);
Route::get('/compras-itens-list',   [CompraItemController::class, 'listarCompraItemAsync']);
