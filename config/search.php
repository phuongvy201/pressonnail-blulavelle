<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hybrid smart product search
    |--------------------------------------------------------------------------
    |
    | Keyword/fuzzy matching always works locally. Semantic ranking is an
    | optional rerank layer: when embeddings are unavailable or the provider
    | fails, search silently falls back to the keyword score.
    |
    */
    'embedding_enabled' => filter_var(env('SEARCH_EMBEDDING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'embedding_api_key' => env('SEARCH_EMBEDDING_API_KEY') ?: env('CHATBOT_API_KEY') ?: env('OPENAI_API_KEY', ''),
    'embedding_base_url' => rtrim((string) (env('SEARCH_EMBEDDING_BASE_URL') ?: env('CHATBOT_BASE_URL') ?: env('OPENAI_BASE_URL', 'https://api.openai.com/v1')), '/'),
    'embedding_model' => env('SEARCH_EMBEDDING_MODEL', 'text-embedding-3-small'),
    'embedding_timeout' => max(3, (int) env('SEARCH_EMBEDDING_TIMEOUT', 10)),
    'candidate_limit' => max(20, (int) env('SEARCH_CANDIDATE_LIMIT', 300)),
    'semantic_weight' => 0.35,
    'semantic_min_similarity' => 0.18,

    // Words that carry no product meaning (already diacritic-normalised) and
    // are dropped before matching. English is the primary storefront
    // language; question fillers from natural English queries come first.
    'stopwords' => [
        'a', 'an', 'the', 'for', 'me', 'my', 'i', 'you', 'your', 'we', 'what',
        'which', 'should', 'would', 'could', 'can', 'do', 'does', 'did', 'is',
        'are', 'have', 'has', 'wear', 'wearing', 'go', 'goes', 'going', 'with',
        'without', 'something', 'anything', 'show', 'find', 'looking', 'look',
        'please', 'want', 'need', 'get', 'some', 'set', 'sets', 'pair', 'style',
        'styles', 'good', 'best', 'nice',
        // Secondary: Vietnamese fillers (kept, harmless for English queries).
        'kiem', 'cho', 'toi', 'minh', 'muon', 'bo', 'nao', 'dep',
        'mau', 'kieu', 'loai', 'gia', 'duoi', 'tren', 'voi', 'va', 'lam',
    ],

    // Multi-word phrases are matched before single words, longest first.
    // English occasion/style phrases are the primary market language.
    'phrases' => [
        'for a wedding' => ['wedding', 'bridal', 'elegant'],
        'wedding guest' => ['wedding', 'elegant'],
        'as a wedding guest' => ['wedding', 'elegant'],
        'date night' => ['elegant', 'romantic'],
        'prom night' => ['prom', 'glam', 'elegant'],
        'for prom' => ['prom', 'glam'],
        'every day' => ['everyday', 'natural'],
        'everyday wear' => ['everyday', 'natural'],
        'for work' => ['office', 'natural', 'professional'],
        'for the office' => ['office', 'natural', 'professional'],
        'for a party' => ['party', 'glam'],
        'birthday party' => ['party', 'fun', 'colorful'],
        'for vacation' => ['vacation', 'summer', 'colorful'],
        'press on' => ['press on'],
        'cat eye' => ['cat eye'],
        // Secondary: Vietnamese phrases.
        'mong tay' => ['nails', 'press on'],
        'mong gia' => ['press on', 'nails'],
        'mong ngan' => ['short', 'nails'],
        'mong dai' => ['long', 'nails'],
        'di tiec' => ['party', 'elegant'],
        'di cuoi' => ['wedding', 'bridal', 'elegant'],
        'dam cuoi' => ['wedding', 'bridal'],
        'hang ngay' => ['everyday', 'natural'],
        'ca tinh' => ['bold'],
        'nhe nhang' => ['natural', 'soft'],
        'sang trong' => ['elegant', 'luxury'],
    ],

    // English aliases first (shapes, colours, finishes, occasions as US/UK
    // customers actually type them), then Vietnamese equivalents.
    'synonyms' => [
        'ballerina' => ['coffin'],
        'squoval' => ['square', 'oval'],
        'pointy' => ['stiletto'],
        'pointed' => ['stiletto'],
        'sparkly' => ['glitter'],
        'sparkle' => ['glitter'],
        'shiny' => ['glossy'],
        'bridal' => ['wedding', 'bridal', 'ivory'],
        'wedding' => ['wedding', 'bridal'],
        'prom' => ['prom', 'glam'],
        'glam' => ['glam', 'glitter', 'elegant'],
        'classy' => ['elegant', 'classic'],
        'elegant' => ['elegant', 'classic'],
        'cute' => ['cute', 'pink'],
        'natural' => ['natural', 'nude'],
        'nude' => ['nude', 'natural', 'beige'],
        'crimson' => ['red', 'burgundy'],
        'burgundy' => ['burgundy', 'wine', 'red'],
        'wine' => ['burgundy', 'red'],
        'blush' => ['blush', 'pink'],
        'pastel' => ['pastel', 'pink', 'lavender'],
        'lavender' => ['lavender', 'purple'],
        'french' => ['french', 'classic', 'white'],
        'tips' => ['nails'],
        'claws' => ['stiletto', 'long'],
        // Vietnamese (diacritic-normalised) equivalents.
        'hong' => ['pink', 'blush', 'rose'],
        'mong' => ['nails'],
        'tay' => ['nails'],
        'ngan' => ['short'],
        'dai' => ['long'],
        'vua' => ['medium'],
        'do' => ['red'],
        'trang' => ['white', 'ivory'],
        'den' => ['black'],
        'xanh' => ['blue'],
        'xanh la' => ['green'],
        'tim' => ['purple'],
        'vang' => ['gold', 'yellow'],
        'bac' => ['silver'],
        'trong' => ['clear'],
        'cuoi' => ['wedding', 'bridal'],
        'tiec' => ['party'],
        'hanh nhan' => ['almond'],
        'oval' => ['oval'],
        'vuong' => ['square'],
        'nhon' => ['stiletto'],
        'quan tai' => ['coffin'],
        'phap' => ['french'],
        'kim tuyen' => ['glitter'],
        'ngoc trai' => ['pearl'],
        'da' => ['rhinestone', 'crystal'],
        'hoa' => ['floral', 'flower'],
        'buom' => ['butterfly'],
        'trai tim' => ['heart'],
        'mat meo' => ['cat eye'],
        'bong' => ['glossy'],
        'li' => ['matte'],
    ],
];
