<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Presentation\Http\Controllers\AdminEmailDispatchController;
use Modules\Core\Presentation\Http\Controllers\AdminImpersonationController;
use Modules\Core\Presentation\Http\Controllers\ApplicationController;
use Modules\Core\Presentation\Http\Controllers\Auth\ConfirmPasswordController;
use Modules\Core\Presentation\Http\Controllers\Auth\ForgotPasswordController;
use Modules\Core\Presentation\Http\Controllers\Auth\LoginController;
use Modules\Core\Presentation\Http\Controllers\Auth\RegisterController;
use Modules\Core\Presentation\Http\Controllers\Auth\ResetPasswordController;
use Modules\Core\Presentation\Http\Controllers\AuthConfigController;
use Modules\Core\Presentation\Http\Controllers\AuthDebugController;
use Modules\Core\Presentation\Http\Controllers\CasAuthController;
use Modules\Core\Presentation\Http\Controllers\HomeController;
use Modules\Core\Presentation\Http\Controllers\RoleController;
use Modules\Core\Presentation\Http\Controllers\SiteStatusController;
use Modules\Core\Presentation\Http\Controllers\UserController;
use Modules\Core\Presentation\Http\Controllers\UserDashboardController;

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

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');

Route::get('/password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
Route::post('/password/confirm', [ConfirmPasswordController::class, 'confirm']);

Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware(['auth', 'auth.type']);
Route::get('/dashboard', [UserDashboardController::class, 'index'])->name('dashboard')->middleware(['auth', 'auth.type']);
Route::post('/dashboard/applications/order', [UserDashboardController::class, 'saveApplicationOrder'])->name('dashboard.applications.order')->middleware(['auth', 'auth.type']);
Route::post('/dashboard/shortcuts', [UserDashboardController::class, 'storeShortcut'])->name('dashboard.shortcuts.store')->middleware(['auth', 'auth.type']);
Route::put('/dashboard/shortcuts/{shortcutId}', [UserDashboardController::class, 'updateShortcut'])->name('dashboard.shortcuts.update')->middleware(['auth', 'auth.type']);
Route::delete('/dashboard/shortcuts/{shortcutId}', [UserDashboardController::class, 'destroyShortcut'])->name('dashboard.shortcuts.destroy')->middleware(['auth', 'auth.type']);
Route::get('/profile', [UserDashboardController::class, 'profile'])->name('profile')->middleware(['auth', 'auth.type']);

// User Management Routes
Route::middleware(['auth', 'auth.type'])->group(function () {
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/sync-with-hr', [UserController::class, 'syncWithHr'])->name('users.sync-with-hr');
    Route::post('users/import-from-hr', [UserController::class, 'importFromHr'])->name('users.import-from-hr');
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('users/{user}/remove-role', [UserController::class, 'removeRole'])->name('users.remove-role');

    // Role Management Routes
    Route::resource('roles', RoleController::class);
    Route::patch('roles/{role}/toggle-status', [RoleController::class, 'toggleStatus'])->name('roles.toggle-status');
    Route::post('roles/{role}/assign-user', [RoleController::class, 'assignToUser'])->name('roles.assign-user');
    Route::delete('roles/{role}/remove-user', [RoleController::class, 'removeFromUser'])->name('roles.remove-user');

    // Application Management Routes
    Route::resource('applications', ApplicationController::class);
    Route::patch('applications/{application}/toggle-status', [ApplicationController::class, 'toggleStatus'])->name('applications.toggle-status');
    Route::post('applications/{application}/assign-role', [ApplicationController::class, 'assignRole'])->name('applications.assign-role');
    Route::delete('applications/{application}/remove-role', [ApplicationController::class, 'removeRole'])->name('applications.remove-role');
    Route::post('applications/{application}/assign-user', [ApplicationController::class, 'assignUser'])->name('applications.assign-user');
    Route::delete('applications/{application}/remove-user', [ApplicationController::class, 'removeUser'])->name('applications.remove-user');
    // API Management Routes
    Route::delete('applications/{application}/revoke-api', [ApplicationController::class, 'revokeApiAccess'])->name('applications.revoke-api');
    Route::post('applications/{application}/regenerate-api', [ApplicationController::class, 'regenerateApiCredentials'])->name('applications.regenerate-api');

    // Authentication Configuration Routes (Admin only)
    Route::get('admin/auth-config', [AuthConfigController::class, 'index'])->name('auth-config.index');
    Route::put('admin/auth-config', [AuthConfigController::class, 'update'])->name('auth-config.update');
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
