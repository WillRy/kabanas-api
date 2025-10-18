<?php

namespace App\Service;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;
use stdClass;

class JwtService
{
    // usado para gerar graceful period do token
    const SECONDS_GRACEFUL_REFRESH = 30;

    const SECONDS_VALID_ACCESS_TOKEN = 3600; // 1 hour

    const SECONDS_VALID_REFRESH = 25200; // 7 hours

    const COOKIE_SAME_SITE = 'Lax';

    private bool $useCookies = false;

    public function __construct(bool $useCookies = false)
    {
        $this->useCookies = $useCookies;
    }

    /**
     * Generate authentication JWT token
     */
    public function createJwt(
        int $userId,
        int $sessionId
    ): string {
        /** @var \App\AuthGuard\JwtGuard $authapi */
        $authapi = auth('api');

        return $authapi->setTTL(60)->claims(['session_id' => $sessionId])->tokenById($userId);
    }

    /**
     * Generate opaque refresh token
     */
    public function createRefreshToken(): string
    {
        return Str::random(64);
    }

    public function setCookie(string $name, string $value, bool $forceExpire = false, bool $httpOnly = true)
    {
        if (! $this->useCookies) {
            return;
        }

        setcookie($name, $value, [
            'expires' => $forceExpire ? time() - 3600 : time() + self::SECONDS_VALID_REFRESH,
            'path' => '/',
            'secure' => true,
            'httponly' => $httpOnly,
            'samesite' => self::COOKIE_SAME_SITE,
        ]);
    }

