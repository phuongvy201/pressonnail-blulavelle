<?php

namespace App\Services\Orders;

use App\Mail\NewOrderAdminNotification;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderPaidMailer
{
    public const ADMIN_EMAIL = 'admin@blulavelle.com';

    public function send(Order $order): void
    {
        $email = trim((string) $order->customer_email);
        if ($email === '') {
            return;
        }

        $key = 'order_confirmation_sent:'.$order->id;
        if (! Cache::add($key, true, now()->addDays(30))) {
            return;
        }

        try {
            $order->loadMissing('items.product');
            Mail::to($email)->send(new OrderConfirmation($order));
        } catch (\Throwable $exception) {
            Cache::forget($key);
            Log::error('Failed to send order confirmation email', [
                'order_number' => $order->order_number,
                'email' => $email,
                'sales_channel' => $order->sales_channel,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        try {
            Mail::to(self::ADMIN_EMAIL)->send(new NewOrderAdminNotification($order));
        } catch (\Throwable $exception) {
            Log::error('Failed to send admin new-order email', [
                'order_number' => $order->order_number,
                'email' => self::ADMIN_EMAIL,
                'sales_channel' => $order->sales_channel,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
