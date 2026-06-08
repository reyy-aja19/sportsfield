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
use App\Http\Controllers\ChatController;

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

// Notifications
Route::get('/notifications/{user_id}', [NotificationController::class, 'index']);

// FIX: Menggunakan Route::match untuk menangani bug downgrade POST ke GET saat redirect SSL hosting
Route::match(['get', 'post'], '/request-admin', [AdminRequestController::class, 'store']);

// Endpoint Webhook disamakan dengan URL di Dashboard Midtrans
Route::post('/midtrans-callback', [PaymentController::class, 'paymentSuccess']);

// Admin data (optional public)
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
    Route::get('/matches/{matchId}/messages', [ChatController::class, 'getMessages']);
    Route::post('/matches/{matchId}/messages', [ChatController::class, 'sendMessage']);
});