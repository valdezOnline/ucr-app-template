<?php

namespace App\Http\Middleware;

use App\Models\SiteStatusSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SiteStatusMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (
            $request->routeIs(
                'site-status.show',
                'site-status-config.*',
                'login',
                'logout',
                'cas.login',
                'cas.logout',
                'register',
                'password.*',
            ) ||
            $request->is('up')
        ) {
            return $next($request);
        }

        if (!Schema::hasTable('site_status_settings')) {
            return $next($request);
        }

        $siteStatus = SiteStatusSetting::current();
        if (!$siteStatus->isActive()) {
            return $next($request);
        }

        $user = $request->user();
        if ($user && ($user->hasRole('admin') || $user->is_admin)) {
            return $next($request);
        }

        return redirect()->route('site-status.show');
    }
}
