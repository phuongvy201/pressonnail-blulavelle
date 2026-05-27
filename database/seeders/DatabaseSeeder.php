<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Chạy seeders
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            ProductTemplateSeeder::class,
            ProductVariantSeeder::class,

            // CMS Seeders
            PostCategorySeeder::class,
            PostTagSeeder::class,
            PageSeeder::class,
            AffiliatePolicyPageSeeder::class,
            PostSeeder::class,
        ]);
    }
}
