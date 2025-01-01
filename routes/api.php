<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;


// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// User
Route::middleware('auth:api')->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/info', [UserController::class, 'getUserInfo']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
    Route::patch('/users/change-password', [UserController::class, 'changePassword'])->name('users.changePassword');
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
});

// Roles
Route::middleware('auth:api')->group(function () {
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
});

// Appointments
Route::middleware('auth:api')->group(function () {
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::get('/appointments/doctor/{doctor_id}', [AppointmentController::class, 'getAppointmentsByDoctor']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
});

// Children
Route::middleware('auth:api')->group(function () {
    Route::get('/children/{userId}', [ChildController::class, 'index']);
    Route::post('/children', [ChildController::class, 'store']);
});