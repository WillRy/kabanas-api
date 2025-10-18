<?php

namespace Tests\Feature\Controllers\Api;

use App\Models\Otp;
use App\Models\User;
use App\Service\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIfLoginValidationWorks(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => '',
            'password' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function testIfLoginFailsWithInvalidCredentials(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/login', [
            'email' => 'teste@teste.com',
            'password' => 'wrongpassword',
        ]);

        $this->assertEquals(401, $response->status());
        $response->assertJsonFragment(['message' => 'Invalid credentials']);
    }

    public function testIfLoginSucceedsWithValidCredentials(): void
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
                ],
                'access_token',
                'token_type',
                'abilities',
                'tokens' => [
                    'session',
                    'token',
                    'refresh_token',
                ],
            ],
        ]);
    }

    public function testIfUserEndpointReturnsAuthenticatedUser(): void
    {
        $this->seed();

        $user = User::getMasterAdmin();

        $this->actingAs($user, 'sanctum');

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

    public function testIfUserEndpointReturnsErrorWhenNotHaveAuthenticatedUser(): void
    {

        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    public function testIfLogoutWorksWhenAuthenticated(): void
    {
        $this->seed();

        $user = $user = User::getMasterAdmin();

        $token = $user->createToken('secrettoken')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(204);
        $response->assertNoContent();

        $tokens = (new JwtService())->doAuth($user->id);

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $tokens->token
        ]);
        $response->assertStatus(204);
        $response->assertNoContent();

        $this->actingAs($user, "web");

        $response = $this->postJson('/api/logout');
        $response->assertStatus(204);
        $response->assertNoContent();
    }

    public function testIfLogoutNotWorksWhenAuthenticated(): void
    {

        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    public function testIfStartPasswordResetValidationWorks(): void
    {
        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => '',
        ]);


        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }


    public function testIfStartPasswordResetFailsWithWrongUser(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin2@admin.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function testIfStartPasswordResetWorks(): void
    {
        $this->seed();

        $response = $this->postJson('/api/auth/start-password-reset', [
            'email' => 'admin@admin.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment(['message' => 'Password reset OTP sent to your email']);
    }

    public function testIfPasswordResetValidationWorks(): void
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

    public function testIfPasswordResetFailsWithWrongEmail(): void
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


    public function testIfPasswordResetFailsWithWrongOtp(): void
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

    public function testIfPasswordResetWorks(): void
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

    public function testIfRefreshTokenFailsWithInvalidToken(): void
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

    public function testIfRefreshTokenWorks(): void
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

    public function testIfRefreshTokenFailsWithoutToken(): void
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
