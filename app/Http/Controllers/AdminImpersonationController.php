<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminImpersonationController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.type']);
    }

    /**
     * Start impersonating a user.
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        $admin = $request->user();

        if (!$admin || (!$admin->hasRole('admin') && !$admin->is_admin)) {
            abort(403, 'Only administrators can start impersonation.');
        }

        try {
            ImpersonationService::start($admin, $user, $request);

            return redirect()->route('dashboard')->with(
                'success',
                'You are now impersonating ' . $user->full_name . '.'
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Failed to start impersonation', [
                'admin_user_id' => $admin->id,
                'target_user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Unable to start impersonation right now.');
        }
    }

    /**
     * Stop the active impersonation session.
     */
    public function stop(Request $request): RedirectResponse
    {
        if (!ImpersonationService::isImpersonating()) {
            return redirect()->back()->with('warning', 'No active impersonation session was found.');
        }

        try {
            ImpersonationService::stop($request);

            return redirect()->route('home')->with(
                'success',
                'Impersonation stopped. You are now back in your admin account.'
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Failed to stop impersonation', [
                'current_user_id' => optional($request->user())->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')->with('error', 'Unable to stop impersonation. Please sign in again.');
        }
    }
}
