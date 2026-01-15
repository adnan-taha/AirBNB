<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\WalletController;


//Health endpoint
Route::get('/health', [AuthController::class, 'health']);
Route::post('/store-token', [AuthController::class, 'storeToken'])->middleware(['auth:sanctum','role:admin']);


//*******AUTH*******
Route::middleware(['auth:sanctum', 'role:admin'])->post('/register/admin', [AuthController::class, 'register_admin']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users/all', [AuthController::class, 'getAllUsers']);

Route::middleware(['auth:sanctum', 'role:admin'])->get('/users/unapproved', [AuthController::class, 'unapprovedUsers']);

Route::middleware(['auth:sanctum', 'role:admin'])->patch('/users/{id}/approve', [AuthController::class, 'approveUser']);

Route::get('/users/admin/{id}', [AuthController::class, 'getUserByIdUn'])->middleware(['auth:sanctum','role:admin']);
Route::get('/users/{id}', [AuthController::class, 'getUserById']);



Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

//*******APARTMENTS*******
Route::get('/apartments', [ApartmentController::class, 'index']);
Route::get('/apartments/{id}', [ApartmentController::class, 'show']);
Route::get('/apartments/{id}/rating', [ApartmentController::class, 'rating']);

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
    Route::get('/apartment/unapproved', [ApartmentController::class, 'unapprovedApartment']);
    Route::get('/apartments/admin/{id}', [ApartmentController::class, 'showUn']);
});

//*******PHOTO*******
Route::middleware('auth:sanctum')->post('/photos/upload', [PhotoController::class, 'uploadImages']);
Route::get('/photos', [PhotoController::class, 'getAllImages'])->middleware('auth:sanctum');

//*******BOOKINGS*******
Route::middleware(['auth:sanctum', 'approved'])->group(function () {

    Route::middleware('role:tenant')->group(function () {
        Route::post('/apartments/{id}/book', [BookingController::class, 'store']);
        Route::get('/bookings', [BookingController::class, 'tenantBookings']);
        Route::put('/bookings/{id}', [BookingController::class, 'update']);
    });

    Route::middleware('role:owner')->group(function () {
        Route::post('/bookings/{id}/approve', [BookingController::class, 'approve']);
        Route::post('/bookings/{id}/reject', [BookingController::class, 'reject']);
        Route::get('/owner/bookings', [BookingController::class, 'ownerBookings']);
    });
    Route::middleware(['auth:sanctum', 'approved'])->group(function () {
        Route::get('/apartments/{id}/bookings', [BookingController::class, 'apartmentBookings']);

        // Cancel booking (tenant or owner)
        Route::post('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    });

    Route::middleware('auth:sanctum')->get(
        '/bookings/{id}',
        [BookingController::class, 'show']
    );


});

//*******REVIEW*******
Route::middleware(['auth:sanctum', 'approved'])->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});

Route::get('/apartments/{id}/reviews', [ReviewController::class, 'apartmentReviews']);

//*******FAVORITE*******
Route::middleware(['auth:sanctum', 'approved'])->group(function () {
    Route::post('/apartments/{id}/favorite', [FavoriteController::class, 'store']);
    Route::delete('/apartments/{id}/favorite', [FavoriteController::class, 'destroy']);
    Route::get('/favorites', [FavoriteController::class, 'index']);

});

//*******MONEY💸💵*******
Route::middleware(['auth:sanctum', 'approved', 'role:admin'])
    ->post('/wallet/{id}', [WalletController::class, 'topUp']);









