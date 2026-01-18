<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('payment')->group(function () {
    Route::post('/create/{order}', [PaymentController::class, 'createPayment'])
        ->name('payment.create')
        ->middleware('auth');

    Route::get('/check-status/{order}', [PaymentController::class, 'checkStatus'])
        ->name('payment.check-status')
        ->middleware('auth');
});

// Public routes for Midtrans callbacks
Route::prefix('midtrans')->group(function () {
    Route::post('/notification', [PaymentController::class, 'notification'])
        ->name('midtrans.notification');

    Route::get('/finish', [PaymentController::class, 'finish'])
        ->name('midtrans.finish');

    Route::get('/unfinish', [PaymentController::class, 'unfinish'])
        ->name('midtrans.unfinish');

    Route::get('/error', [PaymentController::class, 'error'])
        ->name('midtrans.error');
});
require __DIR__.'/auth.php';
