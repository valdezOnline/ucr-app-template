<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public authentication routes for external applications
Route::prefix('auth')->group(function () {
    // Authenticate a user with credentials
    Route::post('/authenticate', [AuthController::class, 'authenticate'])->name('api.auth.authenticate');

    // Verify a user's access to a specific application
    Route::post('/verify-access', [AuthController::class, 'verifyAccess'])->name('api.auth.verify-access');

    // Get user information (requires valid authentication)
    Route::post('/user-info', [AuthController::class, 'getUserInfo'])->name('api.auth.user-info');

    // Check if user has specific role
    Route::post('/check-role', [AuthController::class, 'checkRole'])->name('api.auth.check-role');

    // Health check for API
    Route::get('/health', [AuthController::class, 'health'])->name('api.auth.health');
});

// Protected routes that require API token authentication
Route::middleware('auth:sanctum')->group(function () {
    // Get current user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // User management routes
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('api.users.index');
        Route::get('/{user}', [UserController::class, 'show'])->name('api.users.show');
    });
});
