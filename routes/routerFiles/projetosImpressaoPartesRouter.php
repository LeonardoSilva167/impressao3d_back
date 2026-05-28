<?php

use App\Http\Controllers\ProjetoImpressaoParteController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                          [ProjetoImpressaoParteController::class, 'listarLookupsProjetoImpressaoParte']);
Route::get('/listar',                           [ProjetoImpressaoParteController::class, 'listarProjetoImpressaoParte']);
Route::get('/listar/{id}',                      [ProjetoImpressaoParteController::class, 'listarProjetoImpressaoParteId']);
Route::post('/cadastrar',                       [ProjetoImpressaoParteController::class, 'createProjetoImpressaoParte']);
Route::put('/editar',                           [ProjetoImpressaoParteController::class, 'editProjetoImpressaoParte']);
Route::delete('/excluir/{id}',                  [ProjetoImpressaoParteController::class, 'deleteProjetoImpressaoParte']);
Route::get('/projetos-impressao-partes-list', [ProjetoImpressaoParteController::class, 'listarProjetoImpressaoParteAsync']);
