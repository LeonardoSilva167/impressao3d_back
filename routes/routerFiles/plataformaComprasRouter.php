<?php

use App\Http\Controllers\PlataformaCompraController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                  [PlataformaCompraController::class, 'listarLookupsPlataformaCompra']);
Route::get('/listar',                   [PlataformaCompraController::class, 'listarPlataformaCompra']);
Route::get('/listar/{id}',              [PlataformaCompraController::class, 'listarPlataformaCompraId']);
Route::post('/cadastrar',               [PlataformaCompraController::class, 'createPlataformaCompra']);
Route::put('/editar',                   [PlataformaCompraController::class, 'editPlataformaCompra']);
Route::delete('/excluir/{id}',          [PlataformaCompraController::class, 'deletePlataformaCompra']);
Route::get('/plataforma-compras-list',  [PlataformaCompraController::class, 'listarPlataformaCompraAsync']);
