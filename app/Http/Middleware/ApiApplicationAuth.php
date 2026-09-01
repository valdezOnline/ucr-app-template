<?php

namespace App\Http\Middleware;

use App\Models\Application;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiApplicationAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get API credentials from request
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');
        $apiSecret = $request->header('X-API-Secret') ?? $request->input('api_secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'success' => false,
                'message' => 'API credentials required'
            ], 401);
        }

        // Verify application credentials
        $application = Application::where('api_key', $apiKey)->first();

        if (!$application) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        if (!$application->verifyApiCredentials($apiKey, $apiSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid API credentials'
            ], 401);
        }

        // Check domain restrictions
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');
        $domain = $origin ?: ($referer ? parse_url($referer, PHP_URL_HOST) : null);

        if (!$application->isDomainAllowed($domain)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized domain'
            ], 403);
        }

        // Add application to request for use in controllers
        $request->attributes->set('api_application', $application);

        return $next($request);
    }
}
