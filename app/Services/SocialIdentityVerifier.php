<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class SocialIdentityVerifier
{
    /**
     * @return array{email: string, name: string, providerId: string, avatar: string|null, idColumn: string}
     */
    public function profile(string $provider, string $idToken = '', string $accessToken = '', string $nonce = '', string $name = ''): array
    {
        return match ($provider) {
            'google' => $this->googleProfile($idToken),
            'facebook' => $this->facebookProfile($accessToken),
            'apple' => $this->appleProfile($idToken, $nonce, $name),
            default => throw new \InvalidArgumentException('This sign-in method is not available.'),
        };
    }

    /**
     * @param  array{email: string, name: string, providerId: string, avatar: string|null, idColumn: string}  $profile
     */
    public function findOrCreate(array $profile): User
    {
        $user = User::query()
            ->where('email', $profile['email'])
            ->orWhere($profile['idColumn'], $profile['providerId'])
            ->first();

        if ($user) {
            if (! $user->{$profile['idColumn']}) {
                $user->{$profile['idColumn']} = $profile['providerId'];
            }
            if ($profile['avatar'] && ! $user->avatar) {
                $user->avatar = $profile['avatar'];
            }
            if ($user->email_verified_at === null) {
                $user->email_verified_at = now();
            }
            $user->save();

            return $user;
        }

        $user = User::create([
            'name' => $profile['name'],
            'email' => $profile['email'],
            $profile['idColumn'] => $profile['providerId'],
            'avatar' => $profile['avatar'],
            'email_verified_at' => now(),
            'password' => Hash::make(uniqid('', true)),
        ]);

        if (! $user->hasAnyRole(['admin', 'seller', 'ad-partner'])) {
            $user->assignRole('customer');
        }

        return $user;
    }

    /**
     * @return array{email: string, name: string, providerId: string, avatar: string|null, idColumn: string}
     */
    private function googleProfile(string $idToken): array
    {
        if ($idToken === '') {
            throw new \InvalidArgumentException('Google did not return a sign-in token.');
        }

        $response = Http::timeout(8)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);
        $payload = $response->json();
        if (! $response->ok() || ! is_array($payload)) {
            throw new \InvalidArgumentException('Google could not confirm this sign-in.');
        }

        $audience = (string) ($payload['aud'] ?? '');
        $allowed = array_filter([
            (string) config('services.google.client_id'),
            ...array_map('trim', explode(',', (string) config('services.google.mobile_client_ids', ''))),
        ]);
        if ($audience === '' || ! in_array($audience, $allowed, true)) {
            throw new \InvalidArgumentException('This Google account is not allowed for BluLavelle.');
        }

        $email = strtolower((string) ($payload['email'] ?? ''));
        $verified = $payload['email_verified'] ?? false;
        if ($email === '' || ! in_array($verified, [true, 'true', 1, '1'], true)) {
            throw new \InvalidArgumentException('Google did not share a verified email address.');
        }

        $name = trim((string) ($payload['name'] ?? ''));

        return [
            'email' => $email,
            'name' => $name !== '' ? $name : $email,
            'providerId' => (string) $payload['sub'],
            'avatar' => isset($payload['picture']) ? (string) $payload['picture'] : null,
            'idColumn' => 'google_id',
        ];
    }

    /**
     * @return array{email: string, name: string, providerId: string, avatar: string|null, idColumn: string}
     */
    private function facebookProfile(string $accessToken): array
    {
        if ($accessToken === '') {
            throw new \InvalidArgumentException('Facebook did not return a sign-in token.');
        }

        $appId = (string) config('services.facebook.client_id');
        $appSecret = (string) config('services.facebook.client_secret');
        if ($appId === '' || $appSecret === '') {
            throw new \InvalidArgumentException('Facebook sign-in is not configured.');
        }

        $debug = Http::timeout(8)->get('https://graph.facebook.com/debug_token', [
            'input_token' => $accessToken,
            'access_token' => $appId.'|'.$appSecret,
        ]);
        $tokenData = $debug->json('data');
        if (! $debug->ok() || ! is_array($tokenData) || empty($tokenData['is_valid']) || (string) ($tokenData['app_id'] ?? '') !== $appId) {
            throw new \InvalidArgumentException('Facebook could not confirm this sign-in.');
        }

        $profile = Http::timeout(8)->get('https://graph.facebook.com/me', [
            'fields' => 'id,name,email,picture.type(large)',
            'access_token' => $accessToken,
        ]);
        $body = $profile->json();
        if (! $profile->ok() || ! is_array($body) || empty($body['id'])) {
            throw new \InvalidArgumentException('Facebook could not share this profile.');
        }

        $email = strtolower((string) ($body['email'] ?? ''));
        if ($email === '') {
            throw new \InvalidArgumentException('Facebook did not share an email address. Sign in with email instead.');
        }

        $name = trim((string) ($body['name'] ?? ''));
        $avatar = $body['picture']['data']['url'] ?? null;

        return [
            'email' => $email,
            'name' => $name !== '' ? $name : $email,
            'providerId' => (string) $body['id'],
            'avatar' => is_string($avatar) ? $avatar : null,
            'idColumn' => 'facebook_id',
        ];
    }

    /**
     * @return array{email: string, name: string, providerId: string, avatar: string|null, idColumn: string}
     */
    private function appleProfile(string $idToken, string $nonce, string $name): array
    {
        $claims = $this->appleClaims($idToken, $nonce);
        $providerId = (string) $claims['sub'];
        $existing = User::query()->where('apple_id', $providerId)->first();
        $email = strtolower((string) ($claims['email'] ?? ''));
        if ($email === '' && $existing) {
            $email = strtolower((string) $existing->email);
        }
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Apple did not share an email address. Remove this app from Sign in with Apple in your Apple ID settings, then try again.');
        }
        if ($email !== '' && array_key_exists('email_verified', $claims) && ! in_array($claims['email_verified'], [true, 'true', 1, '1'], true)) {
            throw new \InvalidArgumentException('Apple did not share a verified email address.');
        }

        $display = trim($name);
        if ($existing && trim((string) $existing->name) !== '') {
            $display = (string) $existing->name;
        } elseif ($display === '') {
            $display = $email;
        }

        return [
            'email' => $email,
            'name' => $display,
            'providerId' => $providerId,
            'avatar' => null,
            'idColumn' => 'apple_id',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appleClaims(string $idToken, string $nonce): array
    {
        $parts = explode('.', $idToken);
        if (count($parts) !== 3 || $nonce === '') {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $claims = json_decode($this->base64UrlDecode($parts[1]), true);
        if (! is_array($header) || ! is_array($claims) || ($header['alg'] ?? '') !== 'ES256') {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        $signature = $this->base64UrlDecode($parts[2]);
        $verified = openssl_verify(
            $parts[0].'.'.$parts[1],
            $this->ecdsaSignatureToDer($signature),
            $this->applePublicKey((string) ($header['kid'] ?? '')),
            OPENSSL_ALGO_SHA256,
        );
        if ($verified !== 1) {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        if (($claims['iss'] ?? '') !== 'https://appleid.apple.com') {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        $audience = $claims['aud'] ?? '';
        $audiences = is_array($audience) ? $audience : [$audience];
        $allowed = false;
        foreach ($audiences as $value) {
            if (in_array((string) $value, $this->appleAudiences(), true)) {
                $allowed = true;
                break;
            }
        }
        if (! $allowed) {
            throw new \InvalidArgumentException('This Apple account is not allowed for BluLavelle.');
        }

        if ((int) ($claims['exp'] ?? 0) < time()) {
            throw new \InvalidArgumentException('Apple sign-in expired. Try again.');
        }

        $tokenNonce = (string) ($claims['nonce'] ?? '');
        if (! hash_equals(hash('sha256', $nonce), $tokenNonce)) {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        if ((string) ($claims['sub'] ?? '') === '') {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        return $claims;
    }

    /**
     * @return list<string>
     */
    private function appleAudiences(): array
    {
        $audiences = array_filter(array_map(
            'trim',
            explode(',', (string) config('services.apple.client_ids', '')),
        ));
        $primary = trim((string) config('services.apple.client_id', ''));
        if ($primary !== '') {
            $audiences[] = $primary;
        }

        return array_values(array_unique($audiences));
    }

    private function applePublicKey(string $kid, bool $refresh = false): string
    {
        if ($kid === '') {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }
        if ($refresh) {
            Cache::forget('apple-sign-in-jwks');
        }

        $keys = Cache::remember('apple-sign-in-jwks', now()->addHour(), function () {
            $response = Http::timeout(8)->acceptJson()->get('https://appleid.apple.com/auth/keys');
            $keys = $response->json('keys');
            if (! $response->ok() || ! is_array($keys)) {
                throw new \RuntimeException('Apple sign-in keys are unavailable.');
            }

            return $keys;
        });

        foreach ($keys as $jwk) {
            if (is_array($jwk) && (string) ($jwk['kid'] ?? '') === $kid) {
                return $this->p256PublicPem($jwk);
            }
        }

        if (! $refresh) {
            return $this->applePublicKey($kid, true);
        }

        throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
    }

    /**
     * @param  array<string, mixed>  $jwk
     */
    private function p256PublicPem(array $jwk): string
    {
        if (($jwk['kty'] ?? '') !== 'EC' || ($jwk['crv'] ?? '') !== 'P-256') {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        $point = "\x04".$this->p256Coordinate((string) ($jwk['x'] ?? '')).$this->p256Coordinate((string) ($jwk['y'] ?? ''));
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$point;
        if ($der === false) {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        return "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";
    }

    private function p256Coordinate(string $value): string
    {
        $bytes = $this->base64UrlDecode($value);

        return str_pad(substr($bytes, -32), 32, "\x00", STR_PAD_LEFT);
    }

    private function ecdsaSignatureToDer(string $signature): string
    {
        if (strlen($signature) !== 64) {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        $integer = function (string $bytes): string {
            $bytes = ltrim($bytes, "\x00");
            if ($bytes === '') {
                $bytes = "\x00";
            }
            if ((ord($bytes[0]) & 0x80) !== 0) {
                $bytes = "\x00".$bytes;
            }

            return "\x02".chr(strlen($bytes)).$bytes;
        };

        $body = $integer(substr($signature, 0, 32)).$integer(substr($signature, 32));

        return "\x30".chr(strlen($body)).$body;
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \InvalidArgumentException('Apple could not confirm this sign-in.');
        }

        return $decoded;
    }
}
