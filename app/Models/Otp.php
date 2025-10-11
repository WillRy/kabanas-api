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
}
