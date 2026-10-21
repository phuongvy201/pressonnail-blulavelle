<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductEmbedding;
use App\Services\Search\EmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateProductEmbedding implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $productId) {}

    public function handle(EmbeddingService $embeddings): void
    {
        if (! $embeddings->enabled()) {
            return;
        }

        $product = Product::query()
            ->with(['template.category', 'variants'])
            ->find($this->productId);
        if (! $product) {
            return;
        }

        $text = $product->searchIndexText();
        if ($text === '') {
            return;
        }

        $hash = sha1($text);
        $model = $embeddings->model();
        $existing = ProductEmbedding::query()
            ->where('product_id', $product->id)
            ->where('model', $model)
            ->first();
        if ($existing && $existing->content_hash === $hash) {
            return;
        }

        $vector = $embeddings->embed($text);
        if ($vector === null) {
            return;
        }

        ProductEmbedding::query()->updateOrCreate(
            ['product_id' => $product->id, 'model' => $model],
            ['content_hash' => $hash, 'vector' => $vector],
        );
    }
}
