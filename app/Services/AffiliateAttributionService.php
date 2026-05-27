<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\PromoCode;
use App\Support\AffiliateUtmCapture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AffiliateAttributionService
{
    /**
     * @return array{affiliate_id: int|null, affiliate_attribution: string, utm_snapshot: array<string, string>}
     */
    public function buildOrderAttributes(Request $request, ?PromoCode $appliedPromo): array
    {
        $utmSnapshot = $this->utmSnapshotForOrder($request);

        if ($appliedPromo && $appliedPromo->affiliate_id) {
            $affiliate = Affiliate::query()
                ->where('id', $appliedPromo->affiliate_id)
                ->where('is_active', true)
                ->first();
            if ($affiliate && !$this->isSelfReferral($affiliate)) {
                return [
                    'affiliate_id' => $affiliate->id,
                    'affiliate_attribution' => 'coupon',
                    'utm_snapshot' => $utmSnapshot,
                ];
            }
        }

        $cookieName = (string) config('affiliate.cookie_name', 'pon_affiliate_ref');
        $refFromCookie = $request->cookie($cookieName);
        $code = is_string($refFromCookie) ? trim($refFromCookie) : '';
        if ($code !== '') {
            $affiliate = $this->findActiveAffiliateByCode($code);
            if ($affiliate && !$this->isSelfReferral($affiliate)) {
                return [
                    'affiliate_id' => $affiliate->id,
                    'affiliate_attribution' => 'cookie',
                    'utm_snapshot' => $utmSnapshot,
                ];
            }
        }

        return [
            'affiliate_id' => null,
            'affiliate_attribution' => 'none',
            'utm_snapshot' => $utmSnapshot,
        ];
    }

    public function findActiveAffiliateByCode(string $code): ?Affiliate
    {
        $normalized = Str::lower(trim($code));

        return Affiliate::query()
            ->whereRaw('LOWER(code) = ?', [$normalized])
            ->where('is_active', true)
            ->first();
    }

    /**
     * @return array<string, string>
     */
    public function utmSnapshotForOrder(Request $request): array
    {
        $sessionKey = (string) config('affiliate.utm_session_key', 'affiliate_utm_last');
        $fromSession = $request->session()->get($sessionKey, []);
        if (!is_array($fromSession)) {
            $fromSession = [];
        }

        $fromRequest = AffiliateUtmCapture::fromRequest($request);

        $explicitFromQuery = [];
        foreach (config('affiliate.utm_params', []) as $param) {
            if (! is_string($param) || ! $request->query->has($param)) {
                continue;
            }
            $v = $request->query($param);
            if ($v !== null && $v !== '') {
                $explicitFromQuery[$param] = (string) $v;
            }
        }

        $merged = AffiliateUtmCapture::merge($fromSession, $fromRequest);
        $merged = array_merge($merged, $explicitFromQuery);

        return $merged;
    }

    private function isSelfReferral(Affiliate $affiliate): bool
    {
        $uid = Auth::id();
        if (!$uid || !$affiliate->user_id) {
            return false;
        }

        return (int) $uid === (int) $affiliate->user_id;
    }
}
