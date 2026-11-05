<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Collection;
use App\Services\SearchService;
use Illuminate\Support\Str;

class ChatbotKnowledgeService
{
    public function __construct(private readonly SearchService $search) {}

    private const STOP_WORDS = [
        'a', 'an', 'and', 'are', 'can', 'could', 'do', 'does', 'for', 'have', 'how',
        'i', 'is', 'it', 'long', 'me', 'my', 'of', 'please', 'show', 'take', 'the',
        'to', 'under', 'want', 'what', 'when', 'where', 'with', 'you', 'your',
        'find', 'looking', 'need', 'buy', 'shop', 'get', 'give', 'nail', 'nails',
        'set', 'sets', 'product', 'products', 'about', 'press', 'presson',
    ];

    /** Brand/marketing pages that must not answer product or policy questions. */
    private const BRAND_PAGE_SLUGS = [
        'about-us',
        'about',
        'our-story',
        'blulavelle-story',
        'welcome',
        'home',
        'contact',
        'contact-us',
    ];

    /**
     * @return list<array{title: string, body: string, url: string|null, source: string}>
     */
    public function faqMatches(string $question, int $limit = 3): array
    {
        $terms = $this->terms($question);
        $matches = [];

        // The Sizing Kit is a dedicated route (/sizing-kit), not a CMS page,
        // so it must be offered as a first-class knowledge source.
        if ($this->isSizingQuestion($question)) {
            $matches[] = $this->sizingKitFact();
        }

        $faq = function_exists('content_block')
            ? content_block('layout.footer_faq', function_exists('footer_faq_block_defaults') ? footer_faq_block_defaults() : [])
            : [];

        for ($index = 1; $index <= 4; $index++) {
            $title = trim((string) ($faq['q'.$index] ?? ''));
            $body = trim((string) ($faq['a'.$index] ?? ''));
            if ($title === '' || $body === '') {
                continue;
            }

            $score = $this->score($title, $terms) * 3 + $this->score($body, $terms);
            // Require a real topical hit — a single shared filler word is not enough.
            if ($score >= 3) {
                $matches[] = [
                    'title' => $title,
                    'body' => $body,
                    'url' => url('/#footer-faq'),
                    'source' => 'footer_faq',
                    'score' => $score,
                ];
            }
        }

        $pages = Page::query()
            ->published()
            ->where(function ($query) use ($terms, $question) {
                $query->whereIn('slug', [
                    'faqs', 'help-center', 'shipping-delivery', 'returns-exchanges-policy',
                    'refund-policy', 'cancelchange-order', 'secure-payments',
                ]);

                foreach (array_slice($terms, 0, 8) as $term) {
                    $query->orWhere('title', 'like', '%'.$term.'%')
                        ->orWhere('content', 'like', '%'.$term.'%')
                        ->orWhere('excerpt', 'like', '%'.$term.'%');
                }

                if (preg_match('/\bsize|sizing|fit\b/i', $question) === 1) {
                    $query->orWhere('slug', 'like', '%siz%')
                        ->orWhere('title', 'like', '%siz%');
                }
            })
            ->limit(12)
            ->get();

        $productInquiry = $this->isProductInquiry($question);
        $asksAboutBrand = preg_match('/\b(about (you|blulavelle|blu lavelle)|who (are|is) (you|blulavelle)|your story|company)\b/i', $question) === 1;

        foreach ($pages as $page) {
            $slug = (string) $page->slug;
            if ($productInquiry && in_array($slug, self::BRAND_PAGE_SLUGS, true)) {
                continue;
            }
            if (! $asksAboutBrand && in_array($slug, self::BRAND_PAGE_SLUGS, true)) {
                continue;
            }

            $plain = $this->plainText((string) $page->content);
            $titleSlugScore = $this->score((string) $page->title, $terms) * 4
                + $this->score((string) $page->slug, $terms) * 3;
            $contentScore = $this->score($plain, $terms);
            $score = $titleSlugScore + $contentScore;

            // A page whose title/slug matches the question topic (for
            // example a sizing guide) outranks generic pages that merely
            // contain one shared word such as "choose".
            $topicHit = $this->topicMatchesPage($question, $slug, (string) $page->title);
            if ($topicHit) {
                $score += 10;
            }

            $allowlisted = in_array($slug, $this->relevantSlugs($question), true);
            // Reject content-only hits on long pages (the old About Us failure mode).
            if (! $allowlisted && ! $topicHit && $titleSlugScore < 4) {
                continue;
            }
            if ($score < 4 && ! $allowlisted) {
                continue;
            }

            $matches[] = [
                'title' => (string) $page->title,
                'body' => $this->excerpt($plain, $terms),
                'url' => route('page.show', $slug),
                'source' => 'page:'.$slug,
                'score' => max(1, $score),
            ];
        }

        usort($matches, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return collect($matches)
            ->take($limit)
            ->map(fn (array $match) => collect($match)->except('score')->all())
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, price: float, url: string, image: string|null, description: string}>
     */
    public function searchProducts(string $question, int $limit = 3): array
    {
        $maxPrice = $this->maximumPrice($question);
        $filters = $maxPrice !== null ? ['price_max' => $maxPrice] : [];

        return $this->search->searchProducts($question, $filters, $limit)
            ->map(function (Product $product) {
                $image = $product->primary_image;

                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'price' => (float) $product->price,
                    'url' => route('products.show', $product->slug),
                    'image' => is_string($image) ? $image : null,
                    'description' => Str::limit($this->plainText($product->getEffectiveDescription()), 180, ''),
                ];
            })
            ->values()
            ->all();
    }

