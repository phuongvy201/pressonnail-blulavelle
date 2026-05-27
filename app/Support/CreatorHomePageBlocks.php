<?php

namespace App\Support;

/**
 * Defaults + schema inline-edit cho các section creator home (ngoài hero/faq).
 */
final class CreatorHomePageBlocks
{
    /** @var list<string> */
    public const TIER_SLUGS = ['basic', 'silver', 'gold', 'diamond'];
    public static function defaults(string $key): array
    {
        return match ($key) {
            'creator.home.elevate' => [
                'bg_color' => '#eff4ff',
                'heading' => 'Elevate Your Content',
                'subheading' => 'We provide the tools and incentives to transform your influence into a sustainable creator business.',
                'b1_icon' => 'percent', 'b1_title' => '15% Commission', 'b1_desc' => 'Industry-leading rates on every sale.',
                'b2_icon' => 'featured_seasonal_and_gifts', 'b2_title' => 'Free Samples', 'b2_desc' => 'Monthly curation of hero products.',
                'b3_icon' => 'lock_open', 'b3_title' => 'Early Access', 'b3_desc' => 'Launch content before the public.',
                'b4_icon' => 'insights', 'b4_title' => 'Analytics Dash', 'b4_desc' => 'Real-time performance tracking.',
                'b5_icon' => 'stars', 'b5_title' => 'Monthly Rewards', 'b5_desc' => 'Bonuses for top performers.',
            ],
            'creator.home.steps' => [
                'bg_color' => '',
                'step_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDYVoYqfoh4plqnyKuWtejzwOu2EqqcWvXvdFofsqjYlGBrvbOgEogkr2r6WLMIL9KuIbv9vw_LEVzs-zcNOkwmxjTeFf1PKJZb0YI7xZdzOjhKjWAg63bWTYjliDQxY54nkZiBpukJb0EQ6zyOt4S7AblW3PrT4c7IjXn5_q4TcuKcE4VW5M3vkGFqqjszZI6U_3ApP8dSc5I-Z-cKkW4Lrih-FCpV8HmHhAJS9tcCujEjVkUK5IYnJY-rcoc8QKL_SNqv9vZkHH16',
                'step1_title' => 'Apply Online', 'step1_body' => 'Submit your handles and portfolio. We look for creators with authentic voices and a love for luxury beauty.',
                'step2_title' => 'Get Approved', 'step2_body' => 'Our team reviews applications within 48 hours. Once approved, you gain access to your personalized portal.',
                'step3_title' => 'Share Content', 'step3_body' => 'Create stunning tutorials or reviews using our products. Share your unique affiliate links with your audience.',
                'step4_title' => 'Earn Commission', 'step4_body' => 'Receive automated monthly payouts based on sales driven by your links. Track every dollar in real-time.',
            ],
            'creator.home.tiers' => self::homeTiersDefaults(),
            'creator.home.sample' => [
                'bg_color' => '',
                'heading' => 'Request Your', 'heading_italic' => 'First Sample',
                'subheading' => 'Choose from our bestselling "Luminous Glow" collection. Receive full-sized products to test and feature in your next tutorial.',
                'product_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAJDPF28vt0RFmKqyWm1bO4LcomRaweVSIRAsmnOR020DFyqJKiPBWqYdbAstw6d9UqKo3XBbCkQtxAiFWtcRSlzQwJt-BQfW4dVTT__uuIolHt4JpA9Ltx_EmcsHU8aW2GaaVVt26jBbE8Ya8NLotL6978KOcPAzhFt4nmdGwUG7Eh7kwQeBu12rFoh9UcHYR6_thv52yuv49BrthDbO4DGU4OFGg3lb04pkZXWD61bbcBc75YIt-RKH-hEDekOobN_x5ZG7oCpBrk',
                'product_name' => 'Radiance Nectar Serum', 'product_value' => 'Value: $85.00', 'product_badge' => 'IN STOCK',
                'btn_label' => 'Request Free Sample', 'footnote' => 'Requires video proof within 7 days of arrival',
                'reel1_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBCysg7BdTU1Jm_-NrrGqgeRS_OTIP47HBfSIOYRyqwl0IRv3HtyGKXAyUlkWnnKM3ST1pBtV8WNGXnRaOBlaE2d3-ZBMXTrAnPYR5lI2LZbti8eUP29VYs-fpQZOYkszqUGhHERziDCK9x0ldqnnpTW6CXa0EuU85x0PNwCJC8-Dg23gWaqutCjaYXZVUi7yY-t_flbat9k-PScDK8F-iB8_LQfnaAnfEsITPb1Vgaq6E52LILlJaXcks5pi2VrYWrpgs_mo1UILFh',
                'reel1_handle' => '@sophia_beauty', 'reel1_likes' => '12.4k',
                'reel2_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDad01vN0z_njop0h_0i0LCEMzhS9sX58siFRXYQwNu2Zn4qSyG1Lz2slxU4VesIML_pDHDGD-tbyCFqRvRldutRKT9i7DlAy3pFSZRqNWH4OgPKdH-NXOPFGUS545BfXnxFxHecrtutFgGxffr-7TX1q9w0xAi_KbcI_9edZgk1BYAx3nZ2BxZy5yBCe9XOEiTgMI9dL1xy79jJQ5wSpeDWT2Ffw8h6jJtsoL9GPnHVRdGXlgU4keZFMFT6Eopjms2jb_-QDVjaNhI',
                'reel2_handle' => '@glow_diaries', 'reel2_likes' => '48.2k',
            ],
            'creator.home.spotlight' => [
                'bg_color' => '#eff4ff',
                'heading' => 'Creator Spotlight',
                'subheading' => 'Meet the faces of '.config('app.name').'.',
                'c1_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD1et1WLjpBw8oHCZTAgM4KNAH3qtq1VEBSyLmXZJcO_wQYfHmmpjJRZWAnuM49UlVLDjqm9HygLWAKz7oegMj5dGtFhfU13jeISA_bQByyQx4mAl6zlFj8bVWxSjhreZnvDERh1k8dWjQDLm3SeT1_vPTskXI0RxOjD3BOZ1KrospOUMM8KIkjzxFa-MOtoBAuBEpVxQeFg_shCSezvpKb34qg5je7tX-yXSr9R2odnTRfkYiiG2kNapJnVxBZW8eES773YH_0VIsm',
                'c1_handle' => '@elena_vogue', 'c1_left' => '$14k+ Earned', 'c1_right' => 'Engagement: 5.2%',
                'c2_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB4W2rCWP6zXPJzQh6wBkxUroRnXqicDV7XUpOuaNh6q6OIWdSa80oIhH-DsnCZ0dnRy1WMApj40mnWxy2Wa7QtKj-PDNZtzH4W_L17vjqPjeahofU--xC_J8nYI9ldoKVsx76EnO2KmqvKLrFH4KJcuapb-CLErL9Yl6k_ELDnCC7UQTdHGw8vyB1vXmFkfJju82vTP9mZ1TEU68rE8xFN1qYgQqQfk2H4mxq5b4ISQETxBnB_0KTJcPEpJtTNZHHn6Y5EVkZQ6jQ5',
                'c2_handle' => '@chic_minimal', 'c2_left' => '$9k+ Earned', 'c2_right' => 'Engagement: 4.8%',
                'c3_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDVJL-l_Ng_iUdmP-2PxEO7-AoN35Qqrk0z3KQ5yHH5i1eLeaQ_WHRSlkhAmy8cq-DaodKjgBl-rEktCt5dYmyy6WByaQRPgp9Bqels1cUKVlBbJDauyHCDZBBWVRNSBFOTj6RzNA0JiwyGRRgHbq_hxvl9AN5jwoCRrsYinHwgeU_hEH6H5hLUTKIXl9q1oGNhHJ0MZ9bX7qNX86q73DiC9MGSXKyiiJxreMLmH4XBotKLU2wqmLSbWXt43DlxC5zjdcIlBcFK-7mu',
                'c3_handle' => '@beauty_by_nia', 'c3_left' => '$22k+ Earned', 'c3_right' => 'Engagement: 7.1%',
                'c4_image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBNEMLub7HARLVL4mCotKj0aVuCOojF_Ggq0-MILHZJoidwZbv308TZBGW19irNc8xQ_sbEKsTFVvnIMX6NZxxe3SeNVwexact4YNM1WLHJoipn-rns1Ogvj58kE1KuEinr9Xb9xi9ll-Yc51EMWkMVSRUzTJACYphU11982JL4zz3XYngBVZq7CnzxGowO-eeANL5xeBXMZr8rTN4e5wRzmQdVcG-XT_KrCZX2Tq3KjlNB8y5kN6yAc2BgyB4vkNsCK6I-vh_5Vj4G',
                'c4_handle' => '@marcus_glow', 'c4_left' => '$11k+ Earned', 'c4_right' => 'Engagement: 3.9%',
            ],
            'creator.home.dashboard' => [
                'bg_color' => '',
                'heading' => 'Your Command Center',
                'subheading' => 'The tools you need to succeed, all in one place.',
                'revenue_value' => '$14,502.20', 'revenue_change' => '+12.5% from last month',
                'orders_value' => '482', 'orders_note' => '12 pending verification',
                'payout_value' => 'Next: Nov 15', 'payout_note' => 'Automatic Deposit Active',
                'link_heading' => 'Your Unique Affiliate Link',
                'link_example' => '', 'link_footnote' => 'Shared 24 times this week',
            ],
            'creator.home.cta' => [
                'bg_color' => '',
                'heading' => 'Ready to curate with', 'heading_brand' => config('app.name'),
                'subheading' => 'Application takes less than 5 minutes. Start your journey today.',
                'btn_label' => 'Apply for the Program',
            ],
            default => [],
        };
    }

