<?php

use App\Http\Controllers\TipoMaterialController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',              [TipoMaterialController::class, 'listarLookupsTipoMaterial']);
Route::get('/listar',               [TipoMaterialController::class, 'listarTipoMaterial']);
Route::get('/listar/{id}',          [TipoMaterialController::class, 'listarTipoMaterialId']);
Route::post('/cadastrar',           [TipoMaterialController::class, 'createTipoMaterial']);
Route::put('/editar',               [TipoMaterialController::class, 'editTipoMaterial']);
Route::delete('/excluir/{id}',      [TipoMaterialController::class, 'deleteTipoMaterial']);
Route::get('/tipo-material-list',   [TipoMaterialController::class, 'listarTipoMaterialAsync']);
