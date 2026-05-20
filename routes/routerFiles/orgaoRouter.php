<?php

use App\Http\Controllers\OrgaoController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [OrgaoController::class, 'listarLookupsOrgao']);
Route::get('/listar', [OrgaoController::class, 'listarOrgao']);
Route::get('/listar/{id}', [OrgaoController::class, 'listarOrgaoId']);
Route::post('/cadastrar', [OrgaoController::class, 'createOrgao']);
Route::put('/editar', [OrgaoController::class, 'editOrgao']);
Route::delete('/excluir/{id}', [OrgaoController::class, 'deleteOrgao']);
Route::get('/orgao-list', [OrgaoController::class, 'listarOrgaoAsync']);
Route::get('/unidade-compradora-list', [OrgaoController::class, 'listarUnidadeCompradoraAsync']);