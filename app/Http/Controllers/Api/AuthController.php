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
use App\Service\JwtService;
use App\Service\ResponseJSON;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\PersonalAccessToken;

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

        $abilities = $user->userPermissions();

        $token = $user->createToken('auth_token')->plainTextToken;

        $useCookie = !empty($request->query('cookie'));

        $tokens = (new JwtService($useCookie))->doAuth($user->id);

        return ResponseJSON::getInstance()
            ->setMessage('Successfully logged in')
            ->setData([
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer',
                'abilities' => $abilities,
                'tokens' => $tokens,
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

        if (Auth::guard("sanctum")->check() && $request->user()->currentAccessToken()) {
            /** @var \App\Models\User $user */
            $user = $request->user();

            /** @var PersonalAccessToken $token */
            $token = $user->currentAccessToken();

            $token->delete();
        }

        Auth::guard("web")->logout();


        if (Auth::guard("api")->check()) {
            (new JwtService)->logoutTokens();
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

    public function refreshToken(Request $request)
    {
        try {
            $refreshToken = $request->input('refresh_token') ?? Cookie::get('refresh_token');

            if (empty($refreshToken)) {
                throw new \Exception('Invalid refresh token!', 401);
            }

            $newTokens = (new JwtService)->refreshToken($refreshToken);

            return (new ResponseJSON)->setData($newTokens)->render();
        } catch (\Exception $e) {

            (new JwtService)->logoutTokens();

            return (new ResponseJSON)->setError($e)->setStatusCode(401)->render();
        }
    }
}
