<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\User;
use App\Support\AffiliateSettings;
use App\Support\AffiliateTier;
use Illuminate\Database\Seeder;

class AffiliatePolicyPageSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('admin')->first() ?? User::query()->first();

        if (! $admin) {
            $this->command?->error('No users found. Create a user before seeding affiliate policies.');

            return;
        }

        $brand = config('app.name');
        $storePrivacyUrl = rtrim(config('creator.shop_url', config('app.url')), '/').'/privacy-policy';
        $refParam = config('affiliate.ref_query_param', 'ref');
        $rates = AffiliateSettings::tierRates();
        $cookieDays = AffiliateSettings::attributionWindowDays();
        $rollingDays = AffiliateSettings::tierEvaluationDays();
        $inactivityDays = AffiliateSettings::tierInactivityDays();
        $thresholds = AffiliateSettings::tierOrderThresholds();
        $newCustomersOnly = AffiliateSettings::commissionNewCustomersOnly();
        $sampleQuotas = config('affiliate.sample_quotas', []);
        $samplePeriodDays = (int) data_get($sampleQuotas, 'basic.period_days', 30);
        $payoutMethods = implode(', ', array_values(config('creator.payout_methods', [])));
        $payoutDelayDays = AffiliateSettings::payoutDelayDaysAfterDelivery();

        $sampleQuotaLines = '';
        foreach (AffiliateTier::ALL as $tier) {
            $max = (int) data_get($sampleQuotas, "{$tier}.max_requests", 0);
            $label = AffiliateTier::label($tier);
            $sampleQuotaLines .= "<li><strong>{$label}:</strong> up to {$max} approved sample request(s) per rolling {$samplePeriodDays}-day period</li>\n";
        }

        $acquisitionHtml = $newCustomersOnly
            ? <<<HTML
