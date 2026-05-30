<?php

use App\Http\Controllers\ConfiguracaoController;
use Illuminate\Support\Facades\Route;

Route::get('/listar/{id}', [ConfiguracaoController::class, 'listarConfiguracaoId']);
Route::put('/editar',        [ConfiguracaoController::class, 'editConfiguracao']);
