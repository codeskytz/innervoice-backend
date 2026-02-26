<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConfessionController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthenticateWithToken;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/verify', [AuthController::class, 'verifyOtp']);
Route::post('auth/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('auth/reset-password', [AuthController::class, 'resetPassword']);

// Confession routes - Public (viewable without auth)
Route::get('confessions', [ConfessionController::class, 'index']); // Get all confessions (feed)
Route::get('confessions/{id}', [ConfessionController::class, 'show']); // Get single confession

// Protected routes
Route::middleware(AuthenticateWithToken::class)->group(function () {
    // Auth protected endpoints
    Route::get('me', [AuthController::class, 'me']);

    // Confession routes - Protected (require authentication)
    Route::post('confessions', [ConfessionController::class, 'store']); // Create confession
    Route::get('my-confessions', [ConfessionController::class, 'myConfessions']); // Get user's confessions
    Route::put('confessions/{id}', [ConfessionController::class, 'update']); // Update confession
    Route::delete('confessions/{id}', [ConfessionController::class, 'destroy']); // Delete confession
});

// Public categories endpoint
Route::get('categories', [CategoryController::class, 'getAll']);

// Admin routes (separate from user routes)
Route::post('admin/login', [AdminAuthController::class, 'login']);

// Protected admin routes (require admin authentication)
Route::middleware([AuthenticateWithToken::class, AdminMiddleware::class])->group(function () {
    // Admin auth
    Route::get('admin/me', [AdminAuthController::class, 'me']);
    Route::post('admin/logout', [AdminAuthController::class, 'logout']);

    // User management
    Route::get('admin/users', [AdminController::class, 'getAllUsers']);
    Route::get('admin/users/{id}', [AdminController::class, 'getUser']);
    Route::put('admin/users/{id}', [AdminController::class, 'updateUser']);
    Route::delete('admin/users/{id}', [AdminController::class, 'deleteUser']);

    // Dashboard stats
    Route::get('admin/stats', [AdminController::class, 'getStats']);

    // Category management
    Route::post('admin/categories', [CategoryController::class, 'store']);
    Route::get('admin/categories/{id}', [CategoryController::class, 'getCategory']);
    Route::put('admin/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('admin/categories/{id}', [CategoryController::class, 'delete']);
});