    /**
     * Register user authentication
     * persisting tokens in the database and
     * returning the generated tokens
     */
    public function doAuth(
        int $userId
    ): stdClass {

        $tokenSessionId = DB::table('token_sessions')->insertGetId([
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $createdTokens = new \stdClass;
        $createdTokens->session = DB::table('token_sessions')->where('id', $tokenSessionId)->first();
        $createdTokens->token = $this->createJwt($userId, $tokenSessionId);
        $createdTokens->refresh_token = $this->createRefreshToken();

        $refreshId = DB::table('refresh_token')
            ->insertGetId([
                'token_session_id' => $tokenSessionId,
                'user_id' => $userId,
                'token' => $createdTokens->refresh_token,
                'token_expiration' => date('Y-m-d H:i:s', strtotime('+'.self::SECONDS_VALID_REFRESH.'seconds')),
            ]);

        DB::table('auth_token')
            ->insert([
                'token_session_id' => $tokenSessionId,
                'user_id' => $userId,
                'token' => $createdTokens->token,
                'token_expiration' => date('Y-m-d H:i:s', strtotime('+'.self::SECONDS_VALID_ACCESS_TOKEN.'seconds')),
                'refresh_id' => $refreshId,
            ]);

        $this->setCookie('token', $createdTokens->token);
        $this->setCookie('refresh_token', $createdTokens->refresh_token);

        return $createdTokens;
    }

    /**
     * Checks if the user is logged in (valid token)
     */
    public function isLogged(string $token): bool
    {
        $isTokenValid = $this->validateToken($token);

        return $isTokenValid;
    }

    public function payloadToken(string $token): ?stdClass
    {
        $jwtParts = explode('.', $token);
        $jwtPayload = base64_decode($jwtParts[1]);

        return json_decode($jwtPayload);
    }

    /**
     * Check if authentication token is valid
     */
    public function validateToken(
        string $token
    ): bool {
        $payload = $this->payloadToken($token);

        $token = DB::table('auth_token')
            ->selectRaw('
                *,
                (token_expiration < ?) as expired
                ', [now()])
            ->where('token_session_id', '=', $payload->session_id)
            ->where('token', '=', $token)
            ->first();

        $validToken = ! empty($token) && $token->expired === 0;

        return $validToken;
    }

    /**
     * Retorna a expiração do token JWT
     */
    public function getTokenExpiration(string $token): ?string
    {
        $jwtParts = explode('.', $token);
        $jwtPayload = base64_decode($jwtParts[1]);
        $payload = json_decode($jwtPayload);

        return $payload->exp ? \DateTimeImmutable::createFromFormat('U', $payload->exp)->format('Y-m-d H:i:s') : null;
    }

    /**
     * Perform the refresh token process, including:
     * - Check token expiration
     * - Generate new access token
     * - Generate or reuse refresh token
     * - Invalidate old access token
     * - Invalidate old refresh token (mark used)
     */
    public function refreshToken(
        string $refreshToken
    ): stdClass {
        $this->removeExpiredTokens();

        $tokenInfo = DB::table('refresh_token')
            ->selectRaw('
              *,
              (token_expiration < ?) as expired
              ', [now()])
            ->where('token', '=', $refreshToken)
            ->first();

        $isRefreshValid = ! empty($tokenInfo) && $tokenInfo->expired === 0;

        if (! $isRefreshValid) {
            throw new \Exception('Invalid refresh token');
        }

        $tokenSessionId = $tokenInfo->token_session_id;

        $generatedTokens = new \stdClass;

        $currentValidToken = $this->getValidRefreshToken($tokenInfo->id);

        if (empty($currentValidToken)) {

            $generatedTokens->refresh_token = $this->createRefreshToken();

            $refreshTokenExpiration = date('Y-m-d H:i:s', strtotime('+7hour'));

            $refreshTokenId = DB::table('refresh_token')
                ->insertGetId([
                    'token_session_id' => $tokenSessionId,
                    'user_id' => $tokenInfo->user_id,
                    'token' => $generatedTokens->refresh_token,
                    'token_expiration' => $refreshTokenExpiration,
                    'refresh_id' => $tokenInfo->id,
                ]);
        } else {
            $generatedTokens->refresh_token = $currentValidToken->token;
            $refreshTokenId = $currentValidToken->id;
        }

        $generatedTokens->token = $this->createJwt($tokenInfo->user_id, $tokenSessionId);
        $expiracaoToken = $this->getTokenExpiration($generatedTokens->token);

        DB::table('auth_token')
            ->insert([
                'token_session_id' => $tokenSessionId,
                'user_id' => $tokenInfo->user_id,
                'token' => $generatedTokens->token,
                'token_expiration' => $expiracaoToken,
                'refresh_id' => $refreshTokenId,
            ]);

        if (empty($tokenInfo->used_at)) {
            // invalidate old token with graceful period to avoid race conditions
            DB::table('refresh_token')
                ->where([
                    'id' => $tokenInfo->id,
                ])
                ->update([
                    'used_at' => date('Y-m-d H:i:s'),
                    'token_expiration' => now()->addSeconds(self::SECONDS_GRACEFUL_REFRESH),
                ]);
        }

        $this->setCookie('token', $generatedTokens->token);
        $this->setCookie('refresh_token', $generatedTokens->refresh_token);

        return $generatedTokens;
    }

    /**
     * Log out the user based on a token
     */
    public function deleteLoginByToken(string $token): bool
    {
        $token = DB::table('auth_token')
            ->where('token', '=', $token)
            ->first();

        if (empty($token)) {
            return false;
        }

        DB::table('token_sessions')
            ->where('id', '=', $token->token_session_id)
            ->delete();

        return true;
    }

    /**
     * Force logout by clearing tokens
     */
    public function logoutTokens(): void
    {
        Auth::guard("api")->logout();

        $token = Request::bearerToken() ?? Cookie::get('token');

        // set cookie lax
        $this->setCookie('token', '', true);
        $this->setCookie('refresh_token', '', true);
        // $this->setCookie(CustomCSRF::$cookieName, '', true);

        if ($token) {
            $this->deleteLoginByToken($token);
        }
    }

    public function removeExpiredTokens(): void
    {
        DB::table('refresh_token')
            ->whereRaw('(token_expiration < ?)', [now()])
            ->delete();

        DB::table('auth_token')
            ->whereRaw('(token_expiration < ?)', [now()])
            ->delete();
    }

    public function deleteTokensBySession(int $tokenSessionId): void
    {
        DB::table('refresh_token')
            ->where('token_session_id', '=', $tokenSessionId)
            ->delete();

        DB::table('auth_token')
            ->where('token_session_id', '=', $tokenSessionId)
            ->delete();
    }

    public function getValidRefreshToken(int $parentRefreshId): ?object
    {
        return DB::table('refresh_token')
            ->where('refresh_id', '=', $parentRefreshId)
            ->whereRaw('(token_expiration > ?)', [now()])
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getCurrentSessionId(): ?int
    {
        $token = $this->tokenRequest();

        if (empty($token)) {
            return null;
        }

        $token = DB::table('auth_token')
            ->where('token', '=', $token)
            ->first();

        if (empty($token)) {
            return null;
        }

        return $token->token_session_id;
    }

    public static function tokenRequest(): ?string
    {
        return Request::bearerToken() ?? Cookie::get('token');
    }

    public function cleanOldData()
    {
        DB::table('token_sessions')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('auth_token')
                    ->whereRaw('auth_token.token_session_id = token_sessions.id')
                    ->whereRaw('auth_token.token_expiration >= ?', [now()]);
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('refresh_token')
                    ->whereRaw('refresh_token.token_session_id = token_sessions.id')
                    ->whereRaw('refresh_token.token_expiration >= ?', [now()]);
            })
            ->delete();
    }
}
