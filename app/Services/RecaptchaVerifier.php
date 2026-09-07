<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class RecaptchaVerifier
{
    public function isEnabled(): bool
    {
        return filled($this->secretKey());
    }

    public function siteKey(): ?string
    {
        $key = config('services.recaptcha.site_key');

        return filled($key) ? (string) $key : null;
    }

    public function config(): array
    {
        return [
            'required' => $this->isEnabled(),
            'siteKey' => $this->siteKey(),
            'provider' => 'recaptcha',
        ];
    }

    /**
     * Verify reCAPTCHA when a secret key is configured. No-op otherwise.
     */
    public function verify(Request $request, string $errorKey = 'recaptchaToken'): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $token = $request->input('recaptchaToken', $request->input('g-recaptcha-response'));

        if (! filled($token)) {
            throw ValidationException::withMessages([
                $errorKey => [__('Please complete the security check.')],
            ]);
        }

        try {
            $response = Http::asForm()->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret' => $this->secretKey(),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]
            );

            $data = $response->json();

            if (! ($data['success'] ?? false)) {
                throw ValidationException::withMessages([
                    $errorKey => [__('Security verification failed, please try again.')],
                ]);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                $errorKey => [__('Unable to verify security, please try again.')],
            ]);
        }
    }

    private function secretKey(): ?string
    {
        $key = config('services.recaptcha.secret_key');

        return filled($key) ? (string) $key : null;
    }
}
