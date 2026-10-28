<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Attempt to log in. Failed attempts are limited per email + IP (5/min, against password guessing)
     * and per IP alone (20/min, against trying one password on many emails).
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        foreach ([$this->throttleKey() => 5, $this->ipThrottleKey() => 20] as $key => $maxAttempts) {
            if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
                event(new Lockout($this));

                throw ValidationException::withMessages([
                    'email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
                ]);
            }
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey());
            // Repeated failures for one email or IP are a sign of password guessing.
            app(ActivityLogger::class)->log('auth.failed', 'Login gagal untuk email '.$this->string('email')->limit(100));

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function ipThrottleKey(): string
    {
        return 'login-ip|'.$this->ip();
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
