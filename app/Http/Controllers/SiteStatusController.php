<?php

namespace App\Http\Controllers;

use App\Models\SiteStatusSetting;
use App\Services\AuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteStatusController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'auth.type'])->except('show');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user || (!$user->hasRole('admin') && !$user->is_admin)) {
                abort(403, 'Access denied. Admin role required.');
            }
            return $next($request);
        })->except('show');
    }

    public function show()
    {
        $siteStatus = SiteStatusSetting::current();

        return view('site-status.show', compact('siteStatus'));
    }

    public function edit()
    {
        $siteStatus = SiteStatusSetting::current();

        return view('admin.site-status', compact('siteStatus'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:' . implode(',', SiteStatusSetting::availableModes()),
            'message' => 'nullable|string|max:2000',
        ]);

        if ($validated['mode'] === SiteStatusSetting::MODE_CUSTOM_MESSAGE && blank($validated['message'] ?? null)) {
            return redirect()
                ->route('site-status-config.edit')
                ->withErrors(['message' => 'A custom message is required when "Customizable Message" is selected.'])
                ->withInput();
        }

        $siteStatus = SiteStatusSetting::current();
        $siteStatus->update([
            'mode' => $validated['mode'],
            'message' => blank($validated['message'] ?? null) ? null : trim($validated['message']),
            'updated_by' => auth()->id(),
        ]);

        $targetAuthType = $siteStatus->isActive() ? 'laravel' : 'cas';
        $currentAuthType = AuthenticationService::getType();

        if ($currentAuthType !== $targetAuthType) {
            AuthenticationService::persistAuthType($targetAuthType);

            if ($targetAuthType === 'cas') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with(
                        'success',
                        'Site status updated. Authentication was automatically switched to CAS. Please continue with "Sign In with CAS".'
                    );
            }
        }

        return redirect()
            ->route('site-status-config.edit')
            ->with(
                'success',
                'Site status mode has been updated successfully. Authentication is now set to ' .
                    strtoupper($targetAuthType) . '.'
            );
    }
}
