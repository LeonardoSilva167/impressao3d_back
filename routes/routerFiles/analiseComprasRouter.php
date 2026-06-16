<?php

use App\Http\Controllers\CompraAnaliseController;
use Illuminate\Support\Facades\Route;

Route::get('/lookups', [CompraAnaliseController::class, 'listarLookupsCompraAnalise']);
Route::get('/analise', [CompraAnaliseController::class, 'listarCompraAnalise']);
