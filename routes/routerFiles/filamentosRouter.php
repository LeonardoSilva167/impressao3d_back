<?php

use App\Http\Controllers\FilamentoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',            [FilamentoController::class, 'listarLookupsFilamento']);
Route::get('/listar',             [FilamentoController::class, 'listarFilamento']);
Route::get('/listar/{id}',        [FilamentoController::class, 'listarFilamentoId']);
Route::post('/cadastrar',         [FilamentoController::class, 'createFilamento']);
Route::put('/editar',             [FilamentoController::class, 'editFilamento']);
Route::delete('/excluir/{id}',    [FilamentoController::class, 'deleteFilamento']);
Route::get('/filamentos-list',    [FilamentoController::class, 'listarFilamentoAsync']);
