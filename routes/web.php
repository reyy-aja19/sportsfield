<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\CekLogin;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');
Route::redirect('/dashboard', '/admin/dashboard');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout.perform');
Route::get('/logout-success', [AdminController::class, 'logoutSuccess'])->name('logout.success');

Route::middleware([CekLogin::class, 'role:superadmin,admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::middleware('role:superadmin')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/create', [AdminController::class, 'userCreate'])->name('users.create');
        Route::post('/users', [AdminController::class, 'userStore'])->name('users.store');
        Route::get('/users/{user}/edit', [AdminController::class, 'userEdit'])->name('users.edit');
        Route::put('/users/{user}', [AdminController::class, 'userUpdate'])->name('users.update');
        Route::post('/users/{user}/toggle', [AdminController::class, 'userToggle'])->name('users.toggle');
        Route::delete('/users/{user}', [AdminController::class, 'userDelete'])->name('users.delete');
    });

    Route::get('/lapangan', [AdminController::class, 'courts'])->name('courts');
    Route::get('/lapangan/create', [AdminController::class, 'courtCreate'])->name('courts.create');
    Route::post('/lapangan', [AdminController::class, 'courtStore'])->name('courts.store');
    Route::get('/lapangan/{lapangan}/edit', [AdminController::class, 'courtEdit'])->name('courts.edit');
    Route::put('/lapangan/{lapangan}', [AdminController::class, 'courtUpdate'])->name('courts.update');
    Route::delete('/lapangan/{lapangan}', [AdminController::class, 'courtDelete'])->name('courts.delete');

    Route::get('/booking', [AdminController::class, 'bookings'])->name('bookings');
    Route::post('/booking', [AdminController::class, 'bookingStore'])->name('bookings.store');
    Route::post('/booking/{booking}/toggle', [AdminController::class, 'bookingToggle'])->name('bookings.toggle');
    Route::delete('/booking/{booking}', [AdminController::class, 'bookingDelete'])->name('bookings.delete');

    Route::get('/open-match', [AdminController::class, 'openMatches'])->name('openmatches');
    Route::post('/open-match', [AdminController::class, 'openMatchStore'])->name('openmatches.store');
    Route::put('/open-match/{openMatch}', [AdminController::class, 'openMatchUpdate'])->name('openmatches.update');
    Route::post('/open-match/{openMatch}/toggle', [AdminController::class, 'openMatchToggle'])->name('openmatches.toggle');
    Route::delete('/open-match/{openMatch}', [AdminController::class, 'openMatchDelete'])->name('openmatches.delete');

    Route::get('/reviews', [AdminController::class, 'reviews'])->name('reviews');
    Route::post('/reviews/{review}/reply', [AdminController::class, 'reviewReply'])->name('reviews.reply');
    Route::post('/reviews/{review}/toggle', [AdminController::class, 'reviewToggle'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [AdminController::class, 'reviewDelete'])->name('reviews.delete');

    Route::get('/rewards', [AdminController::class, 'rewards'])->name('rewards');
    Route::post('/rewards', [AdminController::class, 'rewardStore'])->name('rewards.store');
    Route::get('/rewards/{reward}/edit', [AdminController::class, 'rewardEdit'])->name('rewards.edit');
    Route::put('/rewards/{reward}', [AdminController::class, 'rewardUpdate'])->name('rewards.update');
    Route::post('/rewards/{reward}/toggle', [AdminController::class, 'rewardToggle'])->name('rewards.toggle');
    Route::delete('/rewards/{reward}', [AdminController::class, 'rewardDelete'])->name('rewards.delete');

    Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
    Route::post('/payments/{payment}/verify', [AdminController::class, 'paymentVerify'])->name('payments.verify');

    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/reports/export/csv', [AdminController::class, 'exportReportsCsv'])->name('reports.export.csv');
    Route::get('/reports/export/excel', [AdminController::class, 'exportReportsExcel'])->name('reports.export.excel');
    Route::get('/export/csv', [AdminController::class, 'exportReportsCsv'])->name('export.csv');
    Route::get('/export/excel', [AdminController::class, 'exportReportsExcel'])->name('export.excel');
    Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
    Route::post('/profile', [AdminController::class, 'profileUpdate'])->name('profile.update');
    Route::get('/logout', [AdminController::class, 'logoutConfirm'])->name('logout');
});
