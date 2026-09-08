<?php

/**
 * Regenerates public/api-v1-docs.html with full OpenAPI parameters + request bodies.
 * Run: php scripts/generate-api-v1-docs.php
 */

$q = static function (string $name, array $schema, string $description = '', bool $required = false): array {
    return array_filter([
        'name' => $name,
        'in' => 'query',
        'required' => $required ?: null,
        'description' => $description !== '' ? $description : null,
        'schema' => $schema,
    ], static fn ($v) => $v !== null && $v !== false);
};

$path = static function (string $name, array $schema, string $description = ''): array {
    return [
        'name' => $name,
        'in' => 'path',
        'required' => true,
        'description' => $description,
        'schema' => $schema,
    ];
};

$header = static function (string $name, array $schema, string $description = '', bool $required = false): array {
    return array_filter([
        'name' => $name,
        'in' => 'header',
        'required' => $required ?: null,
        'description' => $description !== '' ? $description : null,
        'schema' => $schema,
    ], static fn ($v) => $v !== null && $v !== false);
};

$jsonBody = static function (array $schema, bool $required = true): array {
    return [
        'required' => $required,
        'content' => [
            'application/json' => [
                'schema' => $schema,
            ],
        ],
    ];
};

$guestCartHeader = $header(
    'X-Guest-Cart-Token',
    ['type' => 'string', 'minLength' => 32, 'maxLength' => 64],
    'Guest cart/wishlist token. Returned on first cart/wishlist response; send on subsequent guest calls.'
);

$requestIdHeader = $header(
    'X-Request-ID',
    ['type' => 'string', 'minLength' => 8, 'maxLength' => 128, 'example' => 'ios-req-001'],
    'Optional client request id; echoed back on the response.'
);

$idempotencyHeader = $header(
    'Idempotency-Key',
    ['type' => 'string', 'minLength' => 8, 'maxLength' => 128, 'example' => 'chk-20260907-abc'],
    'Required for checkout attempts that create an order/charge.',
    true
);

$ops = static function (
    string $summary,
    string $tag,
    bool $auth = false,
    string $desc = '',
    array $parameters = [],
    ?array $requestBody = null
) use ($requestIdHeader, $guestCartHeader): array {
    $guestTags = ['Cart', 'Wishlist', 'Checkout', 'Payments', 'Virtual Nail'];
    $params = $parameters;
    $params[] = $requestIdHeader;
    if (in_array($tag, $guestTags, true) || str_contains(strtolower($summary), 'guest')) {
        $params[] = $guestCartHeader;
    }

    $op = [
        'tags' => [$tag],
        'summary' => $summary,
        'description' => $desc !== '' ? $desc : $summary,
        'security' => $auth ? [['bearerAuth' => []]] : [],
        'parameters' => array_values($params),
        // Responses are attached after $paths via attachResponses()
        'responses' => [],
    ];

    if ($requestBody !== null) {
        $op['requestBody'] = $requestBody;
    }

    return $op;
};

$addressSchema = [
    'type' => 'object',
    'required' => ['recipientName', 'line1', 'city', 'postalCode', 'countryCode'],
    'properties' => [
        'recipientName' => ['type' => 'string', 'maxLength' => 255, 'example' => 'Jane Doe'],
        'phone' => ['type' => 'string', 'maxLength' => 20, 'nullable' => true, 'example' => '+12025550123'],
        'line1' => ['type' => 'string', 'maxLength' => 255, 'example' => '123 Main St'],
        'line2' => ['type' => 'string', 'maxLength' => 255, 'nullable' => true],
        'city' => ['type' => 'string', 'maxLength' => 100, 'example' => 'Austin'],
        'stateProvince' => ['type' => 'string', 'maxLength' => 100, 'nullable' => true, 'example' => 'TX'],
        'postalCode' => ['type' => 'string', 'maxLength' => 20, 'example' => '78701'],
        'countryCode' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 2, 'example' => 'US'],
        'label' => ['type' => 'string', 'maxLength' => 40, 'nullable' => true, 'example' => 'Home'],
        'isDefaultShipping' => ['type' => 'boolean', 'default' => false],
        'isDefaultBilling' => ['type' => 'boolean', 'default' => false],
    ],
];

$cartAddSchema = [
    'type' => 'object',
    'required' => ['product_id'],
    'properties' => [
        'product_id' => ['type' => 'integer', 'minimum' => 1, 'example' => 10],
        'productId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Alias of product_id'],
        'quantity' => ['type' => 'integer', 'minimum' => 1, 'default' => 1, 'example' => 1],
        'selected_variant' => [
            'type' => 'object',
            'additionalProperties' => true,
            'example' => ['id' => 5, 'attributes' => ['Size' => 'M']],
        ],
        'selectedVariant' => [
            'type' => 'object',
            'additionalProperties' => true,
            'description' => 'Alias of selected_variant',
        ],
        'customizations' => ['type' => 'object', 'additionalProperties' => true],
        'price' => ['type' => 'number', 'minimum' => 0, 'description' => 'Optional override (major units)'],
    ],
];

$productListParams = [
    $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1, 'example' => 1], 'Page number (starts at 1)'),
    $q('perPage', ['type' => 'integer', 'default' => 18, 'minimum' => 1, 'maximum' => 48, 'example' => 18], 'Page size (1–48)'),
    $q('collectionId', ['type' => 'integer', 'minimum' => 1], 'Filter by collection ID'),
    $q('shopId', ['type' => 'integer', 'minimum' => 1], 'Filter by shop ID'),
    $q('minPrice', ['type' => 'number', 'format' => 'float', 'minimum' => 0, 'example' => 10], 'Minimum price in USD (query filter; response money is minor units)'),
    $q('maxPrice', ['type' => 'number', 'format' => 'float', 'minimum' => 0, 'example' => 50], 'Maximum price in USD'),
    $q('search', ['type' => 'string', 'maxLength' => 200, 'example' => 'almond'], 'Search keyword'),
    $q('searchScope', [
        'type' => 'string',
        'enum' => ['all', 'name', 'description', 'shop'],
        'default' => 'all',
    ], 'Where to apply search'),
    $q('sortBy', [
        'type' => 'string',
        'enum' => ['newest', 'price_asc', 'price_desc', 'name', 'popular'],
        'default' => 'newest',
    ], 'Sort order'),
    $q('inStock', ['type' => 'boolean', 'default' => true], 'Omit/true = in-stock only; false = include OOS'),
];

