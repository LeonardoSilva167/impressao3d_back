<?php

use App\Http\Controllers\CarreteisFinalizadoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                              [CarreteisFinalizadoController::class, 'listarLookupsCarreteisFinalizado']);
Route::get('/lote-mais-antigo/{id_item}',           [CarreteisFinalizadoController::class, 'loteMaisAntigo']);
Route::get('/lote-mais-antigo-filamento/{id_filamento}', [CarreteisFinalizadoController::class, 'loteMaisAntigoFilamento']);
Route::get('/listar',                               [CarreteisFinalizadoController::class, 'listarCarreteisFinalizados']);
Route::get('/listar/{id}',                          [CarreteisFinalizadoController::class, 'listarCarreteisFinalizadoId']);
Route::post('/cadastrar',                           [CarreteisFinalizadoController::class, 'createCarreteisFinalizado']);
Route::put('/editar',                               [CarreteisFinalizadoController::class, 'editCarreteisFinalizado']);
Route::delete('/excluir/{id}',                      [CarreteisFinalizadoController::class, 'deleteCarreteisFinalizado']);
Route::get('/carreteis-finalizados-list',           [CarreteisFinalizadoController::class, 'listarCarreteisFinalizadoAsync']);
