<?php

namespace App\Http\Requests\Api\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string|\closure>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'otp' => function ($attribute, $value, $fail) {
                $user = User::query()->where('email', $this->email)
                    ->first();

                if (! $user) {
                    return $fail('The selected ' . $attribute . ' is invalid.');
                }

                $exists = $user->otps()
                    ->where('code', $value)
                    ->where('type', 'password_reset')
                    ->where('expires_at', '>', now())
                    ->exists();

                return $exists ?: $fail('The selected ' . $attribute . ' is invalid or has expired.');
            },
            'password' => 'required|string|confirmed|min:6',
        ];
    }
}