<p class="mb-4 text-[#404753] leading-relaxed"><strong>New-customer acquisition (active).</strong> Commission generally applies only when the customer&apos;s <em>first paid order</em> on our store is attributed to an affiliate (any active affiliate). Additional paid orders from the same customer may qualify for commission only if they occur within <strong>{$cookieDays} days</strong> of that first paid order. If the customer&apos;s first paid order was not through an affiliate (organic, ads, or direct), later orders—even via your link—do not earn commission. Guest checkouts without an email on the first order may not qualify.</p>
HTML
            : '<p class="mb-4 text-[#404753] leading-relaxed">New-customer-only restrictions are currently disabled; standard attribution rules below apply to qualifying orders.</p>';

        $pages = [
            [
                'title' => 'Affiliate Program Terms',
                'slug' => 'affiliate-program-terms',
                'menu_title' => 'Program Terms',
                'sort_order' => 101,
                'excerpt' => 'Terms governing participation in the '.$brand.' Creator & Affiliate Program.',
                'meta_title' => 'Affiliate Program Terms — '.$brand,
                'meta_description' => 'Rules and obligations for creators and affiliates in the '.$brand.' program.',
                'content' => $this->wrap(
                    'Affiliate Program Terms',
                    'Agreement between you and '.$brand.' for the Creator & Affiliate Program.',
                    <<<HTML
<p class="mb-4 text-[#404753] leading-relaxed">By applying to or participating in the {$brand} Creator &amp; Affiliate Program (&quot;Program&quot;), you agree to these Program Terms, our <a href="/policies/affiliate-commission-payout-policy" class="font-semibold text-primary underline">Commission &amp; Payout Policy</a>, <a href="/policies/affiliate-attribution-cookie-policy" class="font-semibold text-primary underline">Attribution &amp; Cookie Policy</a>, and <a href="/policies/affiliate-privacy-policy" class="font-semibold text-primary underline">Affiliate Privacy Policy</a>. If you do not agree, do not apply or promote our products as an affiliate.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">How to join</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>Submit an application on the creator portal with accurate profile, platform, audience, and proposed referral code.</li>
<li>Create or link a user account and verify your email before final submission.</li>
<li>Applications are reviewed manually; approval is at our sole discretion. Approved partners receive a unique <code class="rounded bg-[#eff4ff] px-1.5 py-0.5 text-sm">ref</code> code and access to the creator dashboard.</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Eligibility</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>You must be at least 18 years old (or the age of majority in your jurisdiction).</li>
<li>You represent that your social channels and audience data are accurate.</li>
<li>One affiliate account per person unless we authorize otherwise in writing.</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Your obligations</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>Disclose affiliate relationships clearly and comply with FTC and local advertising rules (e.g. #ad, paid partnership).</li>
<li>Do not use misleading claims, fake reviews, cookie stuffing, or self-referral abuse.</li>
<li>Do not bid on our brand trademarks in paid search without written approval.</li>
<li>Content must align with our brand standards; we may request edits or remove non-compliant material.</li>
<li>Complete payout setup with accurate details; you are responsible for taxes on commissions you earn.</li>
<li>Commission payouts are issued approximately <strong>{$payoutDelayDays} days</strong> after successful delivery of each qualifying order (see Commission &amp; Payout Policy).</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Samples</h2>
<p class="mb-4 text-[#404753] leading-relaxed">Approved affiliates may request product samples subject to tier quotas, product availability, and admin approval. Sample orders are not commissionable. Misuse of samples (failure to post required content, resale, etc.) may result in suspension.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Our rights</h2>
<p class="mb-4 text-[#404753] leading-relaxed">We may change commission tiers, program rules, or these terms with notice where required. We may suspend or terminate your account for violations, fraud, chargebacks, or prolonged inactivity. Unpaid balances may be withheld while we investigate suspected abuse. Refunds and payment disputes on attributed orders may reduce or reverse commission per our <a href="/policies/affiliate-commission-payout-policy" class="font-semibold text-primary underline">Commission &amp; Payout Policy</a> (including deductions from future payouts if commission was already paid).</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Relationship</h2>
<p class="text-[#404753] leading-relaxed">Affiliates are independent contractors, not employees or partners of {$brand}. Nothing here grants trademark rights beyond approved marketing use.</p>
HTML
                ),
            ],
            [
                'title' => 'Affiliate Privacy Policy',
                'slug' => 'affiliate-privacy-policy',
                'menu_title' => 'Affiliate Privacy',
                'sort_order' => 102,
                'excerpt' => 'How we collect and use information from affiliate applicants and partners.',
                'meta_title' => 'Affiliate Privacy Policy — '.$brand,
                'meta_description' => 'Privacy practices for the '.$brand.' creator and affiliate portal.',
                'content' => $this->wrap(
                    'Affiliate Privacy Policy',
                    'Information we collect when you apply to or participate in our affiliate program.',
                    <<<HTML
<p class="mb-4 text-[#404753] leading-relaxed">This policy describes how {$brand} handles personal information submitted through the creator portal and affiliate tools. Our general store <a href="{$storePrivacyUrl}" class="font-semibold text-primary underline">Privacy Policy</a> also applies where relevant.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Information we collect</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li><strong>Application data:</strong> name, email, phone, platform, follower range, niche, social links, proposed ref code, and agreement to program terms.</li>
<li><strong>Account &amp; payout data:</strong> profile details and payout method information (PayPal or US bank transfer—bank numbers stored encrypted).</li>
<li><strong>Performance data:</strong> referral clicks, attributed orders, commission status (pending/paid), tier, sample requests, and dashboard analytics.</li>
<li><strong>Technical data:</strong> IP address, browser, referrer, UTM parameters, and affiliate attribution cookies (see Attribution &amp; Cookie Policy).</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">How we use it</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>Review applications, operate your affiliate account, attribute sales, and calculate commissions.</li>
<li>Process sample requests and communicate about approvals, payouts, and program updates.</li>
<li>Prevent fraud, enforce program rules, and improve the program.</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Sharing</h2>
<p class="mb-4 text-[#404753] leading-relaxed">We may share data with payment processors, email providers, and analytics tools under contract. We do not sell your personal information. We may disclose information if required by law or to protect our rights.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Your choices</h2>
<p class="text-[#404753] leading-relaxed">Contact us to update application details or ask questions about your data. If you leave the Program, we may retain records as needed for tax, accounting, and dispute resolution.</p>
HTML
                ),
            ],
            [
                'title' => 'Commission & Payout Policy',
                'slug' => 'affiliate-commission-payout-policy',
                'menu_title' => 'Commission & Payouts',
                'sort_order' => 103,
                'excerpt' => 'Commission rates, tiers, qualifying orders, samples, and payout rules for affiliates.',
                'meta_title' => 'Affiliate Commission & Payout Policy — '.$brand,
                'meta_description' => 'Commission tiers, calculation, and payout rules for '.$brand.' affiliates.',
                'content' => $this->wrap(
                    'Commission & Payout Policy',
                    'How affiliate commissions are earned, calculated, and paid.',
                    <<<HTML
<h2 class="creator-font-headline mb-3 text-2xl font-semibold text-[#0b1c30]">Commission tiers</h2>
<p class="mb-4 text-[#404753] leading-relaxed">Unless your profile has a custom <em>commission rate override</em>, your rate follows your program tier. Current default rates (admin may update these in program settings):</p>
<ul class="mb-6 list-disc space-y-2 pl-6 text-[#404753]">
<li><strong>Basic:</strong> {$rates['basic']}% — default tier for new affiliates</li>
<li><strong>Silver:</strong> {$rates['silver']}%</li>
<li><strong>Gold:</strong> {$rates['gold']}%</li>
<li><strong>Diamond:</strong> {$rates['diamond']}%</li>
</ul>
<p class="mb-4 text-[#404753] leading-relaxed"><strong>Tier upgrades</strong> are based on the number of <em>attributed paid orders</em> in a rolling <strong>{$rollingDays}-day</strong> window: Silver at {$thresholds['silver']}+ orders, Gold at {$thresholds['gold']}+, Diamond at {$thresholds['diamond']}+. Tiers are re-evaluated periodically (including via automated monthly recalculation).</p>
<p class="mb-4 text-[#404753] leading-relaxed"><strong>Tier downgrades:</strong> if you have no attributed paid orders for <strong>{$inactivityDays} days</strong>, your tier may drop by one level (unless your tier is locked by admin).</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">When commission is created</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>Commission is created when an attributed order is marked <strong>paid</strong>, in <strong>pending</strong> status until we process payout.</li>
<li>Orders must be attributed to your ref link/cookie or an admin-assigned promo code linked to your account (see Attribution Policy).</li>
<li><strong>Self-purchases</strong> (orders on your own customer account) do not earn commission.</li>
<li>Orders with an open/lost payment dispute, inactive affiliate account, or ineligible products are excluded.</li>
</ul>
{$acquisitionHtml}
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Commission calculation</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>Only products marked <strong>affiliate-eligible</strong> in our catalog count toward commission (gift cards and excluded items do not).</li>
<li>Commission base is derived from the order <strong>subtotal minus discounts</strong>, allocated to eligible line items—<strong>shipping, tax, and tips are not included</strong>.</li>
<li>Amount = eligible base × your commission rate at the time the commission is created (rate is stored on each commission record).</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Refunds, disputes, and chargebacks</h2>
<p class="mb-4 text-[#404753] leading-relaxed">If a customer <strong>requests a refund</strong>, receives a <strong>full or partial refund</strong>, opens a <strong>payment dispute</strong> (chargeback), or we otherwise reverse all or part of an attributed order, commission on the affected product(s) or order is handled as follows:</p>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li><strong>Not yet paid:</strong> commission on the refunded or disputed amount is <strong>not paid</strong>—it is reduced, voided, or held while the case is open. You will not receive commission on revenue we did not keep.</li>
<li><strong>Already paid:</strong> if we already sent you commission on that order or line item, we will <strong>deduct the overpaid amount</strong> from your affiliate balance. That deduction applies to <strong>future payouts</strong> (including commission from later orders) until the balance is corrected. If your balance is insufficient, we may withhold further payouts until the amount is recovered or we agree otherwise in writing.</li>
<li><strong>Partial refunds:</strong> commission is adjusted proportionally to the net eligible amount we retain after the refund.</li>
<li><strong>Open disputes:</strong> while a dispute or chargeback is unresolved, related commission may stay pending or be reversed; a lost dispute is treated like a refund for commission purposes.</li>
</ul>
<p class="mb-4 text-[#404753] leading-relaxed">These adjustments apply regardless of payout timing (including the post-delivery hold period). Our records of order status, refunds, and disputes are authoritative for commission and balance adjustments.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Product samples</h2>
<p class="mb-4 text-[#404753] leading-relaxed">Sample requests are subject to admin approval, stock, per-product rules, and tier quotas over a rolling <strong>{$samplePeriodDays}-day</strong> window (rejected requests do not count):</p>
<ul class="mb-6 list-disc space-y-2 pl-6 text-[#404753]">
{$sampleQuotaLines}
</ul>
<p class="mb-4 text-[#404753] leading-relaxed">Individual products may require a minimum tier or have their own sample limits. Sample fulfillment orders do not generate affiliate commission.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Payouts</h2>
<ul class="mb-4 list-disc space-y-2 pl-6 text-[#404753]">
<li>Your dashboard shows <strong>pending</strong> commission (earned, not yet paid) and <strong>paid</strong> history after we mark payouts.</li>
<li><strong>Payout timing:</strong> we pay commissions approximately <strong>{$payoutDelayDays} calendar days</strong> after the attributed order is marked <strong>successfully delivered</strong> (shipment delivered to the customer). Until that date passes, the amount remains pending even if payout details are on file.</li>
<li>You must complete payout setup in Account setup. Supported methods: {$payoutMethods}.</li>
<li>Payouts are processed <strong>manually by our team</strong> on a periodic basis—we will contact you or process balances as operational schedules allow. There is no automatic instant payout in the portal.</li>
<li>We may delay payouts while investigating suspected abuse, chargebacks, incomplete payout details, or policy violations.</li>
<li>You are responsible for any taxes owed on amounts we pay you.</li>
</ul>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Promotional codes</h2>
<p class="text-[#404753] leading-relaxed">Some promo codes may be linked to a specific affiliate. When a customer uses such a code at checkout, attribution may be credited as a <strong>coupon</strong> attribution (which can override the last-click cookie). Commission still requires all eligibility rules above. Affiliates cannot create their own promo codes in the portal—codes are assigned by {$brand}.</p>
HTML
                ),
            ],
            [
                'title' => 'Attribution & Cookie Policy',
                'slug' => 'affiliate-attribution-cookie-policy',
                'menu_title' => 'Attribution & Cookies',
                'sort_order' => 104,
                'excerpt' => 'How referral links, ref codes, cookies, and promo codes attribute sales to affiliates.',
                'meta_title' => 'Affiliate Attribution & Cookie Policy — '.$brand,
                'meta_description' => 'Last-click attribution and cookie duration for '.$brand.' affiliate links.',
                'content' => $this->wrap(
                    'Attribution & Cookie Policy',
                    'How we credit sales to your affiliate link or ref code.',
                    <<<HTML
<h2 class="creator-font-headline mb-3 text-2xl font-semibold text-[#0b1c30]">Referral links</h2>
<p class="mb-4 text-[#404753] leading-relaxed">Affiliate traffic is tracked with the <code class="rounded bg-[#eff4ff] px-1.5 py-0.5 text-sm">{$refParam}</code> query parameter on approved storefront URLs, for example: <code class="rounded bg-[#eff4ff] px-1.5 py-0.5 text-sm">?{$refParam}=your-code</code>. Your unique code is shown in the creator dashboard after approval.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Last-click cookie</h2>
<p class="mb-4 text-[#404753] leading-relaxed">When a visitor clicks a valid referral link, we store a last-click attribution cookie (default name configured in our system). A later visit from another affiliate&apos;s link within the cookie lifetime can replace the stored code. The affiliate credited at <strong>checkout</strong> is determined by active cookie or qualifying promo code, subject to self-referral blocking.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Cookie duration</h2>
<p class="mb-4 text-[#404753] leading-relaxed">The default affiliate cookie lasts approximately <strong>{$cookieDays} days</strong> from the last qualifying visit (browser settings and applicable law may affect storage). This window also applies to new-customer follow-up commission eligibility when that program rule is active.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Promo code vs cookie</h2>
<p class="mb-4 text-[#404753] leading-relaxed">If a customer applies an affiliate-linked promo code at checkout, that code may take priority over the cookie for order attribution (<code class="rounded bg-[#eff4ff] px-1 py-0.5 text-xs">coupon</code> attribution). Standard checkout flows support this; some alternative payment flows may attribute differently—contact us if you believe an order was mis-credited.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">UTM parameters</h2>
<p class="mb-4 text-[#404753] leading-relaxed"><strong>UTM tags do not determine commission.</strong> We may capture utm_source, utm_medium, utm_campaign, and related fields for analytics and traffic reports in your dashboard. Commission always depends on ref/cookie or affiliate-linked promo codes, not UTM alone.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Cross-domain</h2>
<p class="mb-4 text-[#404753] leading-relaxed">If you share links on the creator portal subdomain and customers purchase on the main store, attribution may rely on a shared parent cookie domain configured for our shop. Attribution applies only when technical requirements are met.</p>
<h2 class="creator-font-headline mb-3 mt-8 text-2xl font-semibold text-[#0b1c30]">Disputes</h2>
<p class="text-[#404753] leading-relaxed">Contact us with order numbers and timestamps if you believe a sale was misattributed. We review server logs, cookies, and checkout data in good faith; our program determination is final for commission purposes.</p>
HTML
                ),
            ],
        ];

        foreach ($pages as $data) {
            Page::query()->updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'user_id' => $admin->id,
                    'title' => $data['title'],
                    'content' => $data['content'],
                    'excerpt' => $data['excerpt'],
                    'status' => 'published',
                    'published_at' => now(),
                    'template' => 'default',
                    'show_in_menu' => false,
                    'menu_title' => $data['menu_title'],
                    'sort_order' => $data['sort_order'],
                    'meta_title' => $data['meta_title'],
                    'meta_description' => $data['meta_description'],
                ]
            );
        }

        $this->command?->info('Affiliate policy pages seeded (updateOrCreate by slug).');
    }

    private function wrap(string $title, string $subtitle, string $body): string
    {
        $updated = now()->format('F d, Y');

        return <<<HTML
<div class="max-w-4xl">
    <div class="mb-8 border-b border-[#bfc7d5] pb-6">
        <h1 class="creator-font-headline text-3xl font-bold text-[#0b1c30] md:text-4xl">{$title}</h1>
        <p class="mt-2 text-lg text-[#404753]">{$subtitle}</p>
        <p class="creator-font-label mt-3 text-xs font-medium uppercase tracking-widest text-[#707884]">Last updated: {$updated}</p>
    </div>
    <div class="prose-policy space-y-2">
        {$body}
    </div>
</div>
HTML;
    }
}
