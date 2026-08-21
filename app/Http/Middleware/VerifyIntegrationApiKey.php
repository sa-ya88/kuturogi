<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = config('integration.api_key');

        if (empty($apiKey)) {
            abort(503, 'Integration API is not configured.');
        }

        if (! hash_equals((string) $apiKey, (string) $request->header('X-Integration-Api-Key'))) {
            abort(401, 'Invalid integration API key.');
        }

        return $next($request);
    }
}
