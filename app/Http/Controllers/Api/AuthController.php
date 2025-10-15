<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BaseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\PasswordResetRequest;
use App\Http\Requests\Api\Auth\SendPasswordResetRequest;
use App\Http\Resources\UserResource;
use App\Mail\SendPasswordReset;
use App\Models\User;
use App\Service\ResponseJSON;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials)) {
            throw new BaseException('Invalid credentials', 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $abilities = $user->roles->flatMap->permissions->pluck('name')->toArray();

        $token = $user->createToken('auth_token')->plainTextToken;

        return ResponseJSON::getInstance()
            ->setMessage('Successfully logged in')
            ->setData([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'abilities' => $abilities,
            ])
            ->setStatusCode(201)
            ->render();
    }

    public function user(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return ResponseJSON::getInstance()
            ->setData(new UserResource($user))
            ->render();
    }

    public function logout(Request $request): Response
    {
        if (Auth::guard('sanctum')->check() && $request->bearerToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        if (! Auth::guard('sanctum')->check() && Auth::check()) {
            Auth::logout();
        }

        return response()->noContent();
    }

    public function sendPasswordReset(SendPasswordResetRequest $request): JsonResponse
    {
        $data = (new User)->generateResetPasswordOtp($request->email);

        Mail::to($data->user->email)->send(new SendPasswordReset($data->user, $data->otp->code));

        return ResponseJSON::getInstance()
            ->setMessage('Password reset OTP sent to your email')
            ->render();
    }

    public function passwordReset(PasswordResetRequest $request): JsonResponse
    {
        (new User)->resetPasswordWithOtp(
            $request->email,
            $request->otp,
            $request->password
        );

        return ResponseJSON::getInstance()
            ->setMessage('Password reset successfully')
            ->render();
    }
}
