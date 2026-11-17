<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects JWTs issued before the user's last password change (see User::$token_version).
 */
class EnsureTokenIsCurrent
{
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('api');
        $user = $guard->user();

        if ($user && app('tymon.jwt.parser')->hasToken() && $guard->payload()->get('tv') !== $user->token_version) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }
}
