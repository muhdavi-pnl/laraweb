<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Authentication
 */
class AuthController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Log in
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! $token = Auth::guard('api')->attempt($request->validated())) {
            $this->audit->record('login_failed', null, null, ['email' => $request->input('email')], userId: null);

            return response()->json([
                'success' => false,
                'message' => 'Email or password is incorrect.',
            ], 401);
        }

        $this->audit->record('login');

        return $this->tokenResponse($token);
    }

    /**
     * Current user and profile
     */
    public function me(): UserResource
    {
        return new UserResource(Auth::guard('api')->user()->load([
            'student.user', 'student.advisor.user', 'student.province', 'student.regency', 'student.district', 'student.village',
            'lecturer',
        ]));
    }

    /**
     * Refresh the access token
     */
    public function refresh(): JsonResponse
    {
        return $this->tokenResponse(Auth::guard('api')->refresh());
    }

    /**
     * Log out (invalidates the token)
     */
    public function logout(): JsonResponse
    {
        $this->audit->record('logout');
        Auth::guard('api')->logout();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    private function tokenResponse(string $token): JsonResponse
    {
        $guard = Auth::guard('api');

        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
            'user' => new UserResource($guard->user()),
        ]);
    }
}
