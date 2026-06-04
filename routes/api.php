<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// CONTROLLERS
use App\Http\Controllers\LapanganController;
use App\Http\Controllers\OpenMatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminRequestController;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'loginApi']);

// Fields / Venue
Route::get('/fields', [LapanganController::class, 'apiIndex']);
Route::get('/fields/{id}', [LapanganController::class, 'apiShow']);

// Booking helpers
Route::get('/booked-slots', [LapanganController::class, 'getBookedSlots']);

// Notifications (kalau mau dibuat auth, pindahkan ke middleware)
Route::get('/notifications/{user_id}', [NotificationController::class, 'index']);

// Admin request
Route::post('/request-admin', [AdminRequestController::class, 'store']);

// Payment webhook / callback
Route::post('/payment-success', [PaymentController::class, 'paymentSuccess']);

// Admin data (optional public - kalau sensitif pindahkan ke auth)
Route::get('/admin/all-bookings', [PaymentController::class, 'apiAllBookings']);


/*
|--------------------------------------------------------------------------
| PRIVATE ROUTES (SANCTUM AUTH)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rewards
    Route::get('/rewards', [RewardController::class, 'apiIndex']);
    Route::post('/rewards/redeem', [RewardController::class, 'apiRedeem']);

    // Bookings
    Route::get('/bookings', [LapanganController::class, 'apiBookings']);
    Route::post('/booking', [LapanganController::class, 'apiStoreBooking']);

    Route::post('/booking/{id}/update-status', [LapanganController::class, 'apiUpdateStatus']);

    // Open Match
    Route::get('/matches', [OpenMatchController::class, 'index']);
    Route::post('/matches', [OpenMatchController::class, 'store']);
    Route::post('/matches/{id}/join', [OpenMatchController::class, 'join']);
});