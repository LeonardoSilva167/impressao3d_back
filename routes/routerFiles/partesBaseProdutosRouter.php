<?php

use App\Http\Controllers\ParteBaseController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                          [ParteBaseController::class, 'listarLookupsParteBase']);
Route::get('/listar',                           [ParteBaseController::class, 'listarParteBase']);
Route::get('/listar/{id}',                      [ParteBaseController::class, 'listarParteBaseId']);
Route::post('/cadastrar',                       [ParteBaseController::class, 'createParteBase']);
Route::put('/editar',                           [ParteBaseController::class, 'editParteBase']);
Route::delete('/excluir/{id}',                  [ParteBaseController::class, 'deleteParteBase']);
Route::get('/partes-base-produtos-list',        [ParteBaseController::class, 'listarParteBaseAsync']);
