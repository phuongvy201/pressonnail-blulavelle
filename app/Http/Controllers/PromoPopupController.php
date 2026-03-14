<?php

namespace App\Http\Controllers;

use App\Models\PromoCode;
use App\Services\PromoCodeSendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PromoPopupController extends Controller
{
    /**
     * GET: Trả về thông tin offer cho popup (headline, subline) theo trigger.
     * Dùng sau Add to Cart / Wishlist để hiển thị "Get 10% OFF! Enter your email..."
     */
    public function offer(Request $request): JsonResponse
    {
        $trigger = $request->query('trigger');
        if (!in_array($trigger, [PromoCodeSendService::TRIGGER_ADD_TO_CART, PromoCodeSendService::TRIGGER_WISHLIST], true)) {
            return response()->json(['available' => false]);
        }

        $promo = PromoCode::where('send_on_trigger', $trigger)
            ->where('is_active', true)
            ->first();

        if (!$promo || !$promo->isValid()) {
            return response()->json(['available' => false]);
        }

        $headline = $this->buildHeadline($promo);
        $description = $this->buildDescription($promo);

        return response()->json([
            'available' => true,
            'headline' => $headline,
            'subline' => 'Enter your email to receive your discount code.',
            'description' => $description,
        ]);
    }

    /**
     * POST: Nhận email, gửi mã promo qua email cho trigger (add_to_cart / wishlist).
     */
    public function claim(Request $request): JsonResponse
    {
        $valid = $request->validate([
            'email' => 'required|email',
            'trigger' => 'required|in:add_to_cart,wishlist',
        ]);
        $email = $valid['email'];
        $trigger = $valid['trigger'];

        $userId = Auth::id();
        $service = app(PromoCodeSendService::class);
        $sent = $service->sendForTrigger($email, $trigger, $userId, true);

        if ($sent) {
            return response()->json(['success' => true, 'message' => 'Check your inbox for your discount code!']);
        }

        return response()->json([
            'success' => false,
            'message' => 'We couldn\'t send the code right now. You may have already received one recently—check your inbox.',
        ], 422);
    }

    private function buildHeadline(PromoCode $promo): string
    {
        if ($promo->type === 'percentage') {
            $val = (int) $promo->value;
            return "Get {$val}% OFF!";
        }
        $val = number_format((float) $promo->value, 0);
        return "Get \${$val} OFF!";
    }

    private function buildDescription(PromoCode $promo): ?string
    {
        if ($promo->type === 'percentage') {
            return (int) $promo->value . '% off your next order.';
        }
        if ($promo->type === 'fixed') {
            return '$' . number_format((float) $promo->value, 0) . ' off your next order.';
        }
        return null;
    }
}
