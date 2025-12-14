<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\PhotoController;

Route::get('/health', function () {
    return response()->json(['status' => 'healthy']);
});


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


Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{id}', [ApartmentController::class, 'show']);


// Owner (must be authenticated, approved and owner role)
Route::middleware(['auth:sanctum', 'approved', 'role:owner'])->group(function () {
    Route::post('/apartments', [ApartmentController::class, 'store']);
    Route::put('/apartments/{id}', [ApartmentController::class, 'update']);
    Route::delete('/apartments/{id}', [ApartmentController::class, 'destroy']);
    Route::get('/owner/apartments', [ApartmentController::class, 'ownerIndex']);
});

// Admin approve (admin + auth + approved)
Route::middleware(['auth:sanctum', 'approved', 'role:admin'])->group(function () {
    Route::post('/apartments/{id}/approve', [ApartmentController::class, 'approve']);
    Route::get('/apartment/unapproved', [ApartmentController::class, 'getUnapproved']);
});

//photo
//Route::middleware('auth:sanctum')->post('/images/upload', [PhotoController::class, 'uploadImages']);
Route::post('/photos/upload', [PhotoController::class, 'uploadImages']);



