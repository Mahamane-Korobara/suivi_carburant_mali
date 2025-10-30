<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\StationAuthController;
use App\Http\Controllers\Admin\StationRequestController;
use App\Http\Controllers\StationController;

// --- Auth Admin ---
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/stations', [StationRequestController::class, 'index']);
    Route::get('/admin/stations/{id}/history', [StationRequestController::class, 'history']);
    Route::post('/admin/stations/{id}/disable', [StationRequestController::class, 'disable']);
    Route::post('/admin/stations/{id}/reactivate', [StationRequestController::class, 'reactivate']);
    Route::post('/admin/stations/{id}/approve', [StationRequestController::class, 'approve']);
    Route::post('/admin/stations/{id}/reject', [StationRequestController::class, 'reject']);
});

// --- Auth Station ---
Route::post('/stations/register', [StationController::class, 'register']);
Route::post('/stations/login', [StationAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/stations/logout', [StationAuthController::class, 'logout']);
});

