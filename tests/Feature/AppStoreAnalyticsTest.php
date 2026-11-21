<?php

use App\Models\AppleAppDownload;
use App\Models\User;
use App\Services\Apple\AppStoreAnalyticsSync;
use App\Services\Apple\AppStoreReportParser;
use App\Support\Apple\AppStoreConnectToken;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

function appleKeys(): array
{
    $private = <<<'PEM'
-----BEGIN PRIVATE KEY-----
MIGHAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBG0wawIBAQQgpk9VXCP0yqolDK8x
JEj3O3i6VuBNzuJ8zPv6fHRBAEOhRANCAAQyJ044dVO0yvc5lYF7Ue8oh3/kQRdb
am1/yprXgkHFId7N9yAToSsoNuwY+O7q92Wkg2435eFTitBo6j+ALcjd
-----END PRIVATE KEY-----
PEM;
    $key = openssl_pkey_get_private($private);
    expect($key)->not->toBeFalse();

    return [
        'private' => $private,
        'public' => openssl_pkey_get_details($key)['key'],
    ];
}

function appleAdmin(): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('app store connect token is a signed es256 jwt', function () {
    $keys = appleKeys();
    config([
        'apple_connect.issuer_id' => 'issuer-1',
        'apple_connect.key_id' => 'KEY123',
        'apple_connect.private_key' => $keys['private'],
        'apple_connect.app_id' => '6826066333',
    ]);

    $parts = explode('.', AppStoreConnectToken::make());
    $decode = function (string $value): string {
        $value = strtr($value, '-_', '+/');
        $pad = strlen($value) % 4;
        if ($pad !== 0) {
            $value .= str_repeat('=', 4 - $pad);
        }

        return base64_decode($value);
    };
    $header = json_decode($decode($parts[0]), true);
    $payload = json_decode($decode($parts[1]), true);
    $raw = $decode($parts[2]);
    $integer = function (string $bytes): string {
        $bytes = ltrim($bytes, "\x00");
        if ($bytes === '' || (ord($bytes[0]) & 0x80) !== 0) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".chr(strlen($bytes)).$bytes;
    };
    $body = $integer(substr($raw, 0, 32)).$integer(substr($raw, 32, 32));
    $der = "\x30".chr(strlen($body)).$body;

    expect($header['alg'])->toBe('ES256')
        ->and($header['kid'])->toBe('KEY123')
        ->and($payload['iss'])->toBe('issuer-1')
        ->and($payload['aud'])->toBe('appstoreconnect-v1')
        ->and(strlen($raw))->toBe(64)
        ->and(openssl_verify($parts[0].'.'.$parts[1], $der, $keys['public'], OPENSSL_ALGO_SHA256))->toBe(1);
});

test('download report rows keep the website campaign', function () {
    $tsv = implode("\n", [
        "Date\tDownload Type\tSource Type\tCampaign\tTerritory\tDevice\tCounts",
        "2026-11-15\tFirst-time download\tWeb Referrer\twebsite_header\tUS\tiPhone\t4",
        "2026-11-15\tFirst-time download\tWeb Referrer\twebsite_header\tUS\tiPhone\t1",
    ]);

    $rows = AppStoreReportParser::rows(gzencode($tsv));

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['campaign'])->toBe('website_header')
        ->and($rows[0]['counts'])->toBe(4);
});

test('sync stores download totals and skips an unchanged instance', function () {
    $keys = appleKeys();
    config([
        'apple_connect.issuer_id' => 'issuer-1',
        'apple_connect.key_id' => 'KEY123',
        'apple_connect.private_key' => $keys['private'],
        'apple_connect.app_id' => '6826066333',
    ]);
    $tsv = "Date\tDownload Type\tSource Type\tCampaign\tTerritory\tDevice\tCounts\n2026-11-15\tFirst-time download\tWeb Referrer\twebsite_header\tUS\tiPhone\t4\n";
    Http::fake(function ($request) use ($tsv) {
        $url = $request->url();
        if (str_contains($url, 'files.example')) {
            return Http::response(gzencode($tsv), 200);
        }
        if (str_contains($url, '/segments')) {
            return Http::response(['data' => [[
                'id' => 'seg-1',
                'attributes' => ['url' => 'https://files.example/report.gz', 'checksum' => 'abc'],
            ]]]);
        }
        if (str_contains($url, '/instances')) {
            return Http::response(['data' => [[
                'id' => 'inst-1',
                'attributes' => ['granularity' => 'DAILY', 'processingDate' => '2026-11-17'],
            ]]]);
        }
        if (str_contains($url, '/reports')) {
            return Http::response(['data' => [[
                'id' => 'report-1',
                'attributes' => ['name' => 'App Downloads Detailed', 'category' => 'COMMERCE'],
            ]]]);
        }

        return Http::response(['data' => [[
            'id' => 'req-1',
            'attributes' => ['accessType' => 'ONGOING'],
        ]]]);
    });

    $first = app(AppStoreAnalyticsSync::class)->sync(1);
    $second = app(AppStoreAnalyticsSync::class)->sync(1);

    expect($first['instances'])->toBe(1)
        ->and($first['rows'])->toBe(1)
        ->and($second['instances'])->toBe(0)
        ->and(AppleAppDownload::query()->where('campaign', 'website_header')->value('counts'))->toBe(4);
});

test('admin can see stored app store downloads', function () {
    AppleAppDownload::query()->create([
        'stat_date' => now()->subDay()->toDateString(),
        'download_type' => 'First-time download',
        'source_type' => 'Web Referrer',
        'campaign' => 'website_footer',
        'territory' => 'US',
        'device' => 'iPhone',
        'counts' => 6,
    ]);

    $this->actingAs(appleAdmin())
        ->get(route('admin.app-store.index'))
        ->assertOk()
        ->assertSee('website_footer')
        ->assertSee('App Store downloads');
});
