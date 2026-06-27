<?php

use App\Http\Controllers\Api\LucasApiController;
use App\Http\Controllers\Api\NotionApiController;
use Illuminate\Support\Facades\Route;

Route::post('/lucas/avisos', LucasApiController::class)->name('api.lucas.notices.store');

Route::post('/notion/clientes', [NotionApiController::class, 'clientes'])->name('api.notion.customers.store');
Route::post('/notion/avisos', [NotionApiController::class, 'avisos'])->name('api.notion.notices.store');
Route::post('/notion/contratos', [NotionApiController::class, 'contratos'])->name('api.notion.contracts.store');
