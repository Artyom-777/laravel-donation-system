<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:login')
        ->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/password', [PasswordController::class, 'update'])
        ->middleware('throttle:5,1')
        ->name('password.update');
    Route::get('/campaigns', [CampaignController::class, 'index'])->name('campaigns');
    Route::view('/campaigns/create', 'campaigns.create')->name('campaigns.create');
    Route::post('/campaigns', [CampaignController::class, 'store'])->name('campaigns.store');
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('/campaigns/{campaign}/donations', [DonationController::class, 'storeIntent'])
        ->middleware('throttle:10,1')
        ->name('campaigns.donations.store');
    Route::post('/donations/{donation}/confirm', [DonationController::class, 'confirm'])
        ->middleware('throttle:10,1')
        ->name('donations.confirm');
    Route::get('/donations/payment-return/{donation}', [DonationController::class, 'paymentReturn'])
        ->name('donations.payment.return');
    Route::get('/donations/thank-you/{donation}', [DonationController::class, 'thankYou'])
        ->name('donations.thank-you');
    Route::get('/donations/export', [DonationController::class, 'export'])->name('donations.export');
    Route::get('/donations', [DonationController::class, 'index'])->name('donations');
    Route::get('/donors', [DonorController::class, 'index'])->name('donors');
    Route::get('/donors/create', [DonorController::class, 'create'])->name('donors.create');
    Route::post('/donors', [DonorController::class, 'store'])->name('donors.store');
    Route::get('/users', [UserController::class, 'index'])->name('users');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::post('/stripe/webhook', [DonationController::class, 'webhook'])->name('stripe.webhook');
