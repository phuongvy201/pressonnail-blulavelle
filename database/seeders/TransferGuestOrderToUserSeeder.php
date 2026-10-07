<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransferGuestOrderToUserSeeder extends Seeder
{
    /**
     * Chuyển order của guest customer sang tài khoản khách hàng đã đăng nhập.
     *
     * Cách sử dụng:
     *   1. Đặt order_number muốn chuyển bên dưới.
     *   2. Đặt user_id (id của tài khoản khách hàng) muốn gán order vào.
     *   3. Chạy: php artisan db:seed --class=TransferGuestOrderToUserSeeder
     *
     * Khi update_customer_info = true, seeder sẽ cập nhật thông tin khách hàng
     * trong order (name, email, phone, address) thành thông tin từ tài khoản user.
     */
    public function run(): void
    {
        // ─── Cấu hình ────────────────────────────────────────────────────────────
        $orderNumber = 'ORD-20261007-0001';   // Mã order cần chuyển (đổi theo order thực tế)
        $userId      = 1;                       // ID tài khoản khách hàng nhận order
        $updateCustomerInfo = false;            // true = cập nhật thông tin khách hàng từ user profile
        // ────────────────────────────────────────────────────────────────────────

        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) {
            $this->command->warn("Không tìm thấy order: {$orderNumber}");
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            $this->command->warn("Không tìm thấy user với id: {$userId}");
            return;
        }

        // Chuyển user_id
        $oldUserId = $order->user_id;
        $order->user_id = $user->id;
        $order->save();

        // Cập nhật thông tin khách hàng trong order từ profile user
        if ($updateCustomerInfo) {
            $order->update([
                'customer_name'  => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => $user->phone,
            ]);
        }

        $this->command->info("✅ Đã chuyển order [{$orderNumber}] từ user_id={$oldUserId} sang user_id={$user->id} ({$user->email})");
    }
}
