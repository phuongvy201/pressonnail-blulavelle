<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\CollectionAutoAssignService;

class ProductObserver
{
    public function saved(Product $product): void
    {
        if ($product->status !== 'active') {
            return;
        }

        if (! $this->shouldSync($product)) {
            return;
        }

        $service = app(CollectionAutoAssignService::class);
        $service->forgetCache();
        $service->syncProduct($product);
    }

    protected function shouldSync(Product $product): bool
    {
        if ($product->wasRecentlyCreated) {
            return true;
        }

        if ($product->wasChanged('status')) {
            return true;
        }

        return $product->wasChanged(['name', 'description', 'meta_keywords']);
    }
}