    public function extractOrderNumber(string $question): ?string
    {
        if (preg_match('/\b[A-Z]{2,}(?:-[A-Z0-9]+){1,5}\b/i', $question, $matches) !== 1) {
            return null;
        }

        $candidate = strtoupper($matches[0]);

        // Avoid treating ordinary hyphenated words as order numbers.
        return preg_match('/\d/', $candidate) === 1 ? $candidate : null;
    }

    public function findOrderForConversation(ChatConversation $conversation, string $orderNumber): ?Order
    {
        $query = Order::query()->where('order_number', $orderNumber);

        if ($conversation->customer_user_id) {
            $customerEmail = $conversation->customer?->email;
            $query->where(function ($ownerQuery) use ($conversation, $customerEmail) {
                $ownerQuery->where('user_id', $conversation->customer_user_id);
                if (filled($customerEmail)) {
                    $ownerQuery->orWhereRaw('LOWER(customer_email) = ?', [mb_strtolower((string) $customerEmail)]);
                }
            });
        } elseif (filled($conversation->guest_email)) {
            $query->whereRaw('LOWER(customer_email) = ?', [mb_strtolower((string) $conversation->guest_email)]);
        } else {
            return null;
        }

        return $query->first();
    }

    /**
     * Recent orders that belong to the person in this chat.
     *
     * @return Collection<int, Order>
     */
    public function recentOrdersForConversation(ChatConversation $conversation, int $limit = 1): Collection
    {
        $query = Order::query()->with('items')->latest('id');

        if ($conversation->customer_user_id) {
            $customerEmail = $conversation->customer?->email;
            $query->where(function ($ownerQuery) use ($conversation, $customerEmail) {
                $ownerQuery->where('user_id', $conversation->customer_user_id);
                if (filled($customerEmail)) {
                    $ownerQuery->orWhereRaw('LOWER(customer_email) = ?', [mb_strtolower((string) $customerEmail)]);
                }
            });
        } elseif (filled($conversation->guest_email)) {
            $query->whereRaw('LOWER(customer_email) = ?', [mb_strtolower((string) $conversation->guest_email)]);
        } else {
            return collect();
        }

        return $query->limit(max(1, $limit))->get();
    }

    /**
     * Nail size saved on an order line, when checkout stored a variant.
     */
    public function sizeLabelFromOptions(mixed $options): ?string
    {
        if (! is_array($options)) {
            return null;
        }

        $variant = $options['selected_variant'] ?? null;
        if (! is_array($variant)) {
            return null;
        }

        $attributes = $variant['attributes'] ?? null;
        if (is_array($attributes) && $attributes !== []) {
            return $this->formatVariantAttributes($attributes);
        }

        foreach (['variant_name', 'name', 'size'] as $key) {
            if (isset($variant[$key]) && is_scalar($variant[$key]) && trim((string) $variant[$key]) !== '') {
                $label = trim((string) $variant[$key]);

                return $key === 'size' ? 'Size: '.$label : $label;
            }
        }

        return $this->formatVariantAttributes($variant);
    }

    /**
     * Customer is asking about a specific set / style, not a policy FAQ.
     */
    public function isProductInquiry(string $question): bool
    {
        $question = trim($question);
        if ($question === '') {
            return false;
        }

        if (preg_match('/\b(ship|deliver|return|refund|cancel|track|tracking|sizing kit|size chart|how long (do|does)|policy)\b/i', $question) === 1
            && preg_match('/\b(show|find|recommend|suggest|looking for|buy|shop)\b/i', $question) !== 1) {
            return false;
        }

        if (preg_match('/\b(show|find|recommend|suggest|looking for|buy|shop|almond|coffin|square|oval|stiletto|french)\b|\bunder\s*\$?\d/i', $question) === 1) {
            return true;
        }

        // “What about the pet portrait press-on nails” / named set questions.
        if (preg_match('/\bwhat about\b/i', $question) === 1) {
            return true;
        }

        // “What colours / styles / shapes do you have?”
        if (preg_match('/\b(what|which|any)\b.+\b(colou?rs?|styles?|designs?|shapes?|sets?)\b/i', $question) === 1) {
            return true;
        }

        if (preg_match('/press[- ]?on/i', $question) === 1
            && preg_match('/\b(nails?|sets?)\b/i', $question) === 1
            && count($this->terms($question)) >= 2) {
            return true;
        }

        return false;
    }

    /**
     * A question about a size the customer already chose, not how to measure.
     */
    public function isPreviousSizeQuestion(string $question): bool
    {
        $question = mb_strtolower(trim($question));
        if (! preg_match('/\b(size|sizing|fit)\b/', $question)) {
            return false;
        }

        if (preg_match('/\b(how (?:do|can|should) i (?:choose|pick|measure|find)|sizing kit|size chart|right size|correct size)\b/', $question) === 1) {
            return false;
        }

        return preg_match('/\b(last|previous(?:ly)?|preferred|preference|usual(?:ly)?|ordered|bought|wore|did i)\b/', $question) === 1;
    }

