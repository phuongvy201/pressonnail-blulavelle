<?php

namespace App\Support\Apple;

use Illuminate\Support\Facades\Cache;
use RuntimeException;

class AppStoreConnectToken
{
    public static function configured(): bool
    {
        return self::issuerId() !== ''
            && self::keyId() !== ''
            && self::privateKeyPem() !== ''
            && self::appId() !== '';
    }

    public static function appId(): string
    {
        return trim((string) config('apple_connect.app_id'));
    }

    public static function make(): string
    {
        $pem = self::privateKeyPem();
        $keyId = self::keyId();
        $issuerId = self::issuerId();
        if ($pem === '' || $keyId === '' || $issuerId === '') {
            throw new RuntimeException('App Store Connect API is not configured.');
        }

        return Cache::remember('apple.connect.jwt.'.hash('sha256', $keyId.'|'.$issuerId), now()->addMinutes(15), function () use ($pem, $keyId, $issuerId): string {
            $header = self::base64Url(json_encode([
                'alg' => 'ES256',
                'kid' => $keyId,
                'typ' => 'JWT',
            ], JSON_THROW_ON_ERROR));
            $now = time();
            $payload = self::base64Url(json_encode([
                'iss' => $issuerId,
                'iat' => $now,
                'exp' => $now + (19 * 60),
                'aud' => 'appstoreconnect-v1',
            ], JSON_THROW_ON_ERROR));
            $signingInput = $header.'.'.$payload;
            $raw = self::sign($signingInput, $pem);

            return $signingInput.'.'.self::base64Url($raw);
        });
    }

    public static function privateKeyPem(): string
    {
        $value = trim((string) config('apple_connect.private_key'));
        if ($value === '') {
            return '';
        }
        if (str_contains($value, 'BEGIN')) {
            return str_replace('\n', "\n", $value);
        }
        if (is_file($value)) {
            return trim((string) file_get_contents($value));
        }

        return '';
    }

    private static function issuerId(): string
    {
        return trim((string) config('apple_connect.issuer_id'));
    }

    private static function keyId(): string
    {
        return trim((string) config('apple_connect.key_id'));
    }

    private static function sign(string $input, string $pem): string
    {
        $key = openssl_pkey_get_private($pem);
        if ($key === false) {
            throw new RuntimeException('App Store Connect private key could not be read.');
        }
        $der = '';
        if (! openssl_sign($input, $der, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('App Store Connect token could not be signed.');
        }

        return self::derToRaw($der);
    }

    public static function derToRaw(string $der): string
    {
        $offset = 0;
        if (ord($der[$offset]) !== 0x30) {
            throw new RuntimeException('App Store Connect signature was not an ECDSA signature.');
        }
        $offset++;
        $offset = self::skipLength($der, $offset);
        $r = self::readInteger($der, $offset);
        $s = self::readInteger($der, $offset);

        return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT)
            .str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }

    private static function skipLength(string $der, int $offset): int
    {
        $length = ord($der[$offset]);
        $offset++;
        if (($length & 0x80) !== 0) {
            $offset += ($length & 0x7F);
        }

        return $offset;
    }

    private static function readInteger(string $der, int &$offset): string
    {
        if (ord($der[$offset]) !== 0x02) {
            throw new RuntimeException('App Store Connect signature was not an ECDSA signature.');
        }
        $offset++;
        $length = ord($der[$offset]);
        $offset++;
        $value = substr($der, $offset, $length);
        $offset += $length;

        return $value;
    }

    public static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
