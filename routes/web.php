<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\WorkOrderPdfController;
use App\Services\GoogleCalendarService;
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
    Route::get('/tecnico/avisos/nuevo', [TechnicianController::class, 'createNotice'])->name('technician.notices.create');
    Route::post('/tecnico/avisos/nuevo', [TechnicianController::class, 'storeNotice'])->name('technician.notices.store');
    Route::get('/tecnico/avisos/{notice}', [TechnicianController::class, 'showNotice'])->name('technician.notices.show');
    Route::post('/tecnico/avisos/{notice}/iniciar', [TechnicianController::class, 'startNotice'])->name('technician.notices.start');
    Route::get('/tecnico/revisiones/nueva', [TechnicianController::class, 'createReview'])->name('technician.reviews.create');
    Route::post('/tecnico/revisiones/nueva', [TechnicianController::class, 'storeReview'])->name('technician.reviews.store');
    Route::post('/tecnico/revisiones/{review}/iniciar', [TechnicianController::class, 'startReview'])->name('technician.reviews.start');
    Route::get('/tecnico/partes-cerrados', [TechnicianController::class, 'closedWorkOrders'])->name('technician.work-orders.closed');
    Route::get('/tecnico/partes/{workOrder}', [TechnicianController::class, 'showWorkOrder'])->name('technician.work-orders.show');
    Route::post('/tecnico/partes/{workOrder}', [TechnicianController::class, 'updateWorkOrder'])->name('technician.work-orders.update');
    Route::get('/tecnico/partes/{workOrder}/firma', [TechnicianController::class, 'signature'])->name('technician.work-orders.signature');
    Route::post('/tecnico/partes/{workOrder}/firma', [TechnicianController::class, 'storeSignature'])->name('technician.work-orders.signature.store');
    Route::get('/partes/{workOrder}/pdf', WorkOrderPdfController::class)->name('work-orders.pdf.download');

    Route::get('/admin/google-calendar/connect', function (GoogleCalendarService $service) {
        return redirect()->away($service->getAuthUrl());
    })->name('google-calendar.connect');

    Route::get('/admin/google-calendar/callback', function (GoogleCalendarService $service) {
        $code = request()->query('code');

        if ($code) {
            $service->handleAuthCallback($code);
        }

        return redirect('/admin/google-calendar-settings')->with('status', 'google-calendar-connected');
    })->name('google-calendar.callback');

    Route::post('/admin/google-calendar/disconnect', function (GoogleCalendarService $service) {
        $service->disconnect();

        return redirect('/admin/google-calendar-settings');
    })->name('google-calendar.disconnect');
});