    public static function schema(string $key): array
    {
        $bg = fn () => content_block_section_bg_schema();
        $text = fn (string $k, string $l) => ['key' => $k, 'label' => $l, 'type' => 'text'];
        $area = fn (string $k, string $l) => ['key' => $k, 'label' => $l, 'type' => 'textarea'];
        $img = fn (string $k, string $l) => ['key' => $k, 'label' => $l, 'type' => 'image'];

        return match ($key) {
            'creator.home.elevate' => array_merge([$bg()], [
                $text('heading', 'Tiêu đề section'),
                $area('subheading', 'Mô tả'),
                $text('b1_icon', 'Card 1 — icon (Material, vd: percent)'), $text('b1_title', 'Card 1 — tiêu đề'), $text('b1_desc', 'Card 1 — mô tả'),
                $text('b2_icon', 'Card 2 — icon'), $text('b2_title', 'Card 2 — tiêu đề'), $text('b2_desc', 'Card 2 — mô tả'),
                $text('b3_icon', 'Card 3 — icon'), $text('b3_title', 'Card 3 — tiêu đề'), $text('b3_desc', 'Card 3 — mô tả'),
                $text('b4_icon', 'Card 4 — icon'), $text('b4_title', 'Card 4 — tiêu đề'), $text('b4_desc', 'Card 4 — mô tả'),
                $text('b5_icon', 'Card 5 — icon'), $text('b5_title', 'Card 5 — tiêu đề'), $text('b5_desc', 'Card 5 — mô tả'),
            ]),
            'creator.home.steps' => array_merge([$bg(), $img('step_image', 'Ảnh bên phải')], [
                $text('step1_title', 'Bước 1 — tiêu đề'), $area('step1_body', 'Bước 1 — mô tả'),
                $text('step2_title', 'Bước 2 — tiêu đề'), $area('step2_body', 'Bước 2 — mô tả'),
                $text('step3_title', 'Bước 3 — tiêu đề'), $area('step3_body', 'Bước 3 — mô tả'),
                $text('step4_title', 'Bước 4 — tiêu đề'), $area('step4_body', 'Bước 4 — mô tả'),
            ]),
            'creator.home.tiers' => array_merge([$bg()], [
                $text('heading', 'Tiêu đề'), $text('subheading', 'Mô tả'),
                ...self::homeTiersSchemaFields($text, $area),
            ]),
            'creator.home.sample' => array_merge([$bg()], [
                $text('heading', 'Tiêu đề (phần thường)'), $text('heading_italic', 'Tiêu đề (italic)'),
                $area('subheading', 'Mô tả'), $img('product_image', 'Ảnh sản phẩm'),
                $text('product_name', 'Tên sản phẩm'), $text('product_value', 'Giá trị'), $text('product_badge', 'Badge kho'),
                $text('btn_label', 'Nút CTA'), $text('footnote', 'Ghi chú dưới nút'),
                $img('reel1_image', 'Reel 1 — ảnh'), $text('reel1_handle', 'Reel 1 — handle'), $text('reel1_likes', 'Reel 1 — likes'),
                $img('reel2_image', 'Reel 2 — ảnh'), $text('reel2_handle', 'Reel 2 — handle'), $text('reel2_likes', 'Reel 2 — likes'),
            ]),
            'creator.home.spotlight' => array_merge([$bg()], [
                $text('heading', 'Tiêu đề'), $text('subheading', 'Mô tả'),
                $img('c1_image', 'Creator 1 — ảnh'), $text('c1_handle', 'Creator 1 — handle'), $text('c1_left', 'Creator 1 — trái'), $text('c1_right', 'Creator 1 — phải'),
                $img('c2_image', 'Creator 2 — ảnh'), $text('c2_handle', 'Creator 2 — handle'), $text('c2_left', 'Creator 2 — trái'), $text('c2_right', 'Creator 2 — phải'),
                $img('c3_image', 'Creator 3 — ảnh'), $text('c3_handle', 'Creator 3 — handle'), $text('c3_left', 'Creator 3 — trái'), $text('c3_right', 'Creator 3 — phải'),
                $img('c4_image', 'Creator 4 — ảnh'), $text('c4_handle', 'Creator 4 — handle'), $text('c4_left', 'Creator 4 — trái'), $text('c4_right', 'Creator 4 — phải'),
            ]),
            'creator.home.dashboard' => array_merge([$bg()], [
                $text('heading', 'Tiêu đề'), $text('subheading', 'Mô tả'),
                $text('revenue_value', 'Total Revenue — số'), $text('revenue_change', 'Total Revenue — %'),
                $text('orders_value', 'Active Orders — số'), $text('orders_note', 'Active Orders — ghi chú'),
                $text('payout_value', 'Payout — dòng chính'), $text('payout_note', 'Payout — ghi chú'),
                $text('link_heading', 'Affiliate link — tiêu đề'), $text('link_example', 'Affiliate link — ví dụ URL (để trống = host/join/your-code)'), $text('link_footnote', 'Affiliate link — ghi chú'),
            ]),
            'creator.home.cta' => array_merge([$bg()], [
                $text('heading', 'Tiêu đề (trước tên brand)'), $text('heading_brand', 'Tên brand trong tiêu đề (để trống = app name)'),
                $area('subheading', 'Mô tả'), $text('btn_label', 'Nút CTA'),
            ]),
            default => [],
        };
    }

