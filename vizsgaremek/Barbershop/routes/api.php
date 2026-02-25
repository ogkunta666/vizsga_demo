<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarberController;
use App\Http\Controllers\Api\BarberBookingController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\HairstyleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/ping', function () {
    return response()->json(['message' => 'pong']);
})->name('ping');

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/email/resend', [AuthController::class, 'resendVerification']);
    });
});



Route::get('/barbers', [BarberController::class, 'index']);
Route::get('/barbers/{barber}', [BarberController::class, 'show']);
Route::get('/barbers/{barber}/next-slot', [BarberController::class, 'nextSlot']);
Route::get('/barbers/{barber}/schedule', [BarberController::class, 'schedule']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/barbers', [BarberController::class, 'store']);
    Route::put('/barbers/{barber}', [BarberController::class, 'update']);
    Route::delete('/barbers/{barber}', [BarberController::class, 'destroy']);
});

// Borbély saját foglalásai
Route::middleware(['auth:sanctum', 'role:barber,admin'])->group(function () {
    Route::get('/barber/me', [BarberBookingController::class, 'me']);
    Route::get('/barber/bookings', [BarberBookingController::class, 'index']);
    Route::put('/barber/bookings/{booking}', [BarberBookingController::class, 'update']);
    Route::delete('/barber/bookings/{booking}', [BarberBookingController::class, 'destroy']);
});

Route::get('/availability', [BookingController::class, 'availability']);
Route::post('/bookings', [BookingController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']);

    Route::middleware('role:admin')->group(function () {
        Route::put('/bookings/{booking}', [BookingController::class, 'update']);
    });
});

Route::get('/hairstyles', [HairstyleController::class, 'index']);
Route::get('/hairstyles/{hairstyle}', [HairstyleController::class, 'show']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/hairstyles', [HairstyleController::class, 'store']);
    Route::put('/hairstyles/{hairstyle}', [HairstyleController::class, 'update']);
    Route::delete('/hairstyles/{hairstyle}', [HairstyleController::class, 'destroy']);
});

Route::get('/gallery', [GalleryController::class, 'index']);

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::post('/gallery', [GalleryController::class, 'store']);
    Route::delete('/gallery/{galleryImage}', [GalleryController::class, 'destroy']);

    // User management
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
