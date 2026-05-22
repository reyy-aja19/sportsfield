<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LapanganController;
use App\Http\Controllers\OpenMatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;

// --- RUTE PUBLIK (Bisa diakses tanpa login) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginApi']);
Route::get('/fields', [LapanganController::class, 'apiIndex']);
Route::get('/fields/{id}', [LapanganController::class, 'apiShow']);

Route::get('/booked-slots', [LapanganController::class, 'getBookedSlots']);

Route::get('/admin/all-bookings', [PaymentController::class, 'apiAllBookings']);

// --- RUTE PRIVAT (Wajib bawa Token / Login) ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Rute User Profile
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rute Booking (Sekarang aman pake $request->user())
    Route::get('/bookings', [LapanganController::class, 'apiBookings']); 
    Route::post('/booking', [LapanganController::class, 'apiStoreBooking']);
    Route::post('/booking/{id}/update-status', [LapanganController::class, 'apiUpdateStatus']);

    

    // Rute Match
    Route::get('/matches', [OpenMatchController::class, 'index']);
    Route::post('/matches', [OpenMatchController::class, 'store']);
    Route::post('/matches/{id}/join', [OpenMatchController::class, 'join']);
});