<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\CarbonRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\EmissionOverviewController;
use App\Http\Controllers\Admin\AnalyticsReportController;
use App\Http\Controllers\Admin\MitigationStrategyController;
use App\Http\Controllers\Admin\AlertController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ForecastController;

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


    // User Management

    Route::get('/admin/users', [UserManagementController::class, 'index'])
        ->name('admin.users');

    Route::get('/admin/users/create', [UserManagementController::class, 'create'])
        ->name('admin.users.create');

    Route::post('/admin/users', [UserManagementController::class, 'store'])
        ->name('admin.users.store');


    // Emission Overview

    Route::get(
        '/admin/emissions',
        [EmissionOverviewController::class, 'index']
    )->name('admin.emissions');

    Route::get(
        '/admin/emissions/export',
        [EmissionOverviewController::class, 'export']
    )->name('admin.emissions.export');


    // Analytics & Reports

    Route::get(
        '/admin/analytics-reports',
        [AnalyticsReportController::class, 'index']
    )->name('admin.analytics');

    Route::get(
        '/admin/analytics-reports/export/pdf',
        [AnalyticsReportController::class, 'exportPDF']
    )->name('admin.analytics.export.pdf');

    Route::get(
        '/admin/analytics-reports/export/excel',
        [AnalyticsReportController::class, 'exportExcel']
    )->name('admin.analytics.export.excel');


    // Forecasting

    Route::get(
        '/admin/forecasting',
        [ForecastController::class, 'index']
    )->name('admin.forecasting');


    // Alerts & Notifications

    Route::get(
        '/admin/alerts-notifications',
        [AlertController::class, 'index']
    )->name('admin.alerts');

    Route::patch(
        '/admin/alerts/{alert}/read',
        [AlertController::class, 'markAsRead']
    )->name('admin.alerts.read');

    Route::delete(
        '/admin/alerts/{alert}',
        [AlertController::class, 'destroy']
    )->name('admin.alerts.destroy');


    // Mitigation Strategies

    Route::get(
        '/admin/mitigation-strategies',
        [MitigationStrategyController::class, 'index']
    )->name('admin.mitigation');

    Route::post(
        '/admin/mitigation-strategies',
        [MitigationStrategyController::class, 'store']
    )->name('admin.mitigation.store');


    // User Profile

    Route::get(
        '/admin/users/{g_suite}',
        [UserManagementController::class, 'show']
    )->name('admin.users.show');


    // User Carbon Records

    Route::get(
        '/admin/users/{g_suite}/records',
        [UserManagementController::class, 'carbonRecords']
    )->name('admin.users.records');


    // Edit User

    Route::get(
        '/admin/users/{g_suite}/edit',
        [UserManagementController::class, 'edit']
    )->name('admin.users.edit');


    // Update User

    Route::put(
        '/admin/users/{g_suite}',
        [UserManagementController::class, 'update']
    )->name('admin.users.update');


    // Delete User

    Route::delete(
        '/admin/users/{g_suite}',
        [UserManagementController::class, 'destroy']
    )->name('admin.users.destroy');


    // Settings

    Route::get(
        '/admin/settings',
        [SettingsController::class, 'index']
    )->name('admin.settings');

    Route::put(
        '/admin/settings',
        [SettingsController::class, 'update']
    )->name('admin.settings.update');

});


Route::middleware('auth')->group(function () {

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');

});


require __DIR__.'/auth.php';