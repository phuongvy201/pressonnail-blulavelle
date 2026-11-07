<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Seeder;

class UpdatePrivacyPolicySeeder extends Seeder
{
    /**
     * Cập nhật (hoặc tạo) trang privacy-policy mà không truncate toàn bộ pages.
     *
     * Chạy: php artisan db:seed --class=UpdatePrivacyPolicySeeder
     */
    public function run(): void
    {
        $admin = User::role('admin')->first() ?? User::query()->first();
        if (! $admin) {
            $this->command?->error('No users found. Create a user first.');

            return;
        }

        $pageSeeder = new PageSeeder;
        $definition = $pageSeeder->normalizePagePresentation(
            $pageSeeder->privacyPolicyPage((int) $admin->id)
        );

        $page = Page::query()->updateOrCreate(
            ['slug' => 'privacy-policy'],
            array_merge($definition, [
                'user_id' => $admin->id,
                'status' => 'published',
                'published_at' => now(),
            ]),
        );

        $this->command?->info('Privacy Policy updated (id='.$page->id.', slug=privacy-policy, last updated November 7, 2026).');
    }
}
