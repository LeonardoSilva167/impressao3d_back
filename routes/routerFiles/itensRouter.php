<?php

use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',         [ItemController::class, 'listarLookupsItem']);
Route::get('/listar',          [ItemController::class, 'listarItem']);
Route::get('/listar/{id}',     [ItemController::class, 'listarItemId']);
Route::post('/cadastrar',      [ItemController::class, 'createItem']);
Route::put('/editar',          [ItemController::class, 'editItem']);
Route::delete('/excluir/{id}', [ItemController::class, 'deleteItem']);
Route::get('/itens-list',      [ItemController::class, 'listarItemAsync']);
