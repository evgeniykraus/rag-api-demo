<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse
     */
    public function handle(Request $request, Closure $next): JsonResponse
    {
        $apiKey = $request->header('x-api-key');

        if (!$apiKey) {
            return response()->json([
                'error' => 'API key is required',
                'message' => __('exceptions.middleware.api_key_required')
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        if ($apiKey !== config('app.api_key')) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => __('exceptions.middleware.api_key_invalid')
            ], SymfonyResponse::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
