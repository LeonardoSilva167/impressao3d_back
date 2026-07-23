<?php

use App\Http\Controllers\FluxoProducaoController;
use Illuminate\Support\Facades\Route;

Route::get('/progresso', [FluxoProducaoController::class, 'obterProgresso']);
Route::get('/lookups',   [FluxoProducaoController::class, 'listarLookupsFluxoProducao']);
