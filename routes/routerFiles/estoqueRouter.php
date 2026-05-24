<?php

use App\Http\Controllers\EstoqueController;
use Illuminate\Support\Facades\Route;

Route::get('/lotes',                    [EstoqueController::class, 'listarLotes']);
Route::post('/consumir',                [EstoqueController::class, 'consumirFilamento']);
Route::post('/finalizar-carretel',      [EstoqueController::class, 'finalizarCarretel']);
