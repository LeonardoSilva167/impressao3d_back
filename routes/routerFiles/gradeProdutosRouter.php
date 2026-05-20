<?php

use App\Http\Controllers\ProdutoGradesController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [ProdutoGradesController::class, 'listarLookupsProdutoGrades']);
Route::get('/listar', [ProdutoGradesController::class, 'listarProdutoGrades']);
Route::get('/listar/{id}', [ProdutoGradesController::class, 'listarGradeId']);
Route::post('/cadastrar', [ProdutoGradesController::class, 'createProdutoGrades']);
Route::put('/editar', [ProdutoGradesController::class, 'editProdutoGrades']);
Route::delete('/excluir/{id}', [ProdutoGradesController::class, 'deleteProdutoGrades']);
Route::get('/produto-grades-list', [ProdutoGradesController::class, 'listarProdutoGradesAsync']);