<?php

use App\Support\Seo;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pages')) {
            return;
        }

        foreach (Seo::catalogDescriptions() as $slug => $description) {
            $page = DB::table('pages')->where('slug', $slug)->first();
            if ($page === null) {
                continue;
            }

            $current = trim(strip_tags((string) ($page->meta_description ?? '')));
            if (mb_strlen($current) >= 120) {
                continue;
            }

            DB::table('pages')->where('id', $page->id)->update([
                'meta_description' => $description,
            ]);
        }
    }

    public function down(): void
    {
    }
};
