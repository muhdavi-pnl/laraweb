<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * @group Authentication
 */
class PasswordResetController extends Controller
{
    /**
     * Request a password reset link
     *
     * Always returns the same response so that registered emails cannot be enumerated.
     *
     * @unauthenticated
     */
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => 'If the email is registered, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the password using a token
     *
     * @unauthenticated
     */
    public function reset(ResetPasswordRequest $request, AuditLogger $audit): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($audit) {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
                $audit->record('password_reset', $user, userId: $user->id);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'success' => false,
                'message' => 'The reset token is invalid or has expired.',
            ], 422);
        }

        return response()->json(['success' => true, 'message' => 'Password has been reset.']);
    }
}
