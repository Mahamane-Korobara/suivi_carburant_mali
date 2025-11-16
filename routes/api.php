<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\StationAuthController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\Admin\DashboardExportController;
use App\Http\Controllers\Station\StationRequestController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\ReportControllerUsager;
use App\Http\Controllers\Admin\AdminNotificationController;

// Pour les usagers
Route::get('/public/stations', [StationController::class, 'index']);
Route::post('/public/stations/{stationId}/report', [ReportControllerUsager::class, 'store']);
Route::get('/public/stations/{id}', [StationController::class, 'show']);
Route::get('/public/fuel-types', [StationController::class, 'typeFuel']);
// --- Auth Admin ---
Route::post('/admin/login', [AdminAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/admin/logout', [AdminAuthController::class, 'logout']);
    Route::get('/admin/stations', [AdminRequestController::class, 'index']);
    Route::get('/admin/stations/{id}/history', [AdminRequestController::class, 'history']);
    Route::post('/admin/stations/{id}/disable', [AdminRequestController::class, 'disable']);
    Route::post('/admin/stations/{id}/reactivate', [AdminRequestController::class, 'reactivate']);
    Route::post('/admin/stations/{id}/approve', [AdminRequestController::class, 'approve']);
    Route::post('/admin/stations/{id}/reject', [AdminRequestController::class, 'reject']);
    Route::get('/admin/stations/reports', [ReportController::class, 'index']);
    Route::get('/admin/stations/reports/{id}', [ReportController::class, 'show']);
    Route::delete('/admin/stations/reports/{id}', [ReportController::class, 'destroy']);
    Route::get('/admin/stations/notifications', [AdminNotificationController::class, 'index']);
    Route::post('/admin/stations/notifications/{id}/read', [AdminNotificationController::class, 'markAsRead']);
    Route::get('/admin/stations/export', [DashboardExportController::class, 'export']);
    Route::get('/admin/stations/stats', [AdminRequestController::class, 'stats']);
    Route::get('admin/stations/fuel-stats', [AdminRequestController::class, 'fuelStats']);
});

// --- Auth Station ---
Route::post('/public/stations/register', [StationController::class, 'register']);
Route::post('/stations/login', [StationAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // Gestion des statuts de carburant
    Route::get('/stations/fuel-statuses', [StationRequestController::class, 'getFuelStatuses']);
    Route::post('/stations/status-change', [StationRequestController::class, 'updateFuelStatus']);
    Route::get('/stations/fuel-history', [StationRequestController::class, 'getFuelHistory']);
    
    // Déconnexion
    Route::post('/stations/logout', [StationAuthController::class, 'logout']);
});

