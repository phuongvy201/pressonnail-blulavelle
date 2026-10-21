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

    // Words that carry no product meaning in English or Vietnamese (already
    // diacritic-normalised) and are dropped before matching.
    'stopwords' => [
        'a', 'an', 'the', 'for', 'me', 'my', 'i', 'show', 'find', 'looking', 'look',
        'please', 'want', 'need', 'get', 'some', 'set', 'sets',
        'kiem', 'cho', 'toi', 'minh', 'muon', 'can', 'bo', 'nao', 'dep',
        'mau', 'kieu', 'loai', 'gia', 'duoi', 'tren', 'voi', 'va', 'di', 'lam',
    ],

    // Multi-word phrases are matched before single words, longest first.
    'phrases' => [
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
        'press on' => ['press on'],
    ],

    // Vietnamese (diacritic-normalised) and common alias -> English catalogue terms.
    'synonyms' => [
        'mong' => ['nails'],
        'tay' => ['nails'],
        'ngan' => ['short'],
        'dai' => ['long'],
        'vua' => ['medium'],
        'hong' => ['pink', 'blush', 'rose'],
        'do' => ['red'],
        'trang' => ['white', 'ivory'],
        'den' => ['black'],
        'xanh' => ['blue'],
        'xanh la' => ['green'],
        'tim' => ['purple'],
        'vang' => ['gold', 'yellow'],
        'bac' => ['silver'],
        'nude' => ['nude', 'natural'],
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
