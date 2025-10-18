<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Otp extends Model
{
    const TYPE_LOGIN = 'login';

    const TYPE_PASSWORD_RESET = 'password_reset';

    const EXPIRES_IN_MINUTES = 10;

    public $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used_at',
        'type',
    ];

    public $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createOtpByUser(int $userId, string $type, ?int $expiresIn = Otp::EXPIRES_IN_MINUTES): Otp
    {
        return self::create([
            'user_id' => $userId,
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes($expiresIn),
            'type' => $type,
        ]);
    }

    public function validateOtp(string $code, string $type): Otp|null
    {
        return Otp::query()
            ->where('code', $code)
            ->where('type', $type)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();
    }
}
