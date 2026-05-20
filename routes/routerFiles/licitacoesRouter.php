<?php

use App\Http\Controllers\LicitacoesController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [LicitacoesController::class, 'listarLookupsLicitacoes']);
Route::get('/listar', [LicitacoesController::class, 'listarLicitacoes']);
Route::get('/listar/{id}', [LicitacoesController::class, 'listarLicitacoesId']);
Route::post('/cadastrar', [LicitacoesController::class, 'createLicitacoes']);
Route::put('/editar', [LicitacoesController::class, 'editLicitacoes']);
Route::delete('/excluir/{id}', [LicitacoesController::class, 'deleteLicitacoes']);
Route::get('/licitacoes-list', [LicitacoesController::class, 'listarLicitacoesAsync']);

Route::group(['prefix' => 'itens'], function () {
    Route::get('/lookups', [LicitacoesController::class, 'listarLookupsLicitacoesItens']);
    Route::get('/listar', [LicitacoesController::class, 'listarLicitacoesItens']);
    Route::get('/listar/{id}', [LicitacoesController::class, 'listarLicitacoesItensId']);
    Route::post('/cadastrar', [LicitacoesController::class, 'createLicitacoesItens']);
    Route::put('/editar', [LicitacoesController::class, 'editLicitacoesItens']);
    Route::delete('/excluir/{id}', [LicitacoesController::class, 'deleteLicitacoesItens']);
    Route::get('/licitacoes-list', [LicitacoesController::class, 'listarLicitacoesItensAsync']);
});