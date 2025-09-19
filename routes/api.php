<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\API\RoomController;
use App\Http\Controllers\API\ReservationController;
use App\Http\Controllers\API\FixedScheduleController;

Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

Route::middleware('auth:api')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout
    Route::post('/logout', [LogoutController::class, 'logout']);

    Route::get('/rooms', [RoomController::class, 'index']);
    Route::post('/rooms', [RoomController::class, 'store']);
    Route::get('/rooms/{room}', [RoomController::class, 'show']);
    Route::put('/rooms/{room}', [RoomController::class, 'update']);
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy']);

    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::put('/reservations/{reservation}', [ReservationController::class, 'update']);
    Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy']);

    // Custom routes untuk approve/reject reservasi (admin)
    Route::get('/reservations/{reservation}/approve', [ReservationController::class, 'approve']);
    Route::get('/reservations/{reservation}/reject', [ReservationController::class, 'reject']);

    Route::get('/fixed_schedules', [FixedScheduleController::class, 'index']);
    Route::post('/fixed_schedules', [FixedScheduleController::class, 'store']);
    Route::get('/fixed_schedules/{fixedSchedule}', [FixedScheduleController::class, 'show']);
    Route::put('/fixed_schedules/{fixedSchedule}', [FixedScheduleController::class, 'update']);
    Route::delete('/fixed_schedules/{fixedSchedule}', [FixedScheduleController::class, 'destroy']);
});
