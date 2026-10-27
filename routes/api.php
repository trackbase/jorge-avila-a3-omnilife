<?php

use App\Http\Controllers\Api\PagoSimuladoController;
use Illuminate\Support\Facades\Route;

Route::post('/pagos/simular', PagoSimuladoController::class)
    ->middleware('throttle:20,1')
    ->name('api.pagos.simular');
