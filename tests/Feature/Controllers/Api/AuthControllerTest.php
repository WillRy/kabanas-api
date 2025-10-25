<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Otp;
use App\Service\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_login_validation_works(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_if_login_fails_with_invalid_credentials(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'teste@teste.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertEquals(401, $response->status());
        $response->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function test_if_login_succeeds_with_valid_credentials(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'permissions',
                ],
                'access_token',
                'token_type',
                'tokens' => [
                    'session',
                    'token',
                    'refresh_token',
                ],
            ],
        ]);
    }

    public function test_if_user_endpoint_returns_authenticated_user(): void
    {
        $this->seed();

        $this->actingAsAdmin('sanctum');

        $response = $this->getJson('/api/user');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'avatar',
            ],
        ]);
    }

    public function test_if_user_endpoint_returns_error_when_not_have_authenticated_user(): void
    {

        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function test_if_logout_works_when_authenticated(): void
    {
        $this->seed();

        $user = $this->getAdmin();

        $token = $user->createToken('secrettoken')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertStatus(204);
        $response->assertNoContent();

        $tokens = (new JwtService)->doAuth($user->id);

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer '.$tokens->token,
        ]);
        $response->assertStatus(204);
        $response->assertNoContent();

        $this->actingAs($user, 'web');

        $response = $this->postJson('/api/logout');
        $response->assertStatus(204);
        $response->assertNoContent();
    }

    public function test_if_logout_not_works_when_authenticated(): void
    {

        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function test_if_start_password_reset_validation_works(): void
    {
        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_if_start_password_reset_fails_with_wrong_user(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin2@admin.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_if_start_password_reset_works(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin@admin.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Password reset OTP sent to your email']);
    }

    public function test_if_password_reset_validation_works(): void
    {
        $response = $this->postJson('/api/auth/password-reset', [
            'email' => '',
            'otp' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'otp', 'password']);
    }

    public function test_if_password_reset_fails_with_wrong_email(): void
    {
        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin@admin.com',
        ]);

        $response = $this->postJson('/api/auth/password-reset', [
            'email' => 'admin2@admin.com',
            'otp' => '',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_if_password_reset_fails_with_wrong_otp(): void
    {
        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin@admin.com',
        ]);

        $response = $this->postJson('/api/auth/password-reset', [
            'email' => 'admin@admin.com',
            'otp' => '123',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['otp']);
    }

    public function test_if_password_reset_works(): void
    {
        $this->seed();

        Mail::fake();
        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin@admin.com',
        ]);
        Mail::assertQueued(\App\Mail\SendPasswordReset::class, 1);

        $otpByUser = Otp::where('type', Otp::TYPE_PASSWORD_RESET)->whereHas('user', function ($query) {
            $query->where('email', '=', 'admin@admin.com');
        })->latest()->first();

        $response = $this->postJson('/api/auth/password-reset', [
            'email' => 'admin@admin.com',
            'otp' => $otpByUser->code,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(200);
    }

    public function test_if_refresh_token_fails_with_invalid_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => 'invalidtoken',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Invalid refresh token']);
    }

    public function test_if_refresh_token_works(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);

        $data = $response->json('data');

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => $data['tokens']['refresh_token'],
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'data' => [
                'token',
                'refresh_token',
            ],
        ]);
    }

    public function test_if_refresh_token_fails_without_token(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@admin.com',
            'password' => 'password',
        ]);

        $data = $response->json('data');

        $response = $this->postJson('/api/auth/refresh', [
            'refresh_token' => null,
        ]);

        $response->assertStatus(401);
    }
}
