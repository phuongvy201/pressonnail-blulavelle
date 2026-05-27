<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\AffiliateClickEvent;
use App\Models\Product;
use App\Support\AffiliateUtmCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AffiliateClickTrackingService
{
    public function record(Affiliate $affiliate, Request $request, string $refCode): void
    {
        try {
            $path = '/'.ltrim($request->path(), '/');
            if (strlen($path) > 512) {
                $path = substr($path, 0, 512);
            }

            $productId = $this->resolveProductIdFromPath($path);

            $utm = AffiliateUtmCapture::fromRequest($request);

            $referrer = $request->headers->get('referer');
            $referrerHost = null;
            if (is_string($referrer) && $referrer !== '') {
                $host = parse_url($referrer, PHP_URL_HOST);
                $referrerHost = is_string($host) ? substr($host, 0, 255) : null;
            }

            AffiliateClickEvent::query()->create([
                'affiliate_id' => $affiliate->id,
                'ref_code' => strtolower($refCode),
                'landing_path' => $path,
                'product_id' => $productId,
                'utm_source' => $utm['utm_source'] ?? null,
                'utm_medium' => $utm['utm_medium'] ?? null,
                'utm_campaign' => $utm['utm_campaign'] ?? null,
                'utm_content' => $utm['utm_content'] ?? null,
                'referrer_host' => $referrerHost,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('affiliate.click_track_failed', [
                'affiliate_id' => $affiliate->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function resolveProductIdFromPath(string $path): ?int
    {
        if (! preg_match('#^/products/([^/]+)#', $path, $m)) {
            return null;
        }

        $slug = urldecode($m[1]);
        $id = Product::query()->where('slug', $slug)->value('id');

        return $id ? (int) $id : null;
    }
}
