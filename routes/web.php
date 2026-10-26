<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminEmailDispatchController;
use App\Http\Controllers\AdminImpersonationController;
use App\Http\Controllers\AuthDebugController;
use App\Http\Controllers\CasAuthController;
use App\Http\Controllers\SiteStatusController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/help', function () {
    return view('help');
})->name('help');
Route::get('/site-status', [SiteStatusController::class, 'show'])->name('site-status.show');

Route::get('/test-auth', [AuthDebugController::class, 'testAuth'])->name('test-auth');
Route::get('/debug/switch-to-cas', [AuthDebugController::class, 'switchToCas'])->name('debug.switch-cas');
Route::get('/debug/switch-to-laravel', [AuthDebugController::class, 'switchToLaravel'])->name('debug.switch-laravel');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home')->middleware(['auth', 'auth.type']);
Route::get('/dashboard', [App\Http\Controllers\UserDashboardController::class, 'index'])->name('dashboard')->middleware(['auth', 'auth.type']);
Route::post('/dashboard/applications/order', [App\Http\Controllers\UserDashboardController::class, 'saveApplicationOrder'])->name('dashboard.applications.order')->middleware(['auth', 'auth.type']);
Route::post('/dashboard/shortcuts', [App\Http\Controllers\UserDashboardController::class, 'storeShortcut'])->name('dashboard.shortcuts.store')->middleware(['auth', 'auth.type']);
Route::put('/dashboard/shortcuts/{shortcutId}', [App\Http\Controllers\UserDashboardController::class, 'updateShortcut'])->name('dashboard.shortcuts.update')->middleware(['auth', 'auth.type']);
Route::delete('/dashboard/shortcuts/{shortcutId}', [App\Http\Controllers\UserDashboardController::class, 'destroyShortcut'])->name('dashboard.shortcuts.destroy')->middleware(['auth', 'auth.type']);
Route::get('/profile', [App\Http\Controllers\UserDashboardController::class, 'profile'])->name('profile')->middleware(['auth', 'auth.type']);

// User Management Routes
Route::middleware(['auth', 'auth.type'])->group(function () {
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::patch('users/{user}/sync-with-hr', [App\Http\Controllers\UserController::class, 'syncWithHr'])->name('users.sync-with-hr');
    Route::post('users/import-from-hr', [App\Http\Controllers\UserController::class, 'importFromHr'])->name('users.import-from-hr');
    Route::patch('users/{user}/toggle-status', [App\Http\Controllers\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/assign-role', [App\Http\Controllers\UserController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('users/{user}/remove-role', [App\Http\Controllers\UserController::class, 'removeRole'])->name('users.remove-role');

    // Role Management Routes
    Route::resource('roles', App\Http\Controllers\RoleController::class);
    Route::patch('roles/{role}/toggle-status', [App\Http\Controllers\RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
    Route::post('roles/{role}/assign-user', [App\Http\Controllers\RoleController::class, 'assignToUser'])->name('roles.assign-user');
    Route::delete('roles/{role}/remove-user', [App\Http\Controllers\RoleController::class, 'removeFromUser'])->name('roles.remove-user');

    // Application Management Routes
    Route::resource('applications', App\Http\Controllers\ApplicationController::class);
    Route::patch('applications/{application}/toggle-status', [App\Http\Controllers\ApplicationController::class, 'toggleStatus'])->name('applications.toggle-status');
    Route::post('applications/{application}/assign-role', [App\Http\Controllers\ApplicationController::class, 'assignRole'])->name('applications.assign-role');
    Route::delete('applications/{application}/remove-role', [App\Http\Controllers\ApplicationController::class, 'removeRole'])->name('applications.remove-role');
    Route::post('applications/{application}/assign-user', [App\Http\Controllers\ApplicationController::class, 'assignUser'])->name('applications.assign-user');
    Route::delete('applications/{application}/remove-user', [App\Http\Controllers\ApplicationController::class, 'removeUser'])->name('applications.remove-user');
    // API Management Routes
    Route::delete('applications/{application}/revoke-api', [App\Http\Controllers\ApplicationController::class, 'revokeApiAccess'])->name('applications.revoke-api');
    Route::post('applications/{application}/regenerate-api', [App\Http\Controllers\ApplicationController::class, 'regenerateApiCredentials'])->name('applications.regenerate-api');

    // Authentication Configuration Routes (Admin only)
    Route::get('admin/auth-config', [App\Http\Controllers\AuthConfigController::class, 'index'])->name('auth-config.index');
    Route::put('admin/auth-config', [App\Http\Controllers\AuthConfigController::class, 'update'])->name('auth-config.update');
    Route::get('admin/site-status', [SiteStatusController::class, 'edit'])->name('site-status-config.edit');
    Route::put('admin/site-status', [SiteStatusController::class, 'update'])->name('site-status-config.update');
    Route::get('admin/email-dispatches', [AdminEmailDispatchController::class, 'index'])->name('admin.email-dispatches.index');
    Route::get('admin/email-dispatches/{emailDispatchLog}', [AdminEmailDispatchController::class, 'show'])->name('admin.email-dispatches.show');
    Route::post('admin/email-dispatches/{emailDispatchLog}/retry', [AdminEmailDispatchController::class, 'retry'])->name('admin.email-dispatches.retry');

    // Admin impersonation routes
    Route::post('admin/impersonation/stop', [AdminImpersonationController::class, 'stop'])->name('admin.impersonation.stop');
    Route::post('admin/impersonation/{user}', [AdminImpersonationController::class, 'start'])->name('admin.impersonation.start');
});

Route::get('/debug-cas', [AuthDebugController::class, 'debugCas'])->name('debug-cas');

// CAS Authentication routes (always available to prevent route errors)
// Routes will redirect appropriately based on current auth configuration
Route::get('/cas/login', [CasAuthController::class, 'login'])->name('cas.login');
Route::get('/cas/logout', [CasAuthController::class, 'logout'])->name('cas.logout');
