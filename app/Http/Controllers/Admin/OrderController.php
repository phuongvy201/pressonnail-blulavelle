<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderTrackingNotification;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    public function __construct()
    {
        // Only admin can update and destroy orders
        $this->middleware('role:admin')->only(['update', 'destroy']);

        // Admin and ad-partner can access index, show, and export
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            $roleNames = [];
            if ($user && method_exists($user, 'roles')) {
                $roleNames = $user->roles->pluck('name')->toArray();
            }

            if (
                !$user ||
                (!in_array('admin', $roleNames, true) && !in_array('ad-partner', $roleNames, true))
            ) {
                abort(403, 'Unauthorized');
            }
            return $next($request);
        })->only(['index', 'show', 'export']);
    }

    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Date range filter
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        // Statistics
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'today_orders' => Order::whereDate('created_at', today())->count(),
        ];

        return view('admin.orders.index', compact('orders', 'stats'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product.shop']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'tracking_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000'
        ]);

        $previousTracking = trim((string) $order->tracking_number);

        $order->update([
            'status' => $request->status,
            'payment_status' => $request->payment_status,
            'tracking_number' => $request->tracking_number,
            'notes' => $request->notes
        ]);

        // Nếu có tracking_number và đơn PayPal đã có capture_id, đẩy tracking sang PayPal
        $paypalTrackingSynced = null; // null = không gọi API, true/false = kết quả
        if ($request->filled('tracking_number') && $order->payment_method === 'paypal' && $order->paypal_capture_id) {
            $paypalTrackingSynced = false;
            try {
                $paypalOrderId = $order->payment_id; // lưu order_id trong payment_id
                $captureId = $order->paypal_capture_id;
                $trackingNumber = $request->tracking_number;
                $carrier = 'OTHER'; // carrier mặc định, có thể thay đổi theo dữ liệu thực tế

                $paypalService = new \App\Services\PayPalService();
                $paypalService->addTracking($paypalOrderId, $captureId, $trackingNumber, $carrier, false);
                $paypalTrackingSynced = true;
            } catch (\Exception $e) {
                Log::error('❌ Failed to push tracking to PayPal', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'paypal_order_id' => $order->payment_id,
                    'paypal_capture_id' => $order->paypal_capture_id,
                    'error' => $e->getMessage()
                ]);
                // Không throw để không chặn cập nhật trong admin
            }
        }

        $newTracking = trim((string) $request->input('tracking_number', ''));
        $trackingJustSet = $newTracking !== ''
            && $newTracking !== $previousTracking;

        if ($trackingJustSet && filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            try {
                $order->refresh();
                $order->load(['items.product.shop']);
                Mail::to($order->customer_email)->send(
                    new OrderTrackingNotification($order, $newTracking, $order->status)
                );
            } catch (\Exception $e) {
                Log::error('Failed to send tracking notification email (admin)', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'to' => $order->customer_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Stripe: bỏ xử lý push tracking (không dùng API tracking)

        $successParts = ['Order updated successfully!'];
        if ($paypalTrackingSynced === true) {
            $successParts[] = 'Tracking đã được ghi nhận trên PayPal (HTTP 2xx — chi tiết trong log: PayPal Add Tracking OK).';
        }

        $redirect = redirect()->back()->with('success', implode(' ', $successParts));
        if ($paypalTrackingSynced === false) {
            $redirect->with(
                'error',
                'Đơn đã lưu nhưng PayPal không nhận tracking. Kiểm tra storage/logs (PayPal Add Tracking Failed) hoặc PayPal Developer Dashboard.'
            );
        }

        return $redirect;
    }

    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order) {
            // Delete order items first
            $order->items()->delete();
            // Delete order
            $order->delete();
        });

        return redirect()->route('admin.orders.index')
            ->with('success', 'Order deleted successfully!');
    }

    public function export(Request $request)
    {
        $query = Order::with(['user', 'items.product.shop']);

        // Apply same filters as index
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Add payment status filter
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        // Generate CSV filename with timestamp
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = 'orders_export_' . $timestamp . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];

        $callback = function () use ($orders) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8 compatibility with Excel
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // CSV headers
            fputcsv($file, [
                'Order Number',
                'Customer Name',
                'Customer Email',
                'Customer Phone',
                'Status',
                'Payment Status',
                'Total Amount (USD)',
                'Tax Amount (USD)',
                'Shipping Amount (USD)',
                'Order Date',
                'Updated Date',
                'Total Items',
                'Product Details',
                'Shop Name',
                'Notes'
            ]);

            // CSV data
            foreach ($orders as $order) {
                // Prepare product details
                $productDetails = [];
                $totalItems = 0;
                $shopNames = [];

                foreach ($order->items as $item) {
                    $totalItems += $item->quantity;
                    $productName = $item->product->name ?? 'Product not found';
                    $productDetails[] = "{$item->quantity}x {$productName} @ $" . number_format($item->price, 2);

                    if ($item->product && $item->product->shop) {
                        $shopNames[] = $item->product->shop->name;
                    }
                }

                $productDetailsString = implode('; ', $productDetails);
                $shopNamesString = implode(', ', array_unique($shopNames));

                fputcsv($file, [
                    $order->order_number ?? '',
                    $order->customer_name ?? '',
                    $order->customer_email ?? '',
                    $order->customer_phone ?? '',
                    ucfirst($order->status ?? ''),
                    ucfirst($order->payment_status ?? ''),
                    number_format($order->total_amount ?? 0, 2),
                    number_format($order->tax_amount ?? 0, 2),
                    number_format($order->shipping_amount ?? 0, 2),
                    $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '',
                    $order->updated_at ? $order->updated_at->format('Y-m-d H:i:s') : '',
                    $totalItems,
                    $productDetailsString,
                    $shopNamesString,
                    $order->notes ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
