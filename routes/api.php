<?php

use App\Http\Controllers\Api\LucasApiController;
use Illuminate\Support\Facades\Route;

Route::post('/lucas/avisos', LucasApiController::class)->name('api.lucas.notices.store');
