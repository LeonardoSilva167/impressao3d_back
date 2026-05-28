<?php

use App\Http\Controllers\ProjetoImpressaoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                      [ProjetoImpressaoController::class, 'listarLookupsProjetoImpressao']);
Route::get('/listar',                       [ProjetoImpressaoController::class, 'listarProjetoImpressao']);
Route::get('/listar/{id}',                  [ProjetoImpressaoController::class, 'listarProjetoImpressaoId']);
Route::post('/cadastrar',                   [ProjetoImpressaoController::class, 'createProjetoImpressao']);
Route::put('/editar',                       [ProjetoImpressaoController::class, 'editProjetoImpressao']);
Route::delete('/excluir/{id}',              [ProjetoImpressaoController::class, 'deleteProjetoImpressao']);
Route::get('/projetos-impressao-list',      [ProjetoImpressaoController::class, 'listarProjetoImpressaoAsync']);
