<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\CarbonRecordController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/carbon-records', [CarbonRecordController::class, 'index'])
        ->name('carbon.index');

    Route::get('/carbon-records/create', [CarbonRecordController::class, 'create'])
        ->name('carbon.create');

    Route::post('/carbon-records', [CarbonRecordController::class, 'store'])
        ->name('carbon.store');

    // User Management page
    Route::get('/admin/users', [UserManagementController::class, 'index'])
    ->name('admin.users');
    
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';