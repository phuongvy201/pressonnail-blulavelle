<?php

namespace App\Services;

use App\Models\Order;
use App\Support\SalesChannel;

class OrderChannelStats
{
    /**
     * Paid purchases only. A pending checkout is not counted as a customer purchase.
     *
     * @return array{website: array{orders: int, customers: int, revenue: float}, app: array{orders: int, customers: int, revenue: float}}
     */
    public static function summary(): array
    {
        $paid = Order::query()->where('payment_status', 'paid');
        $summary = [];

        foreach ([SalesChannel::WEBSITE, SalesChannel::APP] as $channel) {
            $query = (clone $paid)->where('sales_channel', $channel);
            $summary[$channel] = [
                'orders' => (clone $query)->count(),
                'customers' => (int) (clone $query)->distinct()->count('customer_email'),
                'revenue' => (float) (clone $query)->sum('total_amount'),
            ];
        }

        return $summary;
    }
}
