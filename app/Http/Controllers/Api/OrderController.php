<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\BuildsApiListResponse;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use BuildsApiListResponse;

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $status = $this->apiQueryParam($request, 'status');
        $search = $this->apiQueryParam($request, 'search');
        $perPage = min(max((int) ($this->apiQueryParam($request, 'perPage', 'per_page') ?? 10), 1), 50);

        $query = Order::where('user_id', $user->id)
            ->with(['items.product'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()
            ->map(fn (Order $order) => $this->formatOrderSummary($order))
            ->values()
            ->all();

        $response = $this->apiListResponse($paginator, $items);
        $data = $response->getData(true);
        $data['stats'] = $this->orderStats($user->id);

        return response()->json($data);
    }

    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with([
                'items.product',
                'returnRequests' => fn ($q) => $q->orderByDesc('created_at'),
            ])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'order' => $this->formatOrderDetail($order),
        ]);
    }

    private function orderStats(int $userId): array
    {
        return [
            'total' => Order::where('user_id', $userId)->count(),
            'pending' => Order::where('user_id', $userId)->where('status', 'pending')->count(),
            'processing' => Order::where('user_id', $userId)->where('status', 'processing')->count(),
            'completed' => Order::where('user_id', $userId)->where('status', 'completed')->count(),
            'cancelled' => Order::where('user_id', $userId)->where('status', 'cancelled')->count(),
        ];
    }

    private function formatOrderSummary(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'status' => $order->status,
            'paymentStatus' => $order->payment_status,
            'paymentMethod' => $order->payment_method,
            'totalAmount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'USD',
            'itemsCount' => $order->items->count(),
            'createdAt' => $order->created_at?->toIso8601String(),
            'paidAt' => $order->paid_at?->toIso8601String(),
            'trackingNumber' => $order->tracking_number,
        ];
    }

    private function formatOrderDetail(Order $order): array
    {
        return [
            'id' => $order->id,
            'orderNumber' => $order->order_number,
            'status' => $order->status,
            'paymentStatus' => $order->payment_status,
            'paymentMethod' => $order->payment_method,
            'customerName' => $order->customer_name,
            'customerEmail' => $order->customer_email,
            'customerPhone' => $order->customer_phone,
            'shippingAddress' => $order->shipping_address,
            'city' => $order->city,
            'state' => $order->state,
            'postalCode' => $order->postal_code,
            'country' => $order->country,
            'subtotal' => (float) $order->subtotal,
            'taxAmount' => (float) $order->tax_amount,
            'discountAmount' => (float) $order->discount_amount,
            'giftCardAmount' => (float) $order->gift_card_amount,
            'shippingCost' => (float) $order->shipping_cost,
            'tipAmount' => (float) $order->tip_amount,
            'totalAmount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'USD',
            'promoCode' => $order->promo_code,
            'giftCardCode' => $order->gift_card_code,
            'trackingNumber' => $order->tracking_number,
            'notes' => $order->notes,
            'createdAt' => $order->created_at?->toIso8601String(),
            'paidAt' => $order->paid_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'id' => $item->id,
                'productId' => $item->product_id,
                'productName' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'unitPrice' => (float) $item->unit_price,
                'totalPrice' => (float) $item->total_price,
                'productOptions' => $item->product_options ?? [],
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'name' => $item->product->name,
                    'slug' => $item->product->slug,
                    'url' => '/products/'.$item->product->slug,
                ] : null,
            ])->values()->all(),
            'returnRequests' => $order->returnRequests->map(fn ($rr) => [
                'id' => $rr->id,
                'status' => $rr->status,
                'createdAt' => $rr->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }
}
