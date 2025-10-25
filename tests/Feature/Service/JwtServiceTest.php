<?php

namespace Tests\Feature\Service;

use App\Models\User;
use App\Service\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class JwtServiceTest extends \Tests\TestCase
{
    use RefreshDatabase;

    public function test_if_can_do_auth(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $this->assertIsObject($tokens);
        $this->assertObjectHasProperty('session', $tokens);
        $this->assertObjectHasProperty('token', $tokens);
        $this->assertObjectHasProperty('refresh_token', $tokens);

        $this->assertDatabaseHas('token_sessions', [
            'user_id' => $user->id,
            'id' => $tokens->session->id,
        ]);

        $this->assertDatabaseHas('refresh_token', [
            'token_session_id' => $tokens->session->id,
            'user_id' => $user->id,
            'token' => $tokens->refresh_token,
        ]);

        $refresh = DB::table('refresh_token')->where('token', $tokens->refresh_token)->first();

        $this->assertDatabaseHas('auth_token', [
            'token_session_id' => $tokens->session->id,
            'user_id' => $user->id,
            'token' => $tokens->token,
            'refresh_id' => $refresh->id,
        ]);
    }

    public function test_if_can_do_auth_with_cookies(): void
    {
        $this->seed();

        $user = User::factory(1)->create()->first();

        $response = $this->post('/api/auth/login?cookie=1', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(201);

    }

    public function test_if_can_check_if_user_is_logged(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $isLogged = $jwt->isLogged($tokens->token);

        $this->assertTrue($isLogged);
    }

    public function test_if_method_is_logged_returns_false_for_invalid_token(): void
    {
        $jwt = new JwtService;
        $isLogged = $jwt->isLogged('invalid.token.here');

        $this->assertFalse($isLogged);
    }

    public function test_if_refresh_token_fails_with_inexistent_token(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        DB::table('refresh_token')
            ->where('token', $tokens->refresh_token)
            ->delete();

        $this->expectException(\Exception::class);
        $jwt->refreshToken($tokens->refresh_token);
    }

    public function test_if_refresh_token_fails_with_expired_token(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        // Manually expire the refresh token
        DB::table('refresh_token')
            ->where('token', $tokens->refresh_token)
            ->update(['token_expiration' => now()->subMinutes(1)]);

        $this->expectException(\Exception::class);
        $jwt->refreshToken($tokens->refresh_token);
    }

    public function test_if_refresh_token_works(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $newTokens = $jwt->refreshToken($tokens->refresh_token);

        $this->assertIsObject($newTokens);
        $this->assertObjectHasProperty('token', $newTokens);
        $this->assertObjectHasProperty('refresh_token', $newTokens);

        $this->assertDatabaseHas('token_sessions', [
            'user_id' => $user->id,
            'id' => $tokens->session->id,
        ]);

        $this->assertDatabaseHas('refresh_token', [
            'token_session_id' => $tokens->session->id,
            'user_id' => $user->id,
            'token' => $newTokens->refresh_token,
        ]);

        $refresh = DB::table('refresh_token')->where('token', $newTokens->refresh_token)->first();

        $this->assertDatabaseHas('auth_token', [
            'token_session_id' => $tokens->session->id,
            'user_id' => $user->id,
            'token' => $newTokens->token,
            'refresh_id' => $refresh->id,
        ]);

        $this->assertDatabaseMissing('refresh_token', [
            'token' => $tokens->refresh_token,
            'uset_at' => null,
        ]);
    }

    public function test_if_refresh_token_returns_same_refresh_with_sequencial_refresh(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $currentRefresh = $jwt->refreshToken($tokens->refresh_token);

        $finalRefresh = $jwt->refreshToken($tokens->refresh_token);
        $this->assertEquals($finalRefresh->refresh_token, $currentRefresh->refresh_token);
    }

    public function test_if_can_delete_login_by_token(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $jwt->deleteLoginByToken($tokens->token);

        $this->assertDatabaseMissing('token_sessions', [
            'id' => $tokens->session->id,
        ]);

        $this->assertDatabaseMissing('auth_token', [
            'token' => $tokens->token,
        ]);

        $this->assertDatabaseMissing('refresh_token', [
            'token' => $tokens->refresh_token,
        ]);
    }

    public function test_if_delete_login_by_token_exit_when_token_is_invalid(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        // Invalid token
        $return = $jwt->deleteLoginByToken('invalid.token.here');

        $this->assertFalse($return);
    }

    public function test_if_can_logout_by_tokens(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $this->actingAs($user, 'api');

        $this->post('/api/logout', [], ['Authorization' => 'Bearer '.$tokens->token]);

        $this->assertDatabaseMissing('token_sessions', [
            'id' => $tokens->session->id,
        ]);

        $this->assertDatabaseMissing('auth_token', [
            'token' => $tokens->token,
        ]);

        $this->assertDatabaseMissing('refresh_token', [
            'token' => $tokens->refresh_token,
        ]);
    }

    public function test_if_can_delete_expired_tokens(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        // Manually expire the auth token
        DB::table('auth_token')
            ->where('token', $tokens->token)
            ->update(['token_expiration' => now()->subMinutes(1)]);

        DB::table('refresh_token')
            ->where('token', $tokens->refresh_token)
            ->update(['token_expiration' => now()->subMinutes(1)]);

        $jwt->removeExpiredTokens();

        $this->assertDatabaseMissing('auth_token', [
            'token' => $tokens->token,
        ]);

        $this->assertDatabaseMissing('refresh_token', [
            'token' => $tokens->refresh_token,
        ]);
    }

    public function test_if_can_delete_tokens_by_session_id(): void
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        $jwt->deleteTokensBySession($tokens->session->id);

        $this->assertDatabaseMissing('auth_token', [
            'token' => $tokens->token,
        ]);

        $this->assertDatabaseMissing('refresh_token', [
            'token' => $tokens->refresh_token,
        ]);
    }

    public function test_if_can_clean_old_sessions()
    {
        $this->seed();

        $user = User::first();

        $jwt = new JwtService;
        $tokens = $jwt->doAuth($user->id);

        DB::table('auth_token')
            ->where('token', $tokens->token)
            ->update(['token_expiration' => now()->subMinutes(1)]);

        DB::table('refresh_token')
            ->where('token', $tokens->refresh_token)
            ->update(['token_expiration' => now()->subMinutes(1)]);

        $jwt->cleanOldData();

        $this->assertDatabaseMissing('token_sessions', [
            'id' => $tokens->session->id,
        ]);

        $this->assertDatabaseMissing('auth_token', [
            'token' => $tokens->token,
        ]);

        $this->assertDatabaseMissing('refresh_token', [
            'token' => $tokens->refresh_token,
        ]);
    }
}