$paths = [
    '/api/v1/health' => [
        'get' => $ops('Health', 'Health', false, 'Service health + API version.'),
    ],
    '/api/v1/auth/csrf' => [
        'get' => $ops('Auth mode (csrfRequired=false)', 'Auth', false, 'iOS uses Bearer tokens; do not scrape HTML CSRF.'),
    ],
    '/api/v1/auth/captcha' => [
        'get' => $ops('reCAPTCHA config for register', 'Auth', false, 'Returns `{ required, siteKey, provider }`.'),
    ],
    '/api/v1/auth/register' => [
        'post' => $ops(
            'Register + Bearer token',
            'Auth',
            false,
            'Creates account and returns access token. Optional `X-Guest-Cart-Token` merges guest cart.',
            [$guestCartHeader],
            $jsonBody([
                'type' => 'object',
                'required' => ['name', 'email', 'password', 'passwordConfirmation'],
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 255, 'example' => 'Jane Doe'],
                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'jane@example.com'],
                    'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8],
                    'passwordConfirmation' => ['type' => 'string', 'format' => 'password'],
                    'deviceName' => ['type' => 'string', 'maxLength' => 120, 'example' => 'iPhone 16'],
                    'recaptchaToken' => ['type' => 'string', 'nullable' => true],
                ],
            ])
        ),
    ],
    '/api/v1/auth/login' => [
        'post' => $ops(
            'Login + Bearer token',
            'Auth',
            false,
            'Returns access token + user. Optional guest cart merge via header.',
            [$guestCartHeader],
            $jsonBody([
                'type' => 'object',
                'required' => ['email', 'password'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'password' => ['type' => 'string', 'format' => 'password'],
                    'deviceName' => ['type' => 'string', 'maxLength' => 120],
                ],
            ])
        ),
    ],
    '/api/v1/auth/forgot-password' => [
        'post' => $ops(
            'Forgot password',
            'Auth',
            false,
            'Always returns success if email format is valid (no account enumeration).',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['email'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
            ])
        ),
    ],
    '/api/v1/auth/reset-password' => [
        'post' => $ops(
            'Reset password',
            'Auth',
            false,
            'Reset with email token from forgot-password mail.',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['token', 'email', 'password', 'passwordConfirmation'],
                'properties' => [
                    'token' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8],
                    'passwordConfirmation' => ['type' => 'string', 'format' => 'password'],
                ],
            ])
        ),
    ],
    '/api/v1/auth/user' => [
        'get' => $ops('Current user', 'Auth', true),
    ],
    '/api/v1/auth/refresh' => [
        'post' => $ops('Rotate access token', 'Auth', true, 'Revokes current token and issues a new one.'),
    ],
    '/api/v1/auth/revoke' => [
        'post' => $ops('Revoke current token (logout)', 'Auth', true),
    ],
    '/api/v1/auth/change-password' => [
        'post' => $ops(
            'Change password',
            'Auth',
            true,
            'Changes password and revokes other sessions.',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['currentPassword', 'password', 'passwordConfirmation'],
                'properties' => [
                    'currentPassword' => ['type' => 'string', 'format' => 'password'],
                    'password' => ['type' => 'string', 'format' => 'password', 'minLength' => 8],
                    'passwordConfirmation' => ['type' => 'string', 'format' => 'password'],
                ],
            ])
        ),
    ],
    '/api/v1/auth/sessions' => [
        'get' => $ops('List sessions', 'Auth', true),
    ],
    '/api/v1/auth/sessions/{sessionId}' => [
        'delete' => $ops(
            'Revoke other session',
            'Auth',
            true,
            '',
            [$path('sessionId', ['type' => 'integer', 'minimum' => 1], 'Sanctum personal access token id')]
        ),
    ],
    '/api/v1/profile' => [
        'get' => $ops('Get profile', 'Profile', true),
        'patch' => $ops(
            'Update profile',
            'Profile',
            true,
            'Partial update for name/phone.',
            [],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string', 'maxLength' => 255],
                    'phone' => ['type' => 'string', 'maxLength' => 20, 'nullable' => true],
                ],
            ], false)
        ),
    ],
    '/api/v1/account/export' => [
        'get' => $ops('Export account data', 'Account', true),
    ],
    '/api/v1/account' => [
        'delete' => $ops(
            'Delete / anonymize account',
            'Account',
            true,
            'Requires confirmation password.',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['password'],
                'properties' => [
                    'password' => ['type' => 'string', 'format' => 'password'],
                ],
            ])
        ),
    ],
    '/api/v1/addresses' => [
        'get' => $ops('List addresses', 'Addresses', true),
        'post' => $ops('Create address', 'Addresses', true, '', [], $jsonBody($addressSchema)),
    ],
    '/api/v1/addresses/{addressId}' => [
        'get' => $ops(
            'Get address',
            'Addresses',
            true,
            '',
            [$path('addressId', ['type' => 'integer', 'minimum' => 1], 'Address id')]
        ),
        'patch' => $ops(
            'Update address',
            'Addresses',
            true,
            'Partial update; same fields as create.',
            [$path('addressId', ['type' => 'integer', 'minimum' => 1], 'Address id')],
            $jsonBody(array_merge($addressSchema, ['required' => []]), false)
        ),
        'delete' => $ops(
            'Delete address',
            'Addresses',
            true,
            '',
            [$path('addressId', ['type' => 'integer', 'minimum' => 1], 'Address id')]
        ),
    ],
    '/api/v1/addresses/{addressId}/default' => [
        'put' => $ops(
            'Set default address',
            'Addresses',
            true,
            'Defaults both shipping and billing to true when body omitted.',
            [$path('addressId', ['type' => 'integer', 'minimum' => 1], 'Address id')],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'shipping' => ['type' => 'boolean', 'default' => true],
                    'billing' => ['type' => 'boolean', 'default' => true],
                ],
            ], false)
        ),
    ],
    '/api/v1/catalog/products' => [
        'get' => $ops(
            'Product list',
            'Catalog',
            false,
            'Paginated list. Walk with `meta.hasNextPage` when present. Was `GET /api/products`.',
            $productListParams
        ),
    ],
    '/api/v1/catalog/products/{id}' => [
        'get' => $ops(
            'Product by ID',
            'Catalog',
            false,
            '',
            [$path('id', ['type' => 'integer', 'minimum' => 1, 'example' => 10], 'Product id')]
        ),
    ],
    '/api/v1/catalog/products/slug/{slug}' => [
        'get' => $ops(
            'Product by slug',
            'Catalog',
            false,
            'Was `GET /api/product/{slug}`.',
            [$path('slug', ['type' => 'string', 'example' => 'classic-set'], 'Product slug')]
        ),
    ],
    '/api/v1/catalog/collections' => [
        'get' => $ops('Collections', 'Catalog', false, 'Was `GET /api/collections`.', [
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 24, 'minimum' => 1, 'maximum' => 50]),
            $q('search', ['type' => 'string', 'maxLength' => 200]),
            $q('sortBy', ['type' => 'string', 'enum' => ['newest', 'name', 'popular'], 'default' => 'newest']),
            $q('shopId', ['type' => 'integer', 'minimum' => 1]),
        ]),
    ],
    '/api/v1/catalog/shops' => [
        'get' => $ops('Shops', 'Catalog', false, 'Was `GET /api/shops`.', [
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 24, 'minimum' => 1, 'maximum' => 50]),
            $q('search', ['type' => 'string', 'maxLength' => 200]),
            $q('sortBy', ['type' => 'string', 'enum' => ['newest', 'name', 'popular'], 'default' => 'newest']),
        ]),
    ],
    '/api/v1/catalog/templates' => [
        'get' => $ops('Templates', 'Catalog', false, 'Was `GET /api/templates`.', [
            $q('search', ['type' => 'string', 'maxLength' => 200]),
            $q('categoryId', ['type' => 'integer', 'minimum' => 1]),
            $q('sortBy', ['type' => 'string', 'enum' => ['newest', 'name', 'popular'], 'default' => 'newest']),
        ]),
    ],
    '/api/v1/catalog/templates/{id}' => [
        'get' => $ops(
            'Template detail',
            'Catalog',
            false,
            '',
            [$path('id', ['type' => 'integer', 'minimum' => 1], 'Template id')]
        ),
    ],
    '/api/v1/catalog/search/suggestions' => [
        'get' => $ops('Search suggestions', 'Catalog', false, '', [
            $q('q', ['type' => 'string', 'minLength' => 1, 'maxLength' => 200, 'example' => 'pink'], 'Search query', true),
            $q('limit', ['type' => 'integer', 'default' => 8, 'minimum' => 1, 'maximum' => 20]),
        ]),
    ],
    '/api/v1/cart' => [
        'get' => $ops('Get cart', 'Cart', false, 'Guest: send/store `X-Guest-Cart-Token`.', [
            $q('country', ['type' => 'string', 'minLength' => 2, 'maxLength' => 2, 'example' => 'US'], 'Shipping destination country for estimate'),
        ]),
        'post' => $ops(
            'Add to cart',
            'Cart',
            false,
            'Accepts `product_id` or `productId`.',
            [],
            $jsonBody($cartAddSchema)
        ),
        'delete' => $ops('Clear cart', 'Cart'),
    ],
    '/api/v1/cart/{itemId}' => [
        'put' => $ops(
            'Update cart item',
            'Cart',
            false,
            '',
            [$path('itemId', ['type' => 'integer', 'minimum' => 1, 'example' => 42], 'Cart row id')],
            $jsonBody([
                'type' => 'object',
                'required' => ['quantity'],
                'properties' => [
                    'quantity' => ['type' => 'integer', 'minimum' => 1, 'example' => 2],
                    'selected_variant' => ['type' => 'object', 'additionalProperties' => true],
                    'selectedVariant' => ['type' => 'object', 'additionalProperties' => true],
                    'customizations' => ['type' => 'object', 'additionalProperties' => true],
                    'price' => ['type' => 'number', 'minimum' => 0],
                ],
            ])
        ),
        'delete' => $ops(
            'Remove cart item',
            'Cart',
            false,
            '',
            [$path('itemId', ['type' => 'integer', 'minimum' => 1], 'Cart row id')]
        ),
    ],
    '/api/v1/cart/checkout' => [
        'get' => $ops('Checkout snapshot', 'Cart', false, 'Totals/shipping preview. Was `GET /api/checkout`.', [
            $q('country', ['type' => 'string', 'example' => 'US'], 'Destination country'),
        ]),
    ],
    '/api/v1/cart/promo' => [
        'post' => $ops(
            'Apply promo',
            'Cart',
            false,
            'Was `POST /api/cart/apply-promo`.',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['code'],
                'properties' => [
                    'code' => ['type' => 'string', 'maxLength' => 64, 'example' => 'SAVE10'],
                ],
            ])
        ),
        'delete' => $ops('Remove promo', 'Cart'),
    ],
    '/api/v1/cart/gift-card' => [
        'post' => $ops(
            'Apply gift card',
            'Cart',
            false,
            '',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['code'],
                'properties' => [
                    'code' => ['type' => 'string', 'maxLength' => 64, 'example' => 'GIFT-ABCD-1234'],
                ],
            ])
        ),
        'delete' => $ops('Remove gift card', 'Cart'),
    ],
    '/api/v1/cart/discount-mode' => [
        'post' => $ops(
            'Set discount mode',
            'Cart',
            false,
            'Switch between volume discount and promo mode.',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['mode'],
                'properties' => [
                    'mode' => ['type' => 'string', 'enum' => ['volume', 'promo'], 'example' => 'promo'],
                ],
            ])
        ),
    ],
    '/api/v1/cart/sync' => [
        'post' => $ops(
            'Sync guest cart after login',
            'Cart',
            false,
            'Merges guest session cart into the authenticated user cart.',
            [],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'items' => [
                        'type' => 'array',
                        'items' => $cartAddSchema,
                    ],
                ],
            ], false)
        ),
    ],
    '/api/v1/wishlist' => [
        'get' => $ops('List wishlist', 'Wishlist', false, 'Guest or authenticated.', [
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 24, 'minimum' => 1, 'maximum' => 50]),
        ]),
        'post' => $ops(
            'Add to wishlist',
            'Wishlist',
            false,
            '',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1, 'example' => 10],
                    'product_id' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Alias of productId'],
                ],
            ])
        ),
        'delete' => $ops(
            'Remove from wishlist',
            'Wishlist',
            false,
            '',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1],
                    'product_id' => ['type' => 'integer', 'minimum' => 1],
                ],
            ])
        ),
    ],
    '/api/v1/wishlist/toggle' => [
        'post' => $ops(
            'Toggle wishlist item',
            'Wishlist',
            false,
            '',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer', 'minimum' => 1],
                ],
            ])
        ),
    ],
    '/api/v1/wishlist/count' => [
        'get' => $ops('Wishlist count', 'Wishlist'),
    ],
    '/api/v1/wishlist/check' => [
        'post' => $ops(
            'Check products in wishlist',
            'Wishlist',
            false,
            '',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['productIds'],
                'properties' => [
                    'productIds' => [
                        'type' => 'array',
                        'items' => ['type' => 'integer', 'minimum' => 1],
                        'example' => [10, 11, 12],
                    ],
                ],
            ])
        ),
    ],
    '/api/v1/wishlist/clear' => [
        'delete' => $ops('Clear wishlist', 'Wishlist'),
    ],
    '/api/v1/orders' => [
        'get' => $ops('My orders', 'Orders', true, '', [
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 15, 'minimum' => 1, 'maximum' => 50]),
            $q('status', ['type' => 'string', 'example' => 'processing'], 'Filter by order status'),
            $q('search', ['type' => 'string', 'maxLength' => 100], 'Search by order number'),
        ]),
    ],
    '/api/v1/orders/track' => [
        'post' => $ops(
            'Track order by number + email',
            'Orders',
            false,
            'Public tracking — no Bearer required.',
            [],
            $jsonBody([
                'type' => 'object',
                'required' => ['email'],
                'properties' => [
                    'orderNumber' => ['type' => 'string', 'maxLength' => 64, 'example' => 'BL-100234'],
                    'order_number' => ['type' => 'string', 'maxLength' => 64, 'description' => 'Alias of orderNumber'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
            ])
        ),
    ],
    '/api/v1/orders/{orderNumber}' => [
        'get' => $ops(
            'Order detail',
            'Orders',
            true,
            '',
            [$path('orderNumber', ['type' => 'string', 'example' => 'BL-100234'], 'Order number')]
        ),
    ],
    '/api/v1/orders/{orderNumber}/cancel' => [
        'post' => $ops(
            'Cancel order',
            'Orders',
            true,
            'Only pending/processing orders.',
            [$path('orderNumber', ['type' => 'string'], 'Order number')]
        ),
    ],
    '/api/v1/orders/{orderNumber}/return-request' => [
        'post' => $ops(
            'Request return',
            'Orders',
            true,
            '',
            [$path('orderNumber', ['type' => 'string'], 'Order number')],
            $jsonBody([
                'type' => 'object',
                'required' => ['reason'],
                'properties' => [
                    'reason' => ['type' => 'string', 'maxLength' => 1000, 'example' => 'Wrong size'],
                    'notes' => ['type' => 'string', 'maxLength' => 2000, 'nullable' => true],
                ],
            ])
        ),
    ],
    '/api/v1/checkout/shipping/calculate' => [
        'post' => $ops(
            'Calculate shipping',
            'Checkout',
            false,
            'Was `POST /checkout/calculate-shipping`.',
            [],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'country' => ['type' => 'string', 'example' => 'US'],
                    'state' => ['type' => 'string', 'example' => 'TX'],
                    'postalCode' => ['type' => 'string', 'example' => '78701'],
                    'postal_code' => ['type' => 'string', 'description' => 'Alias of postalCode'],
                    'city' => ['type' => 'string'],
                ],
            ], false)
        ),
    ],
    '/api/v1/checkout/shipping/rates' => [
        'post' => $ops(
            'List shipping rates',
            'Checkout',
            false,
            'Was `POST /checkout/get-shipping-rates`.',
            [],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'country' => ['type' => 'string', 'example' => 'US'],
                    'state' => ['type' => 'string'],
                    'postalCode' => ['type' => 'string'],
                    'city' => ['type' => 'string'],
                ],
            ], false)
        ),
    ],
    '/api/v1/checkout/attempts' => [
        'post' => $ops(
            'Create/resume checkout attempt',
            'Checkout',
            false,
            'Preferred iOS checkout. Requires `Idempotency-Key`. Body mirrors web checkout fields (shipping address, payment method, etc.).',
            [$idempotencyHeader],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'shippingAddressId' => ['type' => 'integer', 'minimum' => 1, 'description' => 'Saved address id (logged-in)'],
                    'billingAddressId' => ['type' => 'integer', 'minimum' => 1],
                    'shipping_first_name' => ['type' => 'string'],
                    'shipping_last_name' => ['type' => 'string'],
                    'shipping_address' => ['type' => 'string'],
                    'shipping_city' => ['type' => 'string'],
                    'shipping_state' => ['type' => 'string'],
                    'shipping_postal_code' => ['type' => 'string'],
                    'shipping_country' => ['type' => 'string', 'example' => 'US'],
                    'shipping_phone' => ['type' => 'string'],
                    'payment_method' => ['type' => 'string', 'enum' => ['stripe', 'cod'], 'example' => 'stripe'],
                    'shipping_method' => ['type' => 'string'],
                    'notes' => ['type' => 'string', 'nullable' => true],
                ],
            ], false)
        ),
    ],
    '/api/v1/checkout/attempts/{attemptId}' => [
        'get' => $ops(
            'Get checkout attempt',
            'Checkout',
            false,
            'Recover after timeout / app kill.',
            [$path('attemptId', ['type' => 'string', 'example' => 'att_01HXYZ'], 'Attempt id')]
        ),
    ],
    '/api/v1/checkout/attempts/{attemptId}/confirm' => [
        'post' => $ops(
            'Confirm checkout attempt',
            'Checkout',
            false,
            '',
            [$path('attemptId', ['type' => 'string'], 'Attempt id')],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'paymentIntentId' => ['type' => 'string', 'example' => 'pi_3Abc'],
                    'payment_intent_id' => ['type' => 'string', 'description' => 'Alias'],
                ],
            ], false)
        ),
    ],
    '/api/v1/checkout/attempts/{attemptId}/retry' => [
        'post' => $ops(
            'Retry failed attempt',
            'Checkout',
            false,
            '',
            [$path('attemptId', ['type' => 'string'], 'Attempt id'), $idempotencyHeader]
        ),
    ],
    '/api/v1/payments/stripe/intent' => [
        'post' => $ops(
            'Create Stripe PaymentIntent',
            'Payments',
            false,
            'Was `POST /payment/stripe/create-payment-intent`. Prefer checkout attempts flow when possible.',
            [],
            $jsonBody([
                'type' => 'object',
                'properties' => [
                    'amount' => ['type' => 'integer', 'description' => 'Optional minor units override'],
                    'currency' => ['type' => 'string', 'default' => 'usd', 'example' => 'usd'],
                    'orderNumber' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                ],
            ], false)
        ),
    ],
    '/api/v1/nail/products/{productId}/try-on-config' => [
        'get' => $ops(
            'NailBox try-on config',
            'Nail Try-On',
            false,
            'Returns tryOnEnabled, variants, sample assets, unsupportedReason.',
            [$path('productId', ['type' => 'integer', 'minimum' => 1, 'example' => 10], 'Product id')]
        ),
    ],
    '/api/v1/uploads/presign' => [
        'post' => $ops(
            'Presign hand image upload',
            'Nail Try-On',
            false,
            'Creates a pending upload. PUT binary to uploadUrl, then POST complete. Requires guest token or Bearer.',
            [$guestCartHeader],
            $jsonBody([
                'type' => 'object',
                'required' => ['purpose', 'mimeType', 'size', 'checksum'],
                'properties' => [
                    'purpose' => ['type' => 'string', 'enum' => ['virtual_try_on_hand']],
                    'mimeType' => ['type' => 'string', 'enum' => ['image/jpeg', 'image/png', 'image/webp']],
                    'size' => ['type' => 'integer', 'example' => 2458120],
                    'checksum' => ['type' => 'string', 'example' => 'sha256:abcdef...'],
                ],
            ])
        ),
    ],
    '/api/v1/uploads/{uploadId}/complete' => [
        'post' => $ops(
            'Complete upload → handImageAssetId',
            'Nail Try-On',
            false,
            'Marks upload ready and returns handImageAssetId.',
            [$path('uploadId', ['type' => 'string', 'example' => 'asset_hand_01hxyz'], 'Upload id'), $guestCartHeader]
        ),
    ],
    '/api/v1/uploads/{uploadId}' => [
        'delete' => $ops(
            'Delete upload asset',
            'Nail Try-On',
            false,
            'Purges file and invalidates asset.',
            [$path('uploadId', ['type' => 'string'], 'Upload id'), $guestCartHeader]
        ),
    ],
    '/api/v1/nail/try-ons' => [
        'get' => $ops('List my try-on jobs', 'Nail Try-On', false, '', [
            $guestCartHeader,
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 50]),
        ]),
        'post' => $ops(
            'Create async try-on job',
            'Nail Try-On',
            false,
            'Returns 202 + tryOnId immediately. Requires Idempotency-Key.',
            [$guestCartHeader, $idempotencyHeader],
            $jsonBody([
                'type' => 'object',
                'required' => ['handImageAssetId', 'productId'],
                'properties' => [
                    'handImageAssetId' => ['type' => 'string', 'example' => 'asset_hand_123'],
                    'productId' => ['type' => 'integer', 'example' => 456],
                    'variantId' => ['type' => 'integer', 'nullable' => true, 'example' => 789],
                    'tryOnAssetVersion' => ['type' => 'string', 'example' => 'tryon_456_v3'],
                    'handSide' => ['type' => 'string', 'enum' => ['auto', 'left', 'right'], 'default' => 'auto'],
                ],
            ])
        ),
    ],
    '/api/v1/nail/try-ons/{tryOnId}' => [
        'get' => $ops(
            'Get try-on status/result',
            'Nail Try-On',
            false,
            'Statuses: queued, validating_image, detecting_hand, rendering, succeeded, failed, expired, deleted.',
            [$path('tryOnId', ['type' => 'string', 'example' => 'tryon_01hxyz'], 'Try-on id'), $guestCartHeader]
        ),
        'delete' => $ops(
            'Delete try-on + invalidate URLs',
            'Nail Try-On',
            false,
            'Deletes job and purges output asset so signed URLs stop working.',
            [$path('tryOnId', ['type' => 'string'], 'Try-on id'), $guestCartHeader]
        ),
    ],
    '/api/v1/nail/try-ons/{tryOnId}/variants' => [
        'post' => $ops(
            'Try another product/variant on same hand',
            'Nail Try-On',
            false,
            'Creates a new job linked to the same handImageAssetId. Does not overwrite parent result.',
            [$path('tryOnId', ['type' => 'string'], 'Parent try-on id'), $guestCartHeader, $idempotencyHeader],
            $jsonBody([
                'type' => 'object',
                'required' => ['productId'],
                'properties' => [
                    'productId' => ['type' => 'integer'],
                    'variantId' => ['type' => 'integer', 'nullable' => true],
                    'tryOnAssetVersion' => ['type' => 'string'],
                ],
            ])
        ),
    ],
    '/api/v1/nail/try-ons/{tryOnId}/retry' => [
        'post' => $ops(
            'Safe retry of failed/expired job',
            'Nail Try-On',
            false,
            'Requires Idempotency-Key. Does not double-bill when key is reused.',
            [$path('tryOnId', ['type' => 'string'], 'Try-on id'), $guestCartHeader, $idempotencyHeader]
        ),
    ],
    '/api/v1/virtual-nail/status' => [
        'get' => $ops('Virtual try-on feature status (legacy)', 'Virtual Nail'),
    ],
    '/api/v1/virtual-nail/products' => [
        'get' => $ops('Try-on eligible products', 'Virtual Nail', false, '', [
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 18, 'minimum' => 1, 'maximum' => 48]),
            $q('search', ['type' => 'string', 'maxLength' => 200]),
        ]),
    ],
    '/api/v1/virtual-nail/products/suggestions' => [
        'get' => $ops('Try-on product suggestions', 'Virtual Nail', false, '', [
            $q('q', ['type' => 'string', 'maxLength' => 200], 'Suggestion query'),
            $q('limit', ['type' => 'integer', 'default' => 8, 'minimum' => 1, 'maximum' => 20]),
        ]),
    ],
    '/api/v1/virtual-nail/picker-meta' => [
        'get' => $ops('Picker metadata', 'Virtual Nail'),
    ],
    '/api/v1/virtual-nail/products/{productId}/options' => [
        'get' => $ops(
            'Shape/length options',
            'Virtual Nail',
            false,
            '',
            [$path('productId', ['type' => 'integer', 'minimum' => 1], 'Product id')]
        ),
    ],
    '/api/v1/virtual-nail/try' => [
        'post' => $ops(
            'Start try-on job',
            'Virtual Nail',
            false,
            'Multipart preferred: hand image + product/variant selection.',
            [],
            [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'required' => ['product_id', 'image'],
                            'properties' => [
                                'product_id' => ['type' => 'integer', 'minimum' => 1],
                                'productId' => ['type' => 'integer', 'minimum' => 1],
                                'variant_id' => ['type' => 'integer', 'nullable' => true],
                                'variantId' => ['type' => 'integer', 'nullable' => true],
                                'shape' => ['type' => 'string', 'nullable' => true],
                                'length' => ['type' => 'string', 'nullable' => true],
                                'image' => ['type' => 'string', 'format' => 'binary', 'description' => 'Hand photo'],
                            ],
                        ],
                    ],
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'productId' => ['type' => 'integer'],
                                'variantId' => ['type' => 'integer', 'nullable' => true],
                                'shape' => ['type' => 'string', 'nullable' => true],
                                'length' => ['type' => 'string', 'nullable' => true],
                                'imageBase64' => ['type' => 'string', 'description' => 'If not using multipart'],
                            ],
                        ],
                    ],
                ],
            ]
        ),
    ],
    '/api/v1/virtual-nail/history' => [
        'get' => $ops('Try-on history', 'Virtual Nail', false, '', [
            $q('page', ['type' => 'integer', 'default' => 1, 'minimum' => 1]),
            $q('perPage', ['type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 50]),
        ]),
    ],
    '/api/v1/virtual-nail/pending' => [
        'get' => $ops('Pending trials', 'Virtual Nail'),
    ],
    '/api/v1/virtual-nail/trials/{uuid}/status' => [
        'get' => $ops(
            'Trial status',
            'Virtual Nail',
            false,
            '',
            [$path('uuid', ['type' => 'string', 'format' => 'uuid'], 'Trial uuid')]
        ),
    ],
    '/api/v1/virtual-nail/trials/{uuid}/result' => [
        'get' => $ops(
            'Trial result',
            'Virtual Nail',
            false,
            '',
            [$path('uuid', ['type' => 'string', 'format' => 'uuid'], 'Trial uuid')]
        ),
    ],
    '/api/v1/virtual-nail/trials/{uuid}/hand' => [
        'get' => $ops(
            'Trial hand image',
            'Virtual Nail',
            false,
            'May return image bytes or JSON envelope depending on storage.',
            [$path('uuid', ['type' => 'string', 'format' => 'uuid'], 'Trial uuid')]
        ),
    ],
];

$userExample = [
    'id' => 1,
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'phone' => '+12025550123',
    'avatar' => null,
    'emailVerified' => true,
    'createdAt' => '2026-09-01T12:00:00Z',
    'updatedAt' => '2026-09-07T08:00:00Z',
];

$tokenExample = [
    'token' => '1|plainTextSanctumTokenHere',
    'tokenType' => 'Bearer',
    'expiresAt' => '2026-10-07T08:00:00Z',
    'sessionId' => '42',
    'deviceName' => 'iPhone 16',
];

$addressExample = [
    'addressId' => 3,
    'recipientName' => 'Jane Doe',
    'phone' => '+12025550123',
    'line1' => '123 Main St',
    'line2' => null,
    'city' => 'Austin',
    'stateProvince' => 'TX',
    'postalCode' => '78701',
    'countryCode' => 'US',
    'label' => 'Home',
    'isDefaultShipping' => true,
    'isDefaultBilling' => true,
    'createdAt' => '2026-09-01T12:00:00Z',
    'updatedAt' => '2026-09-01T12:00:00Z',
];

$productListExample = [
    'items' => [[
        'id' => 10,
        'name' => 'Classic Set',
        'slug' => 'classic-set',
        'price' => 2499,
        'sale_price' => null,
        'primary_image' => 'https://cdn.example.com/classic.webp',
        'in_stock' => true,
    ]],
    'meta' => [
        'page' => 1,
        'perPage' => 18,
        'total' => 120,
        'hasNextPage' => true,
    ],
];

$cartExample = [
    'cart_items' => [[
        'id' => 42,
        'product_id' => 10,
        'variant_id' => 5,
        'quantity' => 1,
        'price' => 2499,
        'line_total' => 2499,
        'product' => [
            'id' => 10,
            'name' => 'Classic Set',
            'slug' => 'classic-set',
        ],
    ]],
    'total_items' => 1,
    'total_price' => 2499,
    'summary' => [
        'subtotal' => 2499,
        'shipping' => 599,
        'discount' => 0,
        'gift_card_discount' => 0,
        'total' => 3098,
    ],
    'currency' => 'USD',
];

$tryOnConfigExample = [
    'productId' => 10,
    'variantId' => null,
    'tryOnEnabled' => true,
    'tryOnAssetVersion' => '2026-09-01',
    'supportedVariants' => [
        ['shape' => 'almond', 'length' => 'medium'],
    ],
    'assets' => [
        'handGuideUrl' => 'https://cdn.example.com/try-on/hand-guide.webp',
        'designReferenceUrl' => 'https://cdn.example.com/products/classic.webp',
    ],
    'unsupportedReason' => null,
    'unsupportedMessage' => null,
];

$checkoutAttemptExample = [
    'attemptId' => 'att_01HXYZ',
    'status' => 'requires_payment',
    'orderNumber' => null,
    'clientSecret' => 'pi_3Abc_secret_xyz',
    'paymentIntentId' => 'pi_3Abc',
    'amount' => 3098,
    'currency' => 'usd',
];

/**
 * @param  array{data?:mixed,example?:mixed,metaExample?:mixed,status?:int,extra?:array<string,array>}  $cfg
 */
$buildResponses = static function (array $cfg = []): array {
    $status = (string) ($cfg['status'] ?? 200);
    $dataSchema = $cfg['data'] ?? ['type' => 'object', 'additionalProperties' => true];
    $exampleData = $cfg['example'] ?? new stdClass();
    $metaExample = $cfg['metaExample'] ?? null;

    $successExample = [
        'success' => true,
        'data' => $exampleData,
    ];
    if ($metaExample !== null) {
        $successExample['meta'] = $metaExample;
    }

    $successSchema = [
        'type' => 'object',
        'required' => ['success', 'data'],
        'properties' => [
            'success' => ['type' => 'boolean', 'enum' => [true]],
            'data' => $dataSchema,
            'meta' => [
                'type' => 'object',
                'nullable' => true,
                'additionalProperties' => true,
                'description' => 'Optional metadata (pagination, message, api version, replayed, …)',
            ],
        ],
    ];

    $errorSchema = ['$ref' => '#/components/schemas/ErrorEnvelope'];
    $errorExample = static function (string $code, string $message, int $http): array {
        return [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'fields' => new stdClass(),
            ],
            'requestId' => 'ios-req-001',
        ];
    };

    $json = static function (array $schema, mixed $example) {
        return [
            'application/json' => [
                'schema' => $schema,
                'example' => $example,
            ],
        ];
    };

    $responses = [
        $status => [
            'description' => 'Success envelope. Money fields inside `data` are integer minor units (3299 = $32.99).',
            'headers' => [
                'X-Request-ID' => [
                    'description' => 'Echo of request id',
                    'schema' => ['type' => 'string'],
                ],
            ],
            'content' => $json($successSchema, $successExample),
        ],
        '401' => [
            'description' => 'Unauthenticated',
            'content' => $json($errorSchema, $errorExample('UNAUTHENTICATED', 'Authentication is required.', 401)),
        ],
        '422' => [
            'description' => 'Validation error',
            'content' => $json($errorSchema, [
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'The given data was invalid.',
                    'fields' => [
                        'email' => ['The email field is required.'],
                    ],
                ],
                'requestId' => 'ios-req-001',
            ]),
        ],
        '404' => [
            'description' => 'Not found',
            'content' => $json($errorSchema, $errorExample('NOT_FOUND', 'The requested resource was not found.', 404)),
        ],
        '429' => [
            'description' => 'Rate limited',
            'content' => $json($errorSchema, $errorExample('RATE_LIMITED', 'Too many requests.', 429)),
        ],
        '500' => [
            'description' => 'Server error',
            'content' => $json($errorSchema, $errorExample('INTERNAL_ERROR', 'An unexpected error occurred.', 500)),
        ],
    ];

    foreach ($cfg['extra'] ?? [] as $code => $extra) {
        $responses[(string) $code] = $extra;
    }

    return $responses;
};

