<?php

use App\Http\Controllers\ProjetoImpressaoParteItemController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups',                                    [ProjetoImpressaoParteItemController::class, 'listarLookupsProjetoImpressaoParteItem']);
Route::get('/listar',                                     [ProjetoImpressaoParteItemController::class, 'listarProjetoImpressaoParteItem']);
Route::get('/listar/{id}',                                [ProjetoImpressaoParteItemController::class, 'listarProjetoImpressaoParteItemId']);
Route::post('/cadastrar',                                 [ProjetoImpressaoParteItemController::class, 'createProjetoImpressaoParteItem']);
Route::put('/editar',                                     [ProjetoImpressaoParteItemController::class, 'editProjetoImpressaoParteItem']);
Route::delete('/excluir/{id}',                            [ProjetoImpressaoParteItemController::class, 'deleteProjetoImpressaoParteItem']);
Route::get('/projetos-impressao-parte-itens-list',        [ProjetoImpressaoParteItemController::class, 'listarProjetoImpressaoParteItemAsync']);