    /**
     * @param  array<mixed>  $attributes
     */
    private function formatVariantAttributes(array $attributes): ?string
    {
        $parts = [];
        foreach ($attributes as $key => $value) {
            if (! is_scalar($value) || trim((string) $value) === '') {
                continue;
            }
            if (in_array($key, ['id', 'variant_id', 'sku', 'price', 'image', 'customizations'], true)) {
                continue;
            }
            $parts[] = (is_string($key) ? ucfirst($key).': ' : '').trim((string) $value);
        }

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function orderFacts(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'tracking_number' => $order->tracking_number,
            'total' => (float) $order->total_amount,
            'currency' => $order->currency ?: 'USD',
            'placed_at' => $order->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<string>
     */
    private function terms(string $text): array
    {
        $words = preg_split('/[^a-z0-9]+/i', mb_strtolower($text)) ?: [];
        $terms = [];
        foreach ($words as $word) {
            $word = trim($word);
            if ($word === '' || in_array($word, self::STOP_WORDS, true) || is_numeric($word)) {
                continue;
            }
            $terms[] = $word;
        }

        return array_values(array_unique($terms));
    }

    /**
     * @param  list<string>  $terms
     */
    private function score(string $text, array $terms): int
    {
        $text = mb_strtolower($text);
        $score = 0;
        foreach ($terms as $term) {
            if ($term !== '' && str_contains($text, $term)) {
                $score++;
            }
        }

        return $score;
    }

    private function maximumPrice(string $question): ?float
    {
        if (preg_match('/(?:under|below|less than|max(?:imum)?)\s*\$?\s*(\d+(?:\.\d{1,2})?)/i', $question, $matches) !== 1) {
            return null;
        }

        return (float) $matches[1];
    }

    private function isSizingQuestion(string $question): bool
    {
        if ($this->isPreviousSizeQuestion($question)) {
            return false;
        }

        return preg_match('/\b(size|sizing|fit|fits|measure|measurement|measurements|mm|chart)\b/i', $question) === 1
            || str_contains(mb_strtolower($question), 'sizing kit');
    }

    /**
     * Verified facts from the dedicated Sizing Kit page and the shared
     * reference size chart used by that page.
     *
     * @return array{title: string, body: string, url: string, source: string, score: int}
     */
    private function sizingKitFact(): array
    {
        $body = 'Use the Sizing Kit. Press a clear tip on each nail, note the number from thumb to pinky, then compare it with the size chart. Between sizes, choose the larger one and file the sides.';

        return [
            'title' => 'Sizing Kit',
            'body' => $body,
            'url' => route('sizing-kit.index'),
            'source' => 'sizing_kit',
            'score' => 50,
        ];
    }

    private function topicMatchesPage(string $question, string $slug, string $title): bool
    {
        $question = mb_strtolower($question);
        $target = mb_strtolower($slug.' '.$title);

        $topics = [
            '/\b(size|sizing|fit|measure)\b/' => '/siz|fit|measur/',
            '/\b(ship|deliver|dispatch|arrive)\b/' => '/ship|deliver/',
            '/\b(return|exchange|refund)\b/' => '/return|exchange|refund/',
        ];

        foreach ($topics as $questionPattern => $pagePattern) {
            if (preg_match($questionPattern, $question) === 1
                && preg_match($pagePattern, $target) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function relevantSlugs(string $question): array
    {
        $question = mb_strtolower($question);
        $slugs = [];
        if (preg_match('/ship|deliver|dispatch|arrive/', $question) === 1) {
            $slugs[] = 'shipping-delivery';
        }
        if (preg_match('/return|exchange|refund/', $question) === 1) {
            $slugs[] = 'returns-exchanges-policy';
            $slugs[] = 'refund-policy';
        }
        if (preg_match('/size|sizing|fit/', $question) === 1) {
            $slugs[] = 'faqs';
            $slugs[] = 'help-center';
        }

        return $slugs;
    }

    /**
     * @param  list<string>  $terms
     */
    private function excerpt(string $text, array $terms): string
    {
        if ($text === '') {
            return '';
        }

        // Prefer whole sentences containing the question terms so excerpts
        // never start mid-boilerplate (addresses, footers, navigation).
        $sentences = preg_split('/(?<=[.!?])\s+/u', $text) ?: [];
        $bestIndex = null;
        $bestScore = 0;
        foreach ($sentences as $index => $sentence) {
            $sentenceScore = $this->score($sentence, $terms);
            if ($sentenceScore > $bestScore) {
                $bestScore = $sentenceScore;
                $bestIndex = $index;
            }
        }

        if ($bestIndex !== null && $bestScore > 0) {
            $picked = array_slice($sentences, $bestIndex, 3);

            return Str::limit(implode(' ', $picked), 280, '');
        }

        return Str::limit($text, 280, '');
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