$ref = static fn (string $name): array => ['$ref' => '#/components/schemas/'.$name];

$responseCatalog = [
    'GET /api/v1/health' => [
        'data' => $ref('HealthData'),
        'example' => [
            'service' => 'BluLavelle API',
            'version' => '1.1.1',
            'authMode' => 'bearer',
        ],
    ],
    'GET /api/v1/auth/csrf' => [
        'data' => $ref('CsrfData'),
        'example' => [
            'authMode' => 'bearer',
            'csrfRequired' => false,
            'csrfToken' => null,
            'tokenType' => 'Bearer',
            'tokenTtlSeconds' => 2592000,
            'notes' => 'iOS uses Authorization: Bearer.',
        ],
    ],
    'GET /api/v1/auth/captcha' => [
        'data' => $ref('CaptchaData'),
        'example' => ['required' => false, 'siteKey' => null, 'provider' => 'recaptcha'],
    ],
    'POST /api/v1/auth/register' => [
        'status' => 201,
        'data' => $ref('AuthTokenData'),
        'example' => ['token' => $tokenExample, 'user' => $userExample],
    ],
    'POST /api/v1/auth/login' => [
        'data' => $ref('AuthTokenData'),
        'example' => ['token' => $tokenExample, 'user' => $userExample],
    ],
    'POST /api/v1/auth/forgot-password' => [
        'data' => [
            'type' => 'object',
            'properties' => [
                'sent' => ['type' => 'boolean'],
                'message' => ['type' => 'string'],
            ],
        ],
        'example' => ['sent' => true, 'message' => 'If an account exists for that email, a reset link has been sent.'],
    ],
    'POST /api/v1/auth/reset-password' => [
        'data' => ['type' => 'object', 'properties' => ['reset' => ['type' => 'boolean']]],
        'example' => ['reset' => true],
    ],
    'GET /api/v1/auth/user' => [
        'data' => ['type' => 'object', 'properties' => ['user' => $ref('User')]],
        'example' => ['user' => $userExample],
    ],
    'POST /api/v1/auth/refresh' => [
        'data' => $ref('AuthTokenData'),
        'example' => ['token' => $tokenExample, 'user' => $userExample],
    ],
    'POST /api/v1/auth/revoke' => [
        'data' => ['type' => 'object', 'properties' => ['revoked' => ['type' => 'boolean']]],
        'example' => ['revoked' => true],
    ],
    'POST /api/v1/auth/change-password' => [
        'data' => ['type' => 'object', 'properties' => ['changed' => ['type' => 'boolean']]],
        'example' => ['changed' => true],
    ],
    'GET /api/v1/auth/sessions' => [
        'data' => ['type' => 'array', 'items' => $ref('Session')],
        'example' => [[
            'sessionId' => 42,
            'deviceName' => 'iPhone 16',
            'lastUsedAt' => '2026-09-07T08:00:00Z',
            'isCurrent' => true,
        ]],
    ],
    'DELETE /api/v1/auth/sessions/{sessionId}' => [
        'data' => ['type' => 'object', 'properties' => ['deleted' => ['type' => 'boolean']]],
        'example' => ['deleted' => true],
    ],
    'GET /api/v1/profile' => [
        'data' => ['type' => 'object', 'properties' => ['user' => $ref('User')]],
        'example' => ['user' => $userExample],
    ],
    'PATCH /api/v1/profile' => [
        'data' => ['type' => 'object', 'properties' => ['user' => $ref('User')]],
        'example' => ['user' => $userExample],
    ],
    'GET /api/v1/account/export' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['user' => $userExample, 'addresses' => [$addressExample], 'orders' => []],
    ],
    'DELETE /api/v1/account' => [
        'data' => ['type' => 'object', 'properties' => ['deleted' => ['type' => 'boolean']]],
        'example' => ['deleted' => true],
    ],
    'GET /api/v1/addresses' => [
        'data' => ['type' => 'array', 'items' => $ref('Address')],
        'example' => [$addressExample],
        'metaExample' => ['count' => 1],
    ],
    'POST /api/v1/addresses' => [
        'status' => 201,
        'data' => $ref('Address'),
        'example' => $addressExample,
    ],
    'GET /api/v1/addresses/{addressId}' => [
        'data' => $ref('Address'),
        'example' => $addressExample,
    ],
    'PATCH /api/v1/addresses/{addressId}' => [
        'data' => $ref('Address'),
        'example' => $addressExample,
    ],
    'DELETE /api/v1/addresses/{addressId}' => [
        'data' => ['type' => 'object', 'properties' => ['deleted' => ['type' => 'boolean']]],
        'example' => ['deleted' => true],
    ],
    'PUT /api/v1/addresses/{addressId}/default' => [
        'data' => $ref('Address'),
        'example' => $addressExample,
    ],
    'GET /api/v1/catalog/products' => [
        'data' => $ref('ProductListData'),
        'example' => $productListExample,
    ],
    'GET /api/v1/catalog/products/{id}' => [
        'data' => $ref('ProductDetail'),
        'example' => $productListExample['items'][0] + ['description' => 'Press-on nail set', 'variants' => []],
    ],
    'GET /api/v1/catalog/products/slug/{slug}' => [
        'data' => $ref('ProductDetail'),
        'example' => $productListExample['items'][0] + ['description' => 'Press-on nail set', 'variants' => []],
    ],
    'GET /api/v1/catalog/collections' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['items' => [['id' => 1, 'name' => 'Summer', 'slug' => 'summer']], 'meta' => ['page' => 1]],
    ],
    'GET /api/v1/catalog/shops' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['items' => [['id' => 1, 'name' => 'BluLavelle', 'slug' => 'blulavelle']], 'meta' => ['page' => 1]],
    ],
    'GET /api/v1/catalog/templates' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['items' => [['id' => 1, 'name' => 'Almond Soft']], 'meta' => ['page' => 1]],
    ],
    'GET /api/v1/catalog/templates/{id}' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['id' => 1, 'name' => 'Almond Soft'],
    ],
    'GET /api/v1/catalog/search/suggestions' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['suggestions' => ['almond', 'coffin pink']],
    ],
    'GET /api/v1/cart' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
    ],
    'POST /api/v1/cart' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
        'metaExample' => ['message' => 'Item added to cart'],
    ],
    'DELETE /api/v1/cart' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['cleared' => true],
        'metaExample' => ['message' => 'Cart cleared'],
    ],
    'PUT /api/v1/cart/{itemId}' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
    ],
    'DELETE /api/v1/cart/{itemId}' => [
        'data' => $ref('CartData'),
        'example' => array_merge($cartExample, ['cart_items' => [], 'total_items' => 0]),
    ],
    'GET /api/v1/cart/checkout' => [
        'data' => $ref('CheckoutSnapshot'),
        'example' => array_merge($cartExample, [
            'shipping_details' => ['country' => 'US', 'total_shipping' => 599],
        ]),
    ],
    'POST /api/v1/cart/promo' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
        'metaExample' => ['message' => 'Promo applied'],
    ],
    'DELETE /api/v1/cart/promo' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
    ],
    'POST /api/v1/cart/gift-card' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
    ],
    'DELETE /api/v1/cart/gift-card' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
    ],
    'POST /api/v1/cart/discount-mode' => [
        'data' => ['type' => 'object', 'properties' => ['mode' => ['type' => 'string', 'enum' => ['volume', 'promo']]]],
        'example' => ['mode' => 'promo'],
    ],
    'POST /api/v1/cart/sync' => [
        'data' => $ref('CartData'),
        'example' => $cartExample,
    ],
    'GET /api/v1/wishlist' => [
        'data' => $ref('WishlistListData'),
        'example' => [
            'items' => [['id' => 1, 'productId' => 10, 'product' => ['id' => 10, 'name' => 'Classic Set', 'price' => 2499]]],
            'count' => 1,
            'meta' => ['page' => 1, 'perPage' => 24],
        ],
    ],
    'POST /api/v1/wishlist' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['count' => 1],
        'metaExample' => ['message' => 'Added to wishlist'],
    ],
    'DELETE /api/v1/wishlist' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['count' => 0],
        'metaExample' => ['message' => 'Removed from wishlist'],
    ],
    'POST /api/v1/wishlist/toggle' => [
        'data' => ['type' => 'object', 'properties' => [
            'action' => ['type' => 'string', 'enum' => ['added', 'removed']],
            'count' => ['type' => 'integer'],
        ]],
        'example' => ['action' => 'added', 'count' => 1],
    ],
    'GET /api/v1/wishlist/count' => [
        'data' => ['type' => 'object', 'properties' => ['count' => ['type' => 'integer']]],
        'example' => ['count' => 3],
    ],
    'POST /api/v1/wishlist/check' => [
        'data' => ['type' => 'object', 'properties' => [
            'productIds' => ['type' => 'array', 'items' => ['type' => 'integer']],
        ]],
        'example' => ['productIds' => [10, 12]],
    ],
    'DELETE /api/v1/wishlist/clear' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['count' => 0],
        'metaExample' => ['message' => 'Wishlist cleared'],
    ],
    'GET /api/v1/orders' => [
        'data' => $ref('OrderListData'),
        'example' => [
            'items' => [[
                'id' => 99,
                'orderNumber' => 'BL-100234',
                'status' => 'processing',
                'paymentStatus' => 'paid',
                'totalAmount' => 3098,
                'currency' => 'USD',
                'itemsCount' => 1,
                'createdAt' => '2026-09-07T08:00:00Z',
            ]],
            'meta' => ['page' => 1, 'perPage' => 15],
        ],
    ],
    'POST /api/v1/orders/track' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => [
            'order' => [
                'orderNumber' => 'BL-100234',
                'status' => 'shipped',
                'trackingNumber' => '1Z999',
                'totalAmount' => 3098,
            ],
        ],
    ],
    'GET /api/v1/orders/{orderNumber}' => [
        'data' => $ref('OrderDetail'),
        'example' => [
            'orderNumber' => 'BL-100234',
            'status' => 'processing',
            'paymentStatus' => 'paid',
            'totalAmount' => 3098,
            'currency' => 'USD',
            'items' => [['productId' => 10, 'name' => 'Classic Set', 'quantity' => 1, 'price' => 2499]],
        ],
    ],
    'POST /api/v1/orders/{orderNumber}/cancel' => [
        'data' => ['type' => 'object', 'properties' => [
            'orderNumber' => ['type' => 'string'],
            'status' => ['type' => 'string'],
        ]],
        'example' => ['orderNumber' => 'BL-100234', 'status' => 'cancelled'],
        'metaExample' => ['message' => 'Order has been cancelled successfully.'],
    ],
    'POST /api/v1/orders/{orderNumber}/return-request' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['returnRequestId' => 7, 'status' => 'pending'],
    ],
    'POST /api/v1/checkout/shipping/calculate' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['shipping' => 599, 'currency' => 'USD', 'zone' => 'United States'],
    ],
    'POST /api/v1/checkout/shipping/rates' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['rates' => [['id' => 'standard', 'label' => 'Standard', 'amount' => 599]]],
    ],
    'POST /api/v1/checkout/attempts' => [
        'data' => $ref('CheckoutAttempt'),
        'example' => $checkoutAttemptExample,
        'metaExample' => ['replayed' => false],
    ],
    'GET /api/v1/checkout/attempts/{attemptId}' => [
        'data' => $ref('CheckoutAttempt'),
        'example' => $checkoutAttemptExample,
    ],
    'POST /api/v1/checkout/attempts/{attemptId}/confirm' => [
        'data' => $ref('CheckoutAttempt'),
        'example' => array_merge($checkoutAttemptExample, ['status' => 'succeeded', 'orderNumber' => 'BL-100234']),
    ],
    'POST /api/v1/checkout/attempts/{attemptId}/retry' => [
        'data' => $ref('CheckoutAttempt'),
        'example' => $checkoutAttemptExample,
    ],
    'POST /api/v1/payments/stripe/intent' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => [
            'clientSecret' => 'pi_3Abc_secret_xyz',
            'paymentIntentId' => 'pi_3Abc',
            'amount' => 3098,
            'currency' => 'usd',
        ],
    ],
    'GET /api/v1/nail/products/{productId}/try-on-config' => [
        'data' => $ref('TryOnConfig'),
        'example' => $tryOnConfigExample,
        'metaExample' => ['api' => 'nail.try-on-config', 'version' => '1.2.0'],
        'extra' => [
            '503' => [
                'description' => 'Feature disabled / provider not configured',
                'content' => [
                    'application/json' => [
                        'schema' => ['$ref' => '#/components/schemas/ErrorEnvelope'],
                        'example' => [
                            'success' => false,
                            'error' => [
                                'code' => 'FEATURE_DISABLED',
                                'message' => 'Virtual nail try-on is not available.',
                                'fields' => new stdClass(),
                            ],
                            'requestId' => 'ios-req-001',
                        ],
                    ],
                ],
            ],
        ],
    ],
    'POST /api/v1/uploads/presign' => [
        'status' => 201,
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => [
            'uploadId' => 'asset_hand_01hxyz',
            'uploadUrl' => 'https://example.test/api/v1/uploads/asset_hand_01hxyz/content?signature=...',
            'method' => 'PUT',
            'headers' => ['Content-Type' => 'image/jpeg', 'X-Upload-Token' => '…'],
            'expiresAt' => '2026-09-08T03:30:00Z',
        ],
    ],
    'POST /api/v1/uploads/{uploadId}/complete' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => [
            'uploadId' => 'asset_hand_01hxyz',
            'handImageAssetId' => 'asset_hand_01hxyz',
            'status' => 'ready',
            'mimeType' => 'image/jpeg',
            'size' => 2400000,
        ],
    ],
    'DELETE /api/v1/uploads/{uploadId}' => [
        'data' => ['type' => 'object', 'properties' => ['deleted' => ['type' => 'boolean']]],
        'example' => ['deleted' => true, 'uploadId' => 'asset_hand_01hxyz'],
    ],
    'GET /api/v1/nail/try-ons' => [
        'data' => ['type' => 'object', 'properties' => [
            'items' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/NailTryOnJob']],
        ]],
        'example' => ['items' => [[
            'tryOnId' => 'tryon_01hxyz',
            'status' => 'succeeded',
            'productId' => 456,
            'outputAssetId' => 'asset_out_01hxyz',
        ]]],
        'metaExample' => ['page' => 1, 'perPage' => 20, 'total' => 1, 'hasNextPage' => false],
    ],
    'POST /api/v1/nail/try-ons' => [
        'status' => 202,
        'data' => $ref('NailTryOnJob'),
        'example' => [
            'tryOnId' => 'tryon_01hxyz',
            'status' => 'queued',
            'handImageAssetId' => 'asset_hand_123',
            'productId' => 456,
            'variantId' => 789,
            'tryOnAssetVersion' => 'tryon_456_v3',
            'createdAt' => '2026-09-08T03:00:00Z',
            'expiresAt' => '2026-09-11T03:00:00Z',
        ],
        'metaExample' => ['replayed' => false],
    ],
    'GET /api/v1/nail/try-ons/{tryOnId}' => [
        'data' => $ref('NailTryOnJob'),
        'example' => [
            'tryOnId' => 'tryon_01hxyz',
            'status' => 'succeeded',
            'handImageAssetId' => 'asset_hand_123',
            'productId' => 456,
            'variantId' => 789,
            'tryOnAssetVersion' => 'tryon_456_v3',
            'outputAssetId' => 'asset_out_01hxyz',
            'resultImageUrl' => 'https://example.test/api/v1/uploads/asset_out_01hxyz/download?signature=…',
            'resultUrlExpiresInSeconds' => 900,
            'createdAt' => '2026-09-08T03:00:00Z',
            'completedAt' => '2026-09-08T03:00:12Z',
            'expiresAt' => '2026-09-11T03:00:00Z',
        ],
    ],
    'DELETE /api/v1/nail/try-ons/{tryOnId}' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['deleted' => true, 'tryOnId' => 'tryon_01hxyz'],
    ],
    'POST /api/v1/nail/try-ons/{tryOnId}/variants' => [
        'status' => 202,
        'data' => $ref('NailTryOnJob'),
        'example' => [
            'tryOnId' => 'tryon_01habc',
            'status' => 'queued',
            'parentTryOnId' => 'tryon_01hxyz',
            'productId' => 999,
        ],
        'metaExample' => ['parentTryOnId' => 'tryon_01hxyz', 'replayed' => false],
    ],
    'POST /api/v1/nail/try-ons/{tryOnId}/retry' => [
        'status' => 202,
        'data' => $ref('NailTryOnJob'),
        'example' => ['tryOnId' => 'tryon_01hretry', 'status' => 'queued'],
        'metaExample' => ['replayed' => false],
    ],
    'GET /api/v1/virtual-nail/status' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['enabled' => true, 'provider' => 'image_api'],
    ],
    'GET /api/v1/virtual-nail/products' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => $productListExample,
    ],
    'GET /api/v1/virtual-nail/products/suggestions' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['suggestions' => [['id' => 10, 'name' => 'Classic Set']]],
    ],
    'GET /api/v1/virtual-nail/picker-meta' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['shapes' => ['almond', 'coffin'], 'lengths' => ['short', 'medium', 'long']],
    ],
    'GET /api/v1/virtual-nail/products/{productId}/options' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => [
            'product' => ['id' => 10, 'name' => 'Classic Set'],
            'shapes' => ['almond'],
            'lengths' => ['medium'],
        ],
    ],
    'POST /api/v1/virtual-nail/try' => [
        'data' => $ref('TryOnJob'),
        'example' => [
            'uuid' => '9f3c2a1e-1111-2222-3333-444455556666',
            'status' => 'pending',
            'productId' => 10,
        ],
    ],
    'GET /api/v1/virtual-nail/history' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['items' => [['uuid' => '9f3c2a1e-1111-2222-3333-444455556666', 'status' => 'completed']]],
    ],
    'GET /api/v1/virtual-nail/pending' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['items' => [['uuid' => '9f3c2a1e-1111-2222-3333-444455556666', 'status' => 'pending']]],
    ],
    'GET /api/v1/virtual-nail/trials/{uuid}/status' => [
        'data' => $ref('TryOnJob'),
        'example' => ['uuid' => '9f3c2a1e-1111-2222-3333-444455556666', 'status' => 'completed', 'productId' => 10],
    ],
    'GET /api/v1/virtual-nail/trials/{uuid}/result' => [
        'data' => $ref('TryOnResult'),
        'example' => [
            'uuid' => '9f3c2a1e-1111-2222-3333-444455556666',
            'status' => 'completed',
            'resultImageUrl' => 'https://cdn.example.com/try-on/result.webp',
        ],
    ],
    'GET /api/v1/virtual-nail/trials/{uuid}/hand' => [
        'data' => ['type' => 'object', 'additionalProperties' => true],
        'example' => ['url' => 'https://cdn.example.com/try-on/hand.webp'],
    ],
];

