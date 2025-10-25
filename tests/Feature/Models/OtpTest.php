<?php

namespace Tests\Feature\Models;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_if_otp_can_be_created()
    {
        $this->seed();

        $user = User::inRandomOrder()->first();

        $otp = (new Otp)->createOtpByUser($user->id, Otp::TYPE_PASSWORD_RESET);

        $this->assertInstanceOf(Otp::class, $otp);

        $this->assertDatabaseHas('otps', [
            'user_id' => $user->id,
            'code' => $otp->code,
            'type' => $otp->type,
        ]);
    }

    public function test_if_otp_can_be_owned_by_user()
    {
        $this->seed();

        $user = User::inRandomOrder()->first();

        $otp = (new Otp)->createOtpByUser($user->id, Otp::TYPE_PASSWORD_RESET);

        $this->assertInstanceOf(User::class, $otp->user);
        $this->assertEquals($user->id, $otp->user->id);
    }

    public function test_if_otp_can_be_validated()
    {
        $this->seed();

        $user = User::inRandomOrder()->first();

        $otp = (new Otp)->createOtpByUser($user->id, Otp::TYPE_PASSWORD_RESET);

        $validatedOtp = (new Otp)->validateOtp($otp->code, Otp::TYPE_PASSWORD_RESET);

        $this->assertInstanceOf(Otp::class, $validatedOtp);
        $this->assertEquals($otp->id, $validatedOtp->id);
    }

    public function test_if_otp_validation_fails_when_expired()
    {
        $this->seed();

        $user = User::inRandomOrder()->first();

        $otp = (new Otp)->createOtpByUser($user->id, Otp::TYPE_PASSWORD_RESET, -3);

        $expiredOtp = (new Otp)->validateOtp($otp->code, Otp::TYPE_PASSWORD_RESET);
        $this->assertNull($expiredOtp);
    }

    public function test_if_otp_validation_fails_when_not_exists()
    {
        $expiredOtp = (new Otp)->validateOtp('xpto', Otp::TYPE_PASSWORD_RESET);
        $this->assertNull($expiredOtp);
    }
}
