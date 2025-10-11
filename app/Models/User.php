<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Exceptions\BaseException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use stdClass;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function otps(): HasMany
    {
        return $this->hasMany(Otp::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): HasManyThrough
    {
        return $this->hasManyThrough(Permission::class, Role::class);
    }

    public function resetPasswordWithOtp(string $email, string $otp, string $newPassword): void
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new BaseException('User not found', 404);
        }

        /** @var \App\Models\Otp|null $otp */
        $otp = $user->otps()
            ->where('code', $otp)
            ->where('type', Otp::TYPE_PASSWORD_RESET)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $otp) {
            throw new BaseException('Invalid or expired OTP', 403);
        }

        $user->password = Hash::make($newPassword);

        $user->save();

        $otp->used_at = now();

        $otp->save();

        $user->otps()->where('id', '!=', $otp->id)->where('type', '=', $otp->type)->delete();
    }

    public function generateResetPasswordOtp(string $email): object
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            throw new BaseException('User not found', 404);
        }

        $otp = $user->otps()->create([
            'code' => rand(100000, 999999),
            'expires_at' => now()->addMinutes(Otp::EXPIRES_IN_MINUTES),
            'type' => Otp::TYPE_PASSWORD_RESET,
        ]);

        $data = new stdClass;
        $data->user = $user;
        $data->otp = $otp;

        return $data;
    }
}