    public static function homeTiersDefaults(): array
    {
        $rates = AffiliateSettings::tierRates();

        return [
            'bg_color' => '#e5eeff',
            'heading' => 'Commission Tiers',
            'subheading' => 'Scale your earnings as your influence grows.',
            'basic_label' => AffiliateTier::label(AffiliateTier::BASIC),
            'basic_rate' => self::formatRatePercent($rates['basic']),
            'basic_features' => "Creator Portal Access\nStandard Samples\nMonthly Payouts",
            'basic_cta' => '',
            'silver_label' => AffiliateTier::label(AffiliateTier::SILVER),
            'silver_rate' => self::formatRatePercent($rates['silver']),
            'silver_features' => "Everything in Basic\nPriority Support\nExtra Sample Quota",
            'silver_cta' => '',
            'gold_label' => AffiliateTier::label(AffiliateTier::GOLD),
            'gold_rate' => self::formatRatePercent($rates['gold']),
            'gold_features' => "Advanced Analytics\nPriority Product Selection\nBi-Weekly Payouts\nInvites to Local Events",
            'gold_cta' => 'Apply for Gold',
            'diamond_label' => AffiliateTier::label(AffiliateTier::DIAMOND),
            'diamond_rate' => self::formatRatePercent($rates['diamond']),
            'diamond_features' => "Dedicated Account Manager\nCustom Collection Collabs\nWeekly Payouts\nBrand Trip Eligibility",
            'diamond_cta' => 'Apply for Diamond',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeTiersContent(array $data): array
    {
        if (empty($data['diamond_rate']) && ! empty($data['platinum_rate'])) {
            $data['diamond_rate'] = $data['platinum_rate'];
            $data['diamond_features'] = $data['platinum_features'] ?? '';
            $data['diamond_cta'] = $data['platinum_cta'] ?? '';
            $data['diamond_label'] = $data['platinum_label'] ?? AffiliateTier::label(AffiliateTier::DIAMOND);
        }

        return $data;
    }

    /**
     * @param  callable(string, string): array{key: string, label: string, type: string}  $text
     * @param  callable(string, string): array{key: string, label: string, type: string}  $area
     * @return list<array{key: string, label: string, type: string}>
     */
    private static function homeTiersSchemaFields(callable $text, callable $area): array
    {
        $fields = [];
        foreach (self::TIER_SLUGS as $slug) {
            $label = AffiliateTier::label($slug);
            $fields[] = $text("{$slug}_label", "{$label} — tên hiển thị");
            $fields[] = $text("{$slug}_rate", "{$label} — %");
            $fields[] = $area("{$slug}_features", "{$label} — lợi ích (mỗi dòng 1 mục)");
            $fields[] = $text("{$slug}_cta", "{$label} — nút CTA (để trống = không hiện)");
        }

        return $fields;
    }

    private static function formatRatePercent(float $rate): string
    {
        $formatted = fmod($rate, 1.0) === 0.0 ? (string) (int) $rate : rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.');

        return $formatted.'%';
    }

    /** @return list<string> */
    public static function homeBlockKeys(): array
    {
        return [
            'creator.home.hero',
            'creator.home.elevate',
            'creator.home.steps',
            'creator.home.tiers',
            'creator.home.sample',
            'creator.home.spotlight',
            'creator.home.dashboard',
            'creator.home.faq',
            'creator.home.cta',
        ];
    }
}
