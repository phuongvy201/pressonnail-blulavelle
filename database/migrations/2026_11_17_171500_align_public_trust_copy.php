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
                    [
                        'support@bluprinter.com',
                        'tel:0767383676',
                        'href="/policies/',
                        'Bluprinter is not responsible',
                        'At Bluprinter, customer satisfaction',
                        'Learn about Bluprinter shipping',
                        'There\'s no Blulavelle warehouse',
                        'There’s no Blulavelle warehouse',
                    ],
                    [
                        'support@blulavelle.com',
                        'tel:+12053899573',
                        'href="/',
                        'BluLavelle is not responsible',
                        'At BluLavelle, customer satisfaction',
                        'Learn about BluLavelle shipping',
                        'Orders ship from the BluLavelle warehouses',
                        'Orders ship from the BluLavelle warehouses',
                    ],
                    (string) $page->content
                );
                $description = str_replace(
                    ['Learn about Bluprinter', 'Bluprinter shipping'],
                    ['Learn about BluLavelle', 'BluLavelle shipping'],
                    (string) ($page->meta_description ?? '')
                );
                if ($content !== (string) $page->content || $description !== (string) ($page->meta_description ?? '')) {
                    DB::table('pages')->where('id', $page->id)->update([
                        'content' => $content,
                        'meta_description' => $description,
                    ]);
                }
            });
        }

        if (! Schema::hasTable('content_blocks')) {
            return;
        }

        foreach (['home.hero' => [
            'badge_number' => ['50k+', '50K+'],
            'badge_label' => ['Happy Customers'],
            'meta1_value' => ['50K+', '50k+'],
            'meta1_label' => ['Happy Customers'],
            'meta2_value' => ['4.9/5', '4.9'],
            'meta2_label' => ['Average Rating'],
            'slide2_badge_number' => ['4.9★', '4.9/5'],
            'slide2_badge_label' => ['Average Rating'],
        ], 'home.indulge' => [
            'review_text' => ['50k+ Reviews', '50K+ Reviews'],
        ]] as $key => $fields) {
            $block = DB::table('content_blocks')->where('block_key', $key)->first();
            if (! $block) {
                continue;
            }
            $content = json_decode((string) $block->content, true);
            if (! is_array($content)) {
                continue;
            }
            $replacements = $key === 'home.hero'
                ? [
                    'badge_number' => '30 days',
                    'badge_label' => 'Easy returns',
                    'meta1_value' => '30 days',
                    'meta1_label' => 'Easy returns',
                    'meta2_value' => '$150',
                    'meta2_label' => 'Free shipping over',
                    'slide2_badge_number' => '$150',
                    'slide2_badge_label' => 'Free shipping over',
                ]
                : ['review_text' => 'Customer reviews'];
            $changed = false;
            foreach ($fields as $field => $badValues) {
                if (! isset($content[$field]) || ! in_array($content[$field], $badValues, true)) {
                    continue;
                }
                $content[$field] = $replacements[$field];
                $changed = true;
            }
            if ($changed) {
                DB::table('content_blocks')->where('id', $block->id)->update([
                    'content' => json_encode($content),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Public trust copy was corrected. Do not restore the conflicting claims.
    }
};