foreach ($paths as $route => &$methods) {
    foreach ($methods as $method => &$op) {
        $key = strtoupper($method).' '.$route;
        $cfg = $responseCatalog[$key] ?? [
            'data' => ['type' => 'object', 'additionalProperties' => true],
            'example' => new stdClass(),
        ];
        $op['responses'] = $buildResponses($cfg);
    }
}
unset($methods, $op);

$componentSchemas = [
    'ErrorEnvelope' => [
        'type' => 'object',
        'required' => ['success', 'error', 'requestId'],
        'properties' => [
            'success' => ['type' => 'boolean', 'enum' => [false]],
            'error' => [
                'type' => 'object',
                'required' => ['code', 'message', 'fields'],
                'properties' => [
                    'code' => ['type' => 'string', 'example' => 'VALIDATION_ERROR'],
                    'message' => ['type' => 'string'],
                    'fields' => [
                        'type' => 'object',
                        'additionalProperties' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                        ],
                        'description' => 'Field errors keyed by input name (empty object when none)',
                    ],
                ],
            ],
            'requestId' => ['type' => 'string'],
        ],
    ],
    'HealthData' => [
        'type' => 'object',
        'properties' => [
            'service' => ['type' => 'string'],
            'version' => ['type' => 'string'],
            'authMode' => ['type' => 'string', 'example' => 'bearer'],
        ],
    ],
    'CsrfData' => [
        'type' => 'object',
        'properties' => [
            'authMode' => ['type' => 'string'],
            'csrfRequired' => ['type' => 'boolean', 'example' => false],
            'csrfToken' => ['type' => 'string', 'nullable' => true],
            'tokenType' => ['type' => 'string', 'example' => 'Bearer'],
            'tokenTtlSeconds' => ['type' => 'integer'],
            'notes' => ['type' => 'string'],
        ],
    ],
    'CaptchaData' => [
        'type' => 'object',
        'properties' => [
            'required' => ['type' => 'boolean'],
            'siteKey' => ['type' => 'string', 'nullable' => true],
            'provider' => ['type' => 'string', 'example' => 'recaptcha'],
        ],
    ],
    'User' => [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'email' => ['type' => 'string', 'format' => 'email'],
            'phone' => ['type' => 'string', 'nullable' => true],
            'avatar' => ['type' => 'string', 'nullable' => true],
            'emailVerified' => ['type' => 'boolean'],
            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
            'updatedAt' => ['type' => 'string', 'format' => 'date-time'],
        ],
    ],
    'Token' => [
        'type' => 'object',
        'properties' => [
            'token' => ['type' => 'string'],
            'tokenType' => ['type' => 'string', 'example' => 'Bearer'],
            'expiresAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            'sessionId' => ['type' => 'string'],
            'deviceName' => ['type' => 'string'],
        ],
    ],
    'AuthTokenData' => [
        'type' => 'object',
        'properties' => [
            'token' => ['$ref' => '#/components/schemas/Token'],
            'user' => ['$ref' => '#/components/schemas/User'],
        ],
    ],
    'Session' => [
        'type' => 'object',
        'properties' => [
            'sessionId' => ['type' => 'integer'],
            'deviceName' => ['type' => 'string'],
            'lastUsedAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            'isCurrent' => ['type' => 'boolean'],
        ],
    ],
    'Address' => [
        'type' => 'object',
        'properties' => [
            'addressId' => ['type' => 'integer'],
            'recipientName' => ['type' => 'string'],
            'phone' => ['type' => 'string', 'nullable' => true],
            'line1' => ['type' => 'string'],
            'line2' => ['type' => 'string', 'nullable' => true],
            'city' => ['type' => 'string'],
            'stateProvince' => ['type' => 'string', 'nullable' => true],
            'postalCode' => ['type' => 'string'],
            'countryCode' => ['type' => 'string'],
            'label' => ['type' => 'string', 'nullable' => true],
            'isDefaultShipping' => ['type' => 'boolean'],
            'isDefaultBilling' => ['type' => 'boolean'],
            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
            'updatedAt' => ['type' => 'string', 'format' => 'date-time'],
        ],
    ],
    'MoneyMinor' => [
        'type' => 'integer',
        'description' => 'Amount in minor units (cents). 3299 = $32.99',
        'example' => 2499,
    ],
    'ProductSummary' => [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
            'name' => ['type' => 'string'],
            'slug' => ['type' => 'string'],
            'price' => ['$ref' => '#/components/schemas/MoneyMinor'],
            'sale_price' => ['type' => 'integer', 'nullable' => true],
            'primary_image' => ['type' => 'string', 'nullable' => true],
            'in_stock' => ['type' => 'boolean'],
        ],
    ],
    'ProductDetail' => [
        'allOf' => [
            ['$ref' => '#/components/schemas/ProductSummary'],
            [
                'type' => 'object',
                'properties' => [
                    'description' => ['type' => 'string', 'nullable' => true],
                    'variants' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
                ],
            ],
        ],
    ],
    'ProductListData' => [
        'type' => 'object',
        'properties' => [
            'items' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ProductSummary']],
            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
        ],
    ],
    'PaginationMeta' => [
        'type' => 'object',
        'properties' => [
            'page' => ['type' => 'integer'],
            'perPage' => ['type' => 'integer'],
            'total' => ['type' => 'integer'],
            'hasNextPage' => ['type' => 'boolean'],
        ],
    ],
    'CartData' => [
        'type' => 'object',
        'properties' => [
            'cart_items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
            'total_items' => ['type' => 'integer'],
            'total_price' => ['$ref' => '#/components/schemas/MoneyMinor'],
            'summary' => [
                'type' => 'object',
                'properties' => [
                    'subtotal' => ['$ref' => '#/components/schemas/MoneyMinor'],
                    'shipping' => ['$ref' => '#/components/schemas/MoneyMinor'],
                    'discount' => ['$ref' => '#/components/schemas/MoneyMinor'],
                    'gift_card_discount' => ['$ref' => '#/components/schemas/MoneyMinor'],
                    'total' => ['$ref' => '#/components/schemas/MoneyMinor'],
                ],
            ],
            'currency' => ['type' => 'string', 'example' => 'USD'],
        ],
    ],
    'CheckoutSnapshot' => [
        'allOf' => [
            ['$ref' => '#/components/schemas/CartData'],
            [
                'type' => 'object',
                'properties' => [
                    'shipping_details' => ['type' => 'object', 'additionalProperties' => true],
                ],
            ],
        ],
    ],
    'WishlistListData' => [
        'type' => 'object',
        'properties' => [
            'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
            'count' => ['type' => 'integer'],
            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
        ],
    ],
    'OrderListData' => [
        'type' => 'object',
        'properties' => [
            'items' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/OrderSummary']],
            'meta' => ['$ref' => '#/components/schemas/PaginationMeta'],
        ],
    ],
    'OrderSummary' => [
        'type' => 'object',
        'properties' => [
            'id' => ['type' => 'integer'],
            'orderNumber' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'paymentStatus' => ['type' => 'string'],
            'totalAmount' => ['$ref' => '#/components/schemas/MoneyMinor'],
            'currency' => ['type' => 'string'],
            'itemsCount' => ['type' => 'integer'],
            'createdAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
        ],
    ],
    'OrderDetail' => [
        'type' => 'object',
        'additionalProperties' => true,
        'properties' => [
            'orderNumber' => ['type' => 'string'],
            'status' => ['type' => 'string'],
            'paymentStatus' => ['type' => 'string'],
            'totalAmount' => ['$ref' => '#/components/schemas/MoneyMinor'],
            'currency' => ['type' => 'string'],
            'items' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
        ],
    ],
    'CheckoutAttempt' => [
        'type' => 'object',
        'properties' => [
            'attemptId' => ['type' => 'string'],
            'status' => ['type' => 'string', 'example' => 'requires_payment'],
            'orderNumber' => ['type' => 'string', 'nullable' => true],
            'clientSecret' => ['type' => 'string', 'nullable' => true],
            'paymentIntentId' => ['type' => 'string', 'nullable' => true],
            'amount' => ['$ref' => '#/components/schemas/MoneyMinor'],
            'currency' => ['type' => 'string', 'example' => 'usd'],
        ],
    ],
    'TryOnConfig' => [
        'type' => 'object',
        'properties' => [
            'productId' => ['type' => 'integer'],
            'variantId' => ['type' => 'integer', 'nullable' => true],
            'tryOnEnabled' => ['type' => 'boolean'],
            'tryOnAssetVersion' => ['type' => 'string', 'nullable' => true],
            'supportedVariants' => ['type' => 'array', 'items' => ['type' => 'object', 'additionalProperties' => true]],
            'assets' => ['type' => 'object', 'additionalProperties' => true],
            'unsupportedReason' => ['type' => 'string', 'nullable' => true],
            'unsupportedMessage' => ['type' => 'string', 'nullable' => true],
        ],
    ],
    'TryOnJob' => [
        'type' => 'object',
        'properties' => [
            'uuid' => ['type' => 'string', 'format' => 'uuid'],
            'status' => ['type' => 'string', 'enum' => ['pending', 'processing', 'completed', 'failed']],
            'productId' => ['type' => 'integer'],
        ],
    ],
    'TryOnResult' => [
        'type' => 'object',
        'properties' => [
            'uuid' => ['type' => 'string', 'format' => 'uuid'],
            'status' => ['type' => 'string'],
            'resultImageUrl' => ['type' => 'string', 'nullable' => true],
        ],
    ],
    'NailTryOnJob' => [
        'type' => 'object',
        'properties' => [
            'tryOnId' => ['type' => 'string'],
            'status' => [
                'type' => 'string',
                'enum' => ['queued', 'validating_image', 'detecting_hand', 'rendering', 'succeeded', 'failed', 'expired', 'deleted'],
            ],
            'handImageAssetId' => ['type' => 'string', 'nullable' => true],
            'productId' => ['type' => 'integer'],
            'variantId' => ['type' => 'integer', 'nullable' => true],
            'tryOnAssetVersion' => ['type' => 'string', 'nullable' => true],
            'outputAssetId' => ['type' => 'string', 'nullable' => true],
            'resultImageUrl' => ['type' => 'string', 'nullable' => true, 'description' => 'Short-lived signed URL'],
            'resultUrlExpiresInSeconds' => ['type' => 'integer', 'nullable' => true],
            'errorCode' => [
                'type' => 'string',
                'nullable' => true,
                'description' => 'HAND_NOT_FOUND | MULTIPLE_HANDS_FOUND | NAILS_NOT_VISIBLE | IMAGE_TOO_DARK | IMAGE_TOO_BLURRY | HAND_PARTIALLY_OUTSIDE_FRAME | UNSUPPORTED_IMAGE | TRY_ON_NOT_AVAILABLE_FOR_PRODUCT | AI_PROCESSING_FAILED | RATE_LIMITED | ASSET_EXPIRED | ASSET_NOT_OWNED',
            ],
            'errorMessage' => ['type' => 'string', 'nullable' => true],
            'createdAt' => ['type' => 'string', 'format' => 'date-time'],
            'completedAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
            'expiresAt' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
        ],
    ],
];

