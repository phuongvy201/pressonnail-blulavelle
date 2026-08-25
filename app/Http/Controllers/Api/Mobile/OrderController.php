<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\Concerns\RespondsWithMobileJson;
use App\Http\Controllers\Api\OrderController as StorefrontOrderController;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    use RespondsWithMobileJson;

    public function index(Request $request, StorefrontOrderController $controller): JsonResponse
    {
        return $controller->index($request);
    }

    public function show(Request $request, string $orderNumber, StorefrontOrderController $controller): JsonResponse
    {
        return $controller->show($request, $orderNumber);
    }

    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'orderNumber' => ['required_without:order_number', 'string', 'max:64'],
            'order_number' => ['required_without:orderNumber', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $orderNumber = $validated['orderNumber'] ?? $validated['order_number'];

        $order = Order::where('order_number', $orderNumber)
            ->where('customer_email', $validated['email'])
            ->with(['items.product'])
            ->first();

        if (! $order) {
            return $this->mobileError('Order not found. Please check your order number and email.', 404);
        }

        return $this->mobileSuccess([
            'order' => $this->formatTrackedOrder($order),
        ]);
    }

    public function cancel(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (! in_array($order->status, ['pending', 'processing'], true)) {
            return $this->mobileError('This order cannot be cancelled.', 422);
        }

        $order->update([
            'status' => 'cancelled',
            'notes' => ($order->notes ? $order->notes."\n" : '').
                'Order cancelled by customer on '.now()->format('Y-m-d H:i:s'),
        ]);

        return $this->mobileSuccess([
            'orderNumber' => $order->order_number,
            'status' => $order->status,
        ], 'Order has been cancelled successfully.');
    }

    public function returnRequest(Request $request, string $orderNumber): JsonResponse
    {
        $user = $request->user();

        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'resolution' => ['required', 'string', 'in:refund,exchange,store_credit'],
            'description' => ['nullable', 'string', 'max:2000'],
            'evidence' => ['nullable', 'array'],
            'evidence.*' => ['image', 'max:5120'],
            'confirm' => ['accepted'],
        ]);

        $evidencePaths = [];
        if ($request->hasFile('evidence')) {
            foreach ($request->file('evidence') as $file) {
                $evidencePaths[] = $file->store('returns', 'public');
            }
        }

        ReturnRequest::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'],
            'resolution' => $validated['resolution'],
            'description' => $validated['description'] ?? null,
            'evidence_paths' => $evidencePaths,
            'status' => 'pending',
        ]);

        return $this->mobileSuccess([
            'orderNumber' => $order->order_number,
        ], 'Return/Exchange request submitted successfully.');
    }

    private function formatTrackedOrder(Order $order): array
    {
        return [
            'orderNumber' => $order->order_number,
            'status' => $order->status,
            'paymentStatus' => $order->payment_status,
            'totalAmount' => (float) $order->total_amount,
            'currency' => $order->currency ?? 'USD',
            'trackingNumber' => $order->tracking_number,
            'createdAt' => $order->created_at?->toIso8601String(),
            'items' => $order->items->map(fn ($item) => [
                'productName' => $item->product_name,
                'quantity' => (int) $item->quantity,
                'totalPrice' => (float) $item->total_price,
            ])->values()->all(),
        ];
    }
}
