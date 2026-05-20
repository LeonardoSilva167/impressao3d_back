<?php

use App\Http\Controllers\PncpController;
use Illuminate\Support\Facades\Route;


Route::post('/edital', [PncpController::class, 'buscarEdital']);