$spec = [
    'openapi' => '3.0.0',
    'info' => [
        'title' => 'BluLavelle API v1',
        'version' => '1.2.0',
        'description' => implode("\n\n", [
            'Native iOS API for BluLavelle.',
            '**Auth:** `Authorization: Bearer` (Sanctum). Do not scrape HTML for CSRF.',
            '**Envelope:** `{ success, data, meta? }` or `{ success:false, error:{code,message,fields}, requestId }`.',
            '**Money:** integer minor units in `data` (3299 = $32.99).',
            '**Guest cart/wishlist:** send/store `X-Guest-Cart-Token`.',
            '**Checkout:** `POST /api/v1/checkout/attempts` + `Idempotency-Key`.',
            'Each operation documents parameters, request body, and response schemas/examples.',
        ]),
    ],
    'servers' => [
        ['url' => 'ORIGIN_PLACEHOLDER', 'description' => 'Current server'],
    ],
    'tags' => array_map(
        static fn (string $name) => ['name' => $name],
        ['Health', 'Auth', 'Profile', 'Account', 'Addresses', 'Catalog', 'Cart', 'Wishlist', 'Orders', 'Checkout', 'Payments', 'Nail Try-On', 'Virtual Nail']
    ),
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'bearerFormat' => 'Sanctum',
                'description' => 'Send `Authorization: Bearer {token}` from login/register/refresh.',
            ],
        ],
        'parameters' => [
            'XRequestId' => [
                'name' => 'X-Request-ID',
                'in' => 'header',
                'required' => false,
                'schema' => ['type' => 'string'],
            ],
            'XGuestCartToken' => [
                'name' => 'X-Guest-Cart-Token',
                'in' => 'header',
                'required' => false,
                'schema' => ['type' => 'string'],
            ],
        ],
        'schemas' => $componentSchemas,
    ],
    'paths' => $paths,
];

