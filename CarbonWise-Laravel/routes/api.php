<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CarbonRecordController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\EmailVerificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// EMAIL VERIFICATION
Route::get(
    '/email/verify/{id}/{hash}',
    [EmailVerificationController::class, 'verify']
)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('api.verification.verify');


Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/carbon-records', [CarbonRecordController::class, 'index']);
    Route::post('/carbon-records', [CarbonRecordController::class, 'store']);
    Route::get('/carbon-records/{id}', [CarbonRecordController::class, 'show']);
    Route::put('/carbon-records/{id}', [CarbonRecordController::class, 'update']);
    Route::delete('/carbon-records/{id}', [CarbonRecordController::class, 'destroy']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'changePassword']);
    Route::post('/profile-picture', [ProfileController::class, 'uploadProfilePicture']);

    Route::get('/campus-rankings', [DashboardController::class, 'campusRankings']);
    Route::get('/department-rankings', [DashboardController::class, 'departmentRankings']);
});