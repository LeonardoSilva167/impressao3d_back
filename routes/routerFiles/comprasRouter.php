<?php

use App\Http\Controllers\CompraController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',         [CompraController::class, 'listarLookupsCompra']);
Route::get('/listar',          [CompraController::class, 'listarCompra']);
Route::get('/listar/{id}',     [CompraController::class, 'listarCompraId']);
Route::post('/cadastrar',      [CompraController::class, 'createCompra']);
Route::put('/editar',          [CompraController::class, 'editCompra']);
Route::post('/{id}/cancelar',  [CompraController::class, 'cancelarCompra']);
Route::get('/compras-list',    [CompraController::class, 'listarCompraAsync']);
