<?php

use App\Http\Controllers\CategoriaItemController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                [CategoriaItemController::class, 'listarLookupsCategoriaItem']);
Route::get('/listar',                 [CategoriaItemController::class, 'listarCategoriaItem']);
Route::get('/listar/{id}',            [CategoriaItemController::class, 'listarCategoriaItemId']);
Route::post('/cadastrar',             [CategoriaItemController::class, 'createCategoriaItem']);
Route::put('/editar',                 [CategoriaItemController::class, 'editCategoriaItem']);
Route::delete('/excluir/{id}',        [CategoriaItemController::class, 'deleteCategoriaItem']);
Route::get('/categorias-itens-list',  [CategoriaItemController::class, 'listarCategoriaItemAsync']);
