<?php

use App\Http\Controllers\CorController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',         [CorController::class, 'listarLookupsCor']);
Route::get('/listar',          [CorController::class, 'listarCor']);
Route::get('/listar/{id}',     [CorController::class, 'listarCorId']);
Route::post('/cadastrar',      [CorController::class, 'createCor']);
Route::put('/editar',          [CorController::class, 'editCor']);
Route::delete('/excluir/{id}', [CorController::class, 'deleteCor']);
Route::get('/cores-list',      [CorController::class, 'listarCorAsync']);
