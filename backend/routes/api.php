<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuildingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\VenueController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json([
        'message' => 'pong',
        'app' => config('app.name'),
        'time' => now()->toIso8601String(),
    ]);
});

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('role:admin')->get('/dashboard/admin', [DashboardController::class, 'admin']);
    Route::middleware('role:admin,officer')->get('/dashboard/officer', [DashboardController::class, 'officer']);
    Route::middleware('role:staff')->get('/dashboard/staff', [DashboardController::class, 'staff']);

    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{event}', [EventController::class, 'show']);
    Route::get('/events/{event}/tasks', [TaskController::class, 'index']);
    Route::get('/events/{event}/documents', [DocumentController::class, 'index']);
    Route::get('/events/{event}/report', [ReportController::class, 'event']);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download']);

    Route::get('/schedule', [ScheduleController::class, 'index']);
    Route::get('/venues', [VenueController::class, 'index']);

    Route::get('/my-tasks', [TaskController::class, 'myTasks']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);

    Route::middleware('role:admin,officer')->group(function () {
        Route::get('/analytics', [AnalyticsController::class, 'index']);
        Route::put('/events/{event}/staff', [EventController::class, 'updateStaff']);
        Route::get('/schedule/export', [ScheduleController::class, 'export']);
        Route::post('/events/{event}/tasks', [TaskController::class, 'store']);
        Route::put('/tasks/{task}', [TaskController::class, 'update']);
        Route::patch('/tasks/{task}', [TaskController::class, 'update']);
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy']);
        Route::post('/events/{event}/documents', [DocumentController::class, 'store']);
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy']);
        Route::get('/users', [UserController::class, 'index']);
    });

    // The admin owns the schedule: event details (date, time, venue, ...) are admin-only.
    // Officers handle preparation: tasks, staff, documents and reports.
    Route::middleware('role:admin')->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{event}', [EventController::class, 'update']);
        Route::patch('/events/{event}', [EventController::class, 'update']);
        Route::delete('/events/{event}', [EventController::class, 'destroy']);
        Route::get('/departments', [EventController::class, 'departments']);
        Route::post('/venues', [VenueController::class, 'store']);
        Route::put('/venues/{venue}', [VenueController::class, 'update']);
        Route::delete('/venues/{venue}', [VenueController::class, 'destroy']);
        Route::post('/venues/{venue}/merge', [VenueController::class, 'merge']);
        Route::post('/buildings', [BuildingController::class, 'store']);
        Route::put('/buildings/{building}', [BuildingController::class, 'update']);
        Route::delete('/buildings/{building}', [BuildingController::class, 'destroy']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::patch('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });
});
