<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PeajeController;




Route::get('/', [PeajeController::class, 'index']);
Route::post('/upload-xml', [PeajeController::class, 'upload']);
