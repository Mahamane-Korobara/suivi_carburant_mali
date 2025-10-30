<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\StationAuthController;
use App\Http\Controllers\Admin\StationRequestController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\Admin\DashboardExportController;


// Pour les usagers
Route::get('/public/stations', [StationController::class, 'index']);
Route::get('/public/stations/{id}', [StationController::class, 'show']);

// --- Auth Admin ---
Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::get('/admin/stations/export', [DashboardExportController::class, 'export']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/stations', [StationRequestController::class, 'index']);
    Route::get('/admin/stations/{id}/history', [StationRequestController::class, 'history']);
    Route::post('/admin/stations/{id}/disable', [StationRequestController::class, 'disable']);
    Route::post('/admin/stations/{id}/reactivate', [StationRequestController::class, 'reactivate']);
    Route::post('/admin/stations/{id}/approve', [StationRequestController::class, 'approve']);
    Route::post('/admin/stations/{id}/reject', [StationRequestController::class, 'reject']);
    // Route::get('/admin/stations/export', [StationRequestController::class, 'export']);
    Route::get('/admin/stations/stats', [StationRequestController::class, 'stats']);
});

// --- Auth Station ---
Route::post('/stations/register', [StationController::class, 'register']);
Route::post('/stations/login', [StationAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/stations/logout', [StationAuthController::class, 'logout']);
});