$json = json_encode($spec, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$json = str_replace('"ORIGIN_PLACEHOLDER"', 'window.location.origin', $json);

$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BluLavelle API v1</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    <style>
        body { margin: 0; }
        .topbar { display: none; }
        .api-doc-intro {
            font-family: system-ui, sans-serif;
            padding: 1rem 1.25rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .api-doc-intro code { background: #e2e8f0; padding: 0.1rem 0.35rem; border-radius: 4px; }
        .api-doc-intro a { color: #0f766e; }
    </style>
</head>
<body>
    <div class="api-doc-intro">
        <strong>BluLavelle API v1</strong> for iOS — full storefront surface under <code>/api/v1</code>.
        Auth: <code>Authorization: Bearer</code>. Guest: <code>X-Guest-Cart-Token</code>.
        Money in <code>data</code> = integer minor units. Checkout: <code>Idempotency-Key</code>.
        Open an operation to see <strong>Parameters</strong>, <strong>Request body</strong>, and <strong>Responses</strong> (schema + example).
        <a href="/api-docs.html">Storefront (web) API docs</a>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function () {
            const spec = {$json};
            SwaggerUIBundle({
                spec: spec,
                dom_id: '#swagger-ui',
                presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
                layout: 'StandaloneLayout',
                deepLinking: true,
                tryItOutEnabled: true,
                defaultModelsExpandDepth: 1,
                defaultModelExpandDepth: 2,
            });
        };
    </script>
</body>
</html>
HTML;

$target = dirname(__DIR__).'/public/api-v1-docs.html';
file_put_contents($target, $html);
echo "Wrote {$target} (".strlen($html)." bytes)\n";

$withExample = 0;
foreach ($paths as $methods) {
    foreach ($methods as $op) {
        if (isset($op['responses']['200']['content']['application/json']['example'])
            || isset($op['responses']['201']['content']['application/json']['example'])) {
            $withExample++;
        }
    }
}
echo "Operations with response examples: {$withExample}\n";
