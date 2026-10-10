<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class SocialIdentityVerifier
{
    /**
     * @return array{email: string, name: string, providerId: string, avatar: string|null, idColumn: string}
     */
    public function profile(string $provider, string $idToken = '', string $accessToken = ''): array
    {
        return $provider === 'google'
            ? $this->googleProfile($idToken)
            : $this->facebookProfile($accessToken);
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
}
