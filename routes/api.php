<?php

use App\Http\Controllers\AuthController;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

//Route::middleware(['auth:sanctum', 'admin'])->patch('/users/{id}/approve', [AuthController::class, 'approveUser']);
Route::patch('/users/{id}/approve', [AuthController::class, 'approveUser']);

//Route::middleware(['auth:sanctum', 'admin'])->get('/users/unapproved', [AuthController::class, 'unapprovedUsers']);
Route::get('/users/unapproved', [AuthController::class, 'unapprovedUsers']);


Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});
