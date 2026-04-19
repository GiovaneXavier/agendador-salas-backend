<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = env('AGENT_API_KEY');
        $provided = $request->header('X-Api-Key');

        if (empty($expected) || empty($provided) || !hash_equals($expected, $provided)) {
            return response()->json([
                'error'   => 'unauthorized',
                'message' => 'X-Api-Key inválida ou ausente.',
            ], 401);
        }

        return $next($request);
    }
}
