<?php

namespace App\Services\Chatbot;

use App\Models\ChatConversation;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
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
        'set', 'sets', 'product', 'products',
    ];

    /**
     * @return list<array{title: string, body: string, url: string|null, source: string}>
     */
    public function faqMatches(string $question, int $limit = 3): array
    {
        $terms = $this->terms($question);
        $matches = [];

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
            if ($score > 0) {
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
                    $query->orWhere('slug', 'like', '%size%')
                        ->orWhere('title', 'like', '%size%');
                }
            })
            ->limit(12)
            ->get();

        foreach ($pages as $page) {
            $plain = $this->plainText((string) $page->content);
            $score = $this->score((string) $page->title, $terms) * 4
                + $this->score((string) $page->slug, $terms) * 3
                + $this->score($plain, $terms);

            if ($score <= 0 && ! in_array($page->slug, $this->relevantSlugs($question), true)) {
                continue;
            }

            $matches[] = [
                'title' => (string) $page->title,
                'body' => $this->excerpt($plain, $terms),
                'url' => route('page.show', $page->slug),
                'source' => 'page:'.$page->slug,
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

        $position = null;
        foreach ($terms as $term) {
            $found = mb_stripos($text, $term);
            if ($found !== false) {
                $position = $found;
                break;
            }
        }

        if ($position === null) {
            return Str::limit($text, 650, '');
        }

        $start = max(0, $position - 180);

        return Str::limit(mb_substr($text, $start, 650), 650, '');
    }

    private function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }
}
