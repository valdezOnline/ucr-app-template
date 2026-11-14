<?php

namespace Modules\Core\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\UserActivityTracker;

class TrackUserActivity
{
    public function __construct(
        private readonly UserActivityTracker $tracker,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user) {
            $this->tracker->touchLastSeen($user);
        }

        return $next($request);
    }
}
