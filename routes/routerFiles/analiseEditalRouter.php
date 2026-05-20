<?php

use App\Http\Controllers\AnaliseEditalController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [AnaliseEditalController::class, 'listarLookupsAnaliseEdital']);
Route::get('/listar', [AnaliseEditalController::class, 'listarAnaliseEdital']);
Route::get('/listar/{id}', [AnaliseEditalController::class, 'listarAnaliseEditalId']);
Route::post('/cadastrar', [AnaliseEditalController::class, 'createAnaliseEdital']);
Route::put('/editar', [AnaliseEditalController::class, 'editAnaliseEdital']);
Route::delete('/excluir/{id}', [AnaliseEditalController::class, 'deleteAnaliseEdital']);
Route::get('/analise-edital-list', [AnaliseEditalController::class, 'listarAnaliseEditalAsync']);

// Route::group(['prefix' => 'itens'], function () {
//     Route::get('/lookups', [AnaliseEditalController::class, 'listarLookupsAnaliseEditalItens']);
//     Route::get('/listar', [AnaliseEditalController::class, 'listarAnaliseEditalItens']);
//     Route::get('/listar/{id}', [AnaliseEditalController::class, 'listarAnaliseEditalItensId']);
//     Route::post('/cadastrar', [AnaliseEditalController::class, 'createAnaliseEditalItens']);
//     Route::put('/editar', [AnaliseEditalController::class, 'editAnaliseEditalItens']);
//     Route::delete('/excluir/{id}', [AnaliseEditalController::class, 'deleteAnaliseEditalItens']);
//     Route::get('/AnaliseEdital-list', [AnaliseEditalController::class, 'listarAnaliseEditalItensAsync']);
// });