<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Support\MobileAppLinks;
use App\Support\Seo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogArticleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::role('admin')->first() ?? User::query()->first();
        if (! $user) {
            $this->command?->warn('BlogArticleSeeder skipped: no user exists yet.');

            return;
        }

        $categories = [
            'guides' => ['name' => 'Guides', 'description' => 'Step-by-step help for measuring, applying, and reusing press-on nails.', 'sort_order' => 1],
            'seasonal' => ['name' => 'Seasonal', 'description' => 'Holiday and occasion nail ideas.', 'sort_order' => 2],
            'trends' => ['name' => 'Trends', 'description' => 'Shapes, colors, and nail art styles.', 'sort_order' => 3],
            'app' => ['name' => 'The App', 'description' => 'Virtual try-on, the BluLavelle app, and ordering.', 'sort_order' => 4],
            'stories' => ['name' => 'Stories', 'description' => 'How sets are made and how to read the shop with confidence.', 'sort_order' => 5],
        ];
        $categoryIds = [];
        foreach ($categories as $slug => $category) {
            $categoryIds[$slug] = PostCategory::query()->updateOrCreate(
                ['slug' => $slug],
                $category + ['slug' => $slug]
            )->id;
        }

        $images = $this->productImages();
        $glue = Product::query()->availableForDisplay()
            ->where(function ($query): void {
                $query->where('name', 'like', '%glue%')
                    ->orWhere('name', 'like', '%adhesive%')
                    ->orWhere('name', 'like', '%tab%');
            })
            ->first();
        $designer = Shop::query()->where('shop_status', 'active')->orderBy('id')->first();
        $replacements = [
            '{{sizing_kit}}' => url('/sizing-kit'),
            '{{products}}' => url('/products'),
            '{{reviews}}' => url('/reviews'),
            '{{try_on}}' => url('/virtual-nail'),
            '{{shops}}' => url('/shops'),
            '{{app_store}}' => MobileAppLinks::iosUrl('blog_app_store') ?: 'https://apps.apple.com/app/blulavelle/id6826066333',
            '{{glue}}' => $glue ? url('/products/'.$glue->slug) : url('/search?q=nail+glue'),
            '{{designer_name}}' => $designer?->shop_name ?: 'independent BluLavelle artists',
            '{{designer_url}}' => $designer ? url('/shops/'.$designer->shop_slug) : url('/shops'),
            '{{designer_bio}}' => trim(strip_tags((string) ($designer->shop_description ?? '')))
                ?: 'Each shop on BluLavelle is run by an independent designer. Open a shop page to read that artist’s own description and see the sets they currently sell.',
        ];

        $created = 0;
        foreach ($this->articles() as $index => $article) {
            $image = $this->pickImage($images, (string) ($article['image_hint'] ?? ''), $index);
            $second = $this->pickImage($images, '', $index + 7);
            $html = strtr($this->render($article, $image, $second), $replacements);
            $excerpt = strtr((string) $article['excerpt'], $replacements);
            $slug = Str::slug($article['title']);
            $post = Post::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $user->id,
                    'shop_id' => $user->shop?->id,
                    'post_category_id' => $categoryIds[$article['category']] ?? null,
                    'title' => $article['title'],
                    'content' => $html,
                    'excerpt' => $excerpt,
                    'featured_image' => strlen($image['url']) <= 255 ? $image['url'] : null,
                    'gallery' => array_values(array_filter([
                        strlen($image['url']) <= 2000 ? $image['url'] : null,
                        strlen($second['url']) <= 2000 ? $second['url'] : null,
                    ])),
                    'status' => $article['status'],
                    'published_at' => $article['published_at'],
                    'type' => 'article',
                    'featured' => (bool) ($article['featured'] ?? false),
                    'allow_comments' => true,
                    'meta_title' => Str::limit($article['title'], 47, '').' | BluLavelle',
                    'meta_description' => Str::limit($excerpt, 160, ''),
                ]
            );
            $post->updateReadingTime();
            $created++;
        }

        $this->command?->info('Blog articles ready: '.$created.'. Product photos used: '.count($images).'.');
    }

    /**
     * @return list<array{url: string, name: string, slug: ?string}>
     */
    private function productImages(): array
    {
        $images = [];
        Product::query()->availableForDisplay()->with('template')->orderBy('id')->limit(120)->get()
            ->each(function (Product $product) use (&$images): void {
                $url = Seo::productImageUrl($product);
                if (! is_string($url) || $url === '') {
                    return;
                }
                $images[] = [
                    'url' => $url,
                    'name' => (string) $product->name,
                    'slug' => $product->slug,
                ];
            });

        if ($images === []) {
            $images[] = [
                'url' => url('/images/og/blulavelle-share.png'),
                'name' => 'BluLavelle press-on nails',
                'slug' => null,
            ];
        }

        return $images;
    }

    /**
     * @param  list<array{url: string, name: string, slug: ?string}>  $images
     * @return array{url: string, name: string, slug: ?string}
     */
    private function pickImage(array $images, string $hint, int $index): array
    {
        $hint = strtolower(trim($hint));
        if ($hint !== '') {
            foreach ($images as $image) {
                if (str_contains(strtolower($image['name']), $hint)) {
                    return $image;
                }
            }
        }

        return $images[$index % count($images)];
    }

    /**
     * @param  array{title: string, sections: list<array{heading?: string, paragraphs?: list<string>, list?: list<string>}>}  $article
     * @param  array{url: string, name: string, slug: ?string}  $image
     * @param  array{url: string, name: string, slug: ?string}  $second
     */
    private function render(array $article, array $image, array $second): string
    {
        $html = '<p>'.$this->escapeParagraphs($article['intro']).'</p>';
        $html .= $this->figure($image);
        foreach ($article['sections'] as $section) {
            if (! empty($section['heading'])) {
                $html .= '<h2>'.e($section['heading']).'</h2>';
            }
            foreach ($section['paragraphs'] ?? [] as $paragraph) {
                $html .= '<p>'.$paragraph.'</p>';
            }
            if (! empty($section['list'])) {
                $html .= '<ul>';
                foreach ($section['list'] as $item) {
                    $html .= '<li>'.$item.'</li>';
                }
                $html .= '</ul>';
            }
        }
        $html .= $this->figure($second);
        $html .= '<p>Sets shown in this article are from the BluLavelle catalog. Open a product page for the current price, shape, and included sizes.</p>';

        return $html;
    }

    /**
     * @param  array{url: string, name: string, slug: ?string}  $image
     */
    private function figure(array $image): string
    {
        $caption = e($image['name']);
        $link = $image['slug'] ? '<a href="'.e(url('/products/'.$image['slug'])).'">'.$caption.'</a>' : $caption;

        return '<figure><img src="'.e($image['url']).'" alt="'.e($image['name']).'" width="960" height="960"><figcaption>'.$link.'</figcaption></figure>';
    }

    private function escapeParagraphs(string $html): string
    {
        return $html;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function articles(): array
    {
        return require __DIR__.'/data/blog_articles.php';
    }
}
