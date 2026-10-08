<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderReviewController extends Controller
{
    public function index(Request $request, string $orderNumber): JsonResponse
    {
        $order = $this->ownedOrder($request, $orderNumber);

        return ApiResponse::success($this->eligibility($order, (int) $request->user()->id));
    }

    public function store(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();
        $order = $this->ownedOrder($request, $orderNumber);

        $validated = $request->validate([
            'productId' => ['required_without:product_id', 'integer', 'min:1'],
            'product_id' => ['required_without:productId', 'integer', 'min:1'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'reviewText' => ['required_without:review_text', 'string', 'max:2000'],
            'review_text' => ['required_without:reviewText', 'string', 'max:2000'],
            'reviewImage' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
            'review_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        if ($order->status !== 'delivered') {
            return ApiResponse::error(
                'ORDER_NOT_DELIVERED',
                'You can review this order after it is delivered.',
                409
            );
        }

        $productId = (int) ($request->input('productId') ?? $request->input('product_id'));
        $item = $this->reviewableItem($order, $productId);
        if (! $item) {
            return ApiResponse::error(
                'PRODUCT_NOT_IN_ORDER',
                'That product is not a reviewable item on this delivered order.',
                422
            );
        }

        $existing = Review::query()
            ->where('product_id', $productId)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            return ApiResponse::error(
                'ALREADY_REVIEWED',
                'You have already submitted a review for this product.',
                409
            );
        }

        $imagePath = $this->storeImage($request->file('reviewImage') ?? $request->file('review_image'));
        $review = Review::query()->create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'customer_name' => (string) ($user->name ?? 'Customer'),
            'customer_email' => (string) ($user->email ?? ''),
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'review_text' => (string) ($request->input('reviewText') ?? $request->input('review_text')),
            'image_url' => $imagePath,
            'is_verified_purchase' => true,
            'is_approved' => true,
        ]);

        return ApiResponse::success([
            'review' => $this->reviewPayload($review, $order->order_number),
        ], [
            'message' => 'Thank you! Your review has been submitted successfully.',
        ], 201);
    }

    private function ownedOrder(Request $request, string $orderNumber): Order
    {
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['items.product:id,is_gift_card'])
            ->first();

        if (! $order) {
            throw new NotFoundHttpException('Order not found.');
        }

        return $order;
    }

    private function eligibility(Order $order, int $userId): array
    {
        $productIds = $order->items->pluck('product_id')->filter()->unique()->values();
        $reviews = Review::query()
            ->where('user_id', $userId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->keyBy('product_id');
        $delivered = $order->status === 'delivered';

        $items = $order->items
            ->filter(fn (OrderItem $item) => $item->product_id)
            ->unique('product_id')
            ->map(function (OrderItem $item) use ($delivered, $reviews, $order) {
                $existing = $reviews->get($item->product_id);
                $giftCard = (bool) ($item->product?->is_gift_card);
                $canReview = $delivered && ! $giftCard && ! $existing;
                $reason = null;
                if (! $delivered) {
                    $reason = 'not_delivered';
                } elseif ($giftCard) {
                    $reason = 'gift_card';
                } elseif ($existing) {
                    $reason = 'already_reviewed';
                }

                return [
                    'orderItemId' => $item->id,
                    'productId' => (int) $item->product_id,
                    'productName' => $item->product_name,
                    'canReview' => $canReview,
                    'reviewBlockReason' => $reason,
                    'review' => $existing ? $this->reviewPayload($existing, $order->order_number) : null,
                ];
            })
            ->values();

        return [
            'orderNumber' => $order->order_number,
            'status' => $order->status,
            'canReview' => $delivered && $items->contains(fn (array $item) => $item['canReview']),
            'items' => $items,
        ];
    }

    private function reviewableItem(Order $order, int $productId): ?OrderItem
    {
        return $order->items->first(function (OrderItem $item) use ($productId) {
            return (int) $item->product_id === $productId && ! ($item->product?->is_gift_card);
        });
    }

    private function reviewPayload(Review $review, string $orderNumber): array
    {
        return [
            'id' => $review->id,
            'orderNumber' => $orderNumber,
            'productId' => (int) $review->product_id,
            'rating' => (int) $review->rating,
            'title' => $review->title,
            'reviewText' => $review->review_text,
            'imageUrl' => $review->image_url_for_display,
            'isVerifiedPurchase' => (bool) $review->is_verified_purchase,
            'isApproved' => (bool) $review->is_approved,
            'createdAt' => ApiResponse::iso($review->created_at),
        ];
    }

    private function storeImage(mixed $upload): ?string
    {
        if (! $upload instanceof UploadedFile || ! $upload->isValid()) {
            return null;
        }

        $extension = strtolower((string) ($upload->getClientOriginalExtension() ?: $upload->extension()));
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');
        $storedPath = 'reviews/'.$filename;
        $stored = Storage::disk('public')->put($storedPath, fopen($upload->getPathname(), 'r'));
        if (! $stored) {
            throw new \RuntimeException('Unable to save the uploaded review image.');
        }

        return $storedPath;
    }
}
