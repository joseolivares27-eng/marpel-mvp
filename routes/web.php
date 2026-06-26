<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\WorkOrderPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('technician.dashboard') : redirect()->route('login'));
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/tecnico', [TechnicianController::class, 'dashboard'])->name('technician.dashboard');
    Route::get('/tecnico/avisos', [TechnicianController::class, 'notices'])->name('technician.notices.index');
    Route::get('/tecnico/avisos/{notice}', [TechnicianController::class, 'showNotice'])->name('technician.notices.show');
    Route::post('/tecnico/avisos/{notice}/iniciar', [TechnicianController::class, 'startNotice'])->name('technician.notices.start');
    Route::post('/tecnico/revisiones/{review}/iniciar', [TechnicianController::class, 'startReview'])->name('technician.reviews.start');
    Route::get('/tecnico/partes/{workOrder}', [TechnicianController::class, 'showWorkOrder'])->name('technician.work-orders.show');
    Route::post('/tecnico/partes/{workOrder}', [TechnicianController::class, 'updateWorkOrder'])->name('technician.work-orders.update');
    Route::get('/tecnico/partes/{workOrder}/firma', [TechnicianController::class, 'signature'])->name('technician.work-orders.signature');
    Route::post('/tecnico/partes/{workOrder}/firma', [TechnicianController::class, 'storeSignature'])->name('technician.work-orders.signature.store');
    Route::get('/partes/{workOrder}/pdf', WorkOrderPdfController::class)->name('work-orders.pdf.download');
});
