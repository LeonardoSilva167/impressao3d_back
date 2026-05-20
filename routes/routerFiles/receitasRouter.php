<?php

use App\Http\Controllers\ReceitaController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ReceitaController::class, 'listarLookupsReceitas']);
Route::get('/listar', [ReceitaController::class, 'listarReceitas']);
Route::get('/listar/{id}', [ReceitaController::class, 'listarReceitasId']);
Route::post('/cadastrar', [ReceitaController::class, 'createReceitas']);
Route::put('/editar', [ReceitaController::class, 'editReceitas']);
Route::delete('/excluir/{id}', [ReceitaController::class, 'deleteReceitas']);
Route::get('/receitas-list', [ReceitaController::class, 'listarReceitasAsync']);