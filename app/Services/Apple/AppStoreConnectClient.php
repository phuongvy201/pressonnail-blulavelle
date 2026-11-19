<?php

namespace App\Services\Apple;

use App\Support\Apple\AppStoreConnectToken;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AppStoreConnectClient
{
    private const BASE = 'https://api.appstoreconnect.apple.com';

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(string $path, array $query = []): array
    {
        $response = Http::withToken(AppStoreConnectToken::make())
            ->acceptJson()
            ->timeout(45)
            ->get($this->url($path), $query);

        return $this->json($response);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $path, array $body): array
    {
        $response = Http::withToken(AppStoreConnectToken::make())
            ->acceptJson()
            ->timeout(45)
            ->post($this->url($path), $body);

        return $this->json($response);
    }

    public function download(string $url): string
    {
        $response = Http::timeout(90)
            ->withHeaders(['Accept' => '*/*'])
            ->get($url);
        if ($response->failed()) {
            throw new RuntimeException('App Store report download failed ('.$response->status().').');
        }

        return $response->body();
    }

    private function url(string $path): string
    {
        if (str_starts_with($path, 'https://')) {
            return $path;
        }

        return self::BASE.'/'.ltrim($path, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function json(Response $response): array
    {
        if ($response->failed()) {
            $detail = (string) ($response->json('errors.0.detail') ?: $response->body());
            $detail = trim(preg_replace('/\s+/', ' ', $detail) ?? $detail);

            throw new RuntimeException('App Store Connect '.$response->status().': '.mb_substr($detail, 0, 300));
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }
}
