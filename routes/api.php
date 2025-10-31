<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\StationAuthController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\Admin\DashboardExportController;
use App\Http\Controllers\Station\StationRequestController;


// Pour les usagers
Route::get('/public/stations', [StationController::class, 'index']);
Route::get('/public/stations/{id}', [StationController::class, 'show']);

// --- Auth Admin ---
Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::get('/admin/stations/export', [DashboardExportController::class, 'export']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/stations', [AdminRequestController::class, 'index']);
    Route::get('/admin/stations/{id}/history', [AdminRequestController::class, 'history']);
    Route::post('/admin/stations/{id}/disable', [AdminRequestController::class, 'disable']);
    Route::post('/admin/stations/{id}/reactivate', [AdminRequestController::class, 'reactivate']);
    Route::post('/admin/stations/{id}/approve', [AdminRequestController::class, 'approve']);
    Route::post('/admin/stations/{id}/reject', [AdminRequestController::class, 'reject']);
    // Route::get('/admin/stations/export', [AdminRequestController::class, 'export']);
    Route::get('/admin/stations/stats', [AdminRequestController::class, 'stats']);
});

// --- Auth Station ---
Route::post('/public/stations/register', [StationController::class, 'register']);
Route::post('/stations/login', [StationAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/stations/status-change', [StationRequestController::class, 'updateFuelStatus']);
    Route::post('/stations/logout', [StationAuthController::class, 'logout']);
});

