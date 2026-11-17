<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pages')) {
            DB::table('pages')->orderBy('id')->lazy()->each(function (object $page): void {
                $content = str_replace(
                    ['16342015', '1301 S ARAPAHO', '1301 S Arapaho'],
                    ['16342615', '1301 E ARAPAHO', '1301 E Arapaho'],
                    (string) $page->content
                );
                if ($content !== (string) $page->content) {
                    DB::table('pages')->where('id', $page->id)->update(['content' => $content]);
                }
            });
        }

        if (! Schema::hasTable('content_blocks')) {
            return;
        }

        $block = DB::table('content_blocks')->where('block_key', 'layout.footer_faq')->first();
        if (! $block) {
            return;
        }

        $content = json_decode((string) $block->content, true);
        if (! is_array($content) || ! isset($content['a4']) || ! is_string($content['a4'])) {
            return;
        }
        if (! str_contains($content['a4'], '7 days') && ! str_contains($content['a4'], 'do not accept returns')) {
            return;
        }

        $content['a4'] = 'You can request a return or exchange within 30 days of delivery. If an item arrives damaged, incorrect, or does not fit, contact support@blulavelle.com and we will help. There is no restocking fee.';
        DB::table('content_blocks')->where('id', $block->id)->update([
            'content' => json_encode($content),
        ]);
    }

    public function down(): void
    {
        // Public facts were corrected. Do not restore the conflicting company number or the 7-day return line.
    }
};
