<?php

namespace App\Services;

use Google\Client;
use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\Product as GoogleProduct;
use Google\Service\ShoppingContent\Price;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\GmcConfig;

class GoogleMerchantCenterService
{
    private $client;
    private $merchantId;
    private $dataSourceId;
    private $targetCountry;
    private $currency;
    private $contentLanguage;
    private $shoppingContentService;
    private $credentialsPath;

    /**
     * Create instance with GmcConfig from database
     */
    public static function fromConfig(GmcConfig $config): self
    {
        $instance = new self();
        $instance->merchantId = $config->merchant_id;
        $instance->dataSourceId = $config->data_source_id;
        $instance->targetCountry = $config->target_country;
        $instance->currency = GmcConfig::getCurrencyForCountry($config->target_country);
        $instance->contentLanguage = $config->content_language;

        $credentialsPath = $config->credentials_path;
        $instance->credentialsPath = $credentialsPath;
        $instance->initializeClient($credentialsPath);

        return $instance;
    }

    public function __construct(?GmcConfig $config = null)
    {
        if ($config) {
            // Use provided config from database
            $this->merchantId = $config->merchant_id;
            $this->dataSourceId = $config->data_source_id;
            $this->targetCountry = $config->target_country;
            $this->currency = GmcConfig::getCurrencyForCountry($config->target_country);
            $this->contentLanguage = $config->content_language;
            $credentialsPath = $config->credentials_path;
        } else {
            // Use default config from .env (backward compatibility)
            $this->merchantId = config('services.google.merchant_id');
            $this->dataSourceId = config('services.google.data_source_id', 'PRODUCT_FEED_API');
            $this->targetCountry = config('services.google.target_country', 'GB');
            $this->currency = config('services.google.currency', 'GBP');
            $this->contentLanguage = config('services.google.content_language', 'en');
            $credentialsPath = config('services.google.merchant_credentials_path');
        }

        if (!$this->merchantId || !$credentialsPath) {
            throw new \Exception('Google Merchant Center credentials not configured. Please set GMC_MERCHANT_ID and GMC_CREDENTIALS_PATH in .env or use GmcConfig');
        }

        $this->credentialsPath = $credentialsPath;
        $this->initializeClient($credentialsPath);
    }

    /**
     * Initialize Google Client with credentials
     */
    private function initializeClient(string $credentialsPath): void
    {
        // Check if credentials file exists
        $resolvedPath = null;
        // Use storage_path('app') to get the correct storage/app directory
        $storageAppPath = storage_path('app');

        // Try multiple methods to find the file
        $possiblePaths = [
            // Direct path as provided (absolute)
            $credentialsPath,
            // Relative to storage/app using storage_path
            storage_path('app/' . $credentialsPath),
            // Just filename in storage/app
            $storageAppPath . DIRECTORY_SEPARATOR . basename($credentialsPath),
            // Remove 'app/' prefix if present
            $storageAppPath . DIRECTORY_SEPARATOR . str_replace('app/', '', $credentialsPath),
            // Remove 'app\' prefix if present (Windows)
            $storageAppPath . DIRECTORY_SEPARATOR . str_replace('app\\', '', $credentialsPath),
            // Try with Storage facade (in case it's configured differently)
            Storage::exists($credentialsPath) ? Storage::path($credentialsPath) : null,
        ];

        // Remove null values
        $possiblePaths = array_filter($possiblePaths);

        // Remove duplicates and check each path
        $possiblePaths = array_unique($possiblePaths);

        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_file($path)) {
                $resolvedPath = $path;
                break;
            }
        }

        if (!$resolvedPath) {
            // Log all attempted paths for debugging
            Log::error('GMC Credentials file not found', [
                'original_path' => $credentialsPath,
                'storage_app_path' => $storageAppPath,
                'attempted_paths' => $possiblePaths,
                'files_in_storage_app' => array_slice(scandir($storageAppPath), 2) // Skip . and ..
            ]);

            throw new \Exception("Google Merchant Center credentials file not found: {$credentialsPath}. Please check GMC_CREDENTIALS_PATH in .env file or GmcConfig. Storage app path: {$storageAppPath}");
        }

        $credentialsPath = $resolvedPath;

        try {
            $this->client = new Client();
            $this->client->setAuthConfig($credentialsPath);
            $this->client->addScope(ShoppingContent::CONTENT);
            $this->client->setAccessType('offline');

            // Initialize ShoppingContent service
            $this->shoppingContentService = new ShoppingContent($this->client);
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Merchant Center client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to initialize Google Merchant Center API: ' . $e->getMessage());
        }
    }

    /**
     * Insert or update a product in Google Merchant Center using ShoppingContent API
     */
    public function insertProduct($productData): array
    {
        try {
            // Create Google Product object using ShoppingContent service
            $googleProduct = new GoogleProduct();
            $googleProduct->setOfferId($productData['offer_id']);
            $googleProduct->setTitle($productData['title']);
            $googleProduct->setDescription($productData['description']);
            $googleProduct->setLink($productData['link']);
            $googleProduct->setImageLink($productData['image_link']);
            $googleProduct->setContentLanguage($productData['content_language'] ?? $this->contentLanguage);
            $googleProduct->setTargetCountry($productData['target_country'] ?? $this->targetCountry);
            $googleProduct->setChannel('online');
            $googleProduct->setAvailability($productData['availability'] ?? 'in stock');

            // Set price
            $price = new Price();
            $price->setValue($productData['price']);
            $price->setCurrency($productData['currency'] ?? $this->currency);
            $googleProduct->setPrice($price);

            // Set condition
            if (isset($productData['condition'])) {
                $googleProduct->setCondition($productData['condition']);
            }

            // Set brand
            if (isset($productData['brand'])) {
                $googleProduct->setBrand($productData['brand']);
            }

            // Set Google Product Category
            if (isset($productData['google_product_category'])) {
                $googleProduct->setGoogleProductCategory($productData['google_product_category']);
            }

            // Set Product Type
            if (isset($productData['product_type'])) {
                $googleProduct->setProductTypes([$productData['product_type']]);
            }

            // Set MPN
            if (isset($productData['mpn'])) {
                $googleProduct->setMpn($productData['mpn']);
            }

            // Set additional images
            if (isset($productData['additional_image_links']) && is_array($productData['additional_image_links']) && !empty($productData['additional_image_links'])) {
                $googleProduct->setAdditionalImageLinks(array_values($productData['additional_image_links']));
            }

            // Set shipping - IMPORTANT: currency must match product currency
            if (isset($productData['shipping']) && is_array($productData['shipping'])) {
                $shippingArray = [];
                foreach ($productData['shipping'] as $shippingItem) {
                    $shippingEntry = [
                        'country' => $shippingItem['country']
                    ];

                    // Only add price if it exists (optional for some shipping configurations)
                    if (isset($shippingItem['price']) && is_array($shippingItem['price']) && isset($shippingItem['price']['value'])) {
                        $shippingPrice = new Price();
                        $shippingPrice->setValue($shippingItem['price']['value']);
                        // Ensure shipping currency matches product currency
                        $shippingPrice->setCurrency($productData['currency'] ?? $this->currency);
                        $shippingEntry['price'] = $shippingPrice;
                    }

                    $shippingArray[] = $shippingEntry;
                }
                $googleProduct->setShipping($shippingArray);
            }

            // Set age_group, color, gender (only for Clothing/Apparel products)
            // Only set these if they are provided in productData
            if (isset($productData['age_group'])) {
                $googleProduct->setAgeGroup($productData['age_group']);
            }

            if (isset($productData['color'])) {
                $googleProduct->setColor($productData['color']);
            }

            if (isset($productData['gender'])) {
                $googleProduct->setGender($productData['gender']);
            }

            // Set size_system and size_type (for Clothing/Apparel products)
            if (isset($productData['size_system'])) {
                $googleProduct->setSizeSystem($productData['size_system']);
            }

            if (isset($productData['size_type'])) {
                $googleProduct->setSizeType($productData['size_type']);
            }

            // Log the request
            Log::debug('GMC API Request', [
                'merchant_id' => $this->merchantId,
                'product_offer_id' => $productData['offer_id'],
                'method' => 'ShoppingContent::products->insert'
            ]);

            // Use ShoppingContent service to insert product
            $response = $this->shoppingContentService->products->insert($this->merchantId, $googleProduct);

            // Log successful upload
            Log::info('GMC Product uploaded successfully', [
                'offer_id' => $productData['offer_id'],
                'product_id' => $response->getId(),
                'title' => $productData['title'] ?? 'N/A',
                'merchant_id' => $this->merchantId,
                'gmc_response' => [
                    'id' => $response->getId(),
                    'offerId' => $response->getOfferId(),
                    'title' => $response->getTitle()
                ]
            ]);

            return [
                'success' => true,
                'product_id' => $response->getId(),
                'message' => 'Product successfully uploaded to Google Merchant Center'
            ];
        } catch (\Google\Service\Exception $e) {
            $errorMessage = $this->parseGoogleServiceError($e);
            Log::error('GMC API Error', [
                'error' => $errorMessage,
                'product_data' => $productData,
                'merchant_id' => $this->merchantId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'message' => 'Failed to upload product to Google Merchant Center'
            ];
        } catch (\Exception $e) {
            Log::error('GMC Upload Error', [
                'error' => $e->getMessage(),
                'product_data' => $productData,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'An error occurred while uploading to Google Merchant Center'
            ];
        }
    }

    /**
     * Batch insert products
     */
    public function batchInsertProducts(array $productsData): array
    {
        $results = [
            'success' => [],
            'failed' => [],
            'total' => count($productsData),
            'success_count' => 0,
            'failed_count' => 0
        ];

        foreach ($productsData as $index => $productData) {
            $result = $this->insertProduct($productData);

            if ($result['success']) {
                $results['success'][] = [
                    'index' => $index,
                    'offer_id' => $productData['offer_id'],
                    'product_id' => $result['product_id'] ?? null,
                    'message' => $result['message']
                ];
                $results['success_count']++;
            } else {
                $results['failed'][] = [
                    'index' => $index,
                    'offer_id' => $productData['offer_id'],
                    'error' => $result['error'] ?? 'Unknown error',
                    'message' => $result['message']
                ];
                $results['failed_count']++;
            }

            // Add small delay to avoid rate limiting
            if ($index < count($productsData) - 1) {
                usleep(100000); // 0.1 second delay
            }
        }

        // Log batch upload summary
        Log::info('GMC Batch upload completed', [
            'total' => $results['total'],
            'success_count' => $results['success_count'],
            'failed_count' => $results['failed_count'],
            'merchant_id' => $this->merchantId,
            'data_source_id' => $this->dataSourceId,
            'successful_products' => array_column($results['success'], 'offer_id'),
            'failed_products' => array_column($results['failed'], 'offer_id')
        ]);

        return $results;
    }

    /**
     * Delete a product from Google Merchant Center
     * Uses Content API format: channel:language:country:offerId
     * Example: online:en:GB:SKU12345
     */
    public function deleteProduct($offerId): array
    {
        // Build productId in the correct format: channel:language:country:offerId
        // Channel is always "online" for web products
        $channel = 'online';
        $language = strtolower($this->contentLanguage ?? 'en');
        $country = strtoupper($this->targetCountry ?? 'US');
        $productId = "{$channel}:{$language}:{$country}:{$offerId}";

        try {
            // Get service account email from credentials for logging
            $serviceAccountEmail = $this->getServiceAccountEmail();

            Log::info('GMC Delete Product - Building productId', [
                'offer_id' => $offerId,
                'channel' => $channel,
                'language' => $language,
                'country' => $country,
                'product_id' => $productId,
                'merchant_id' => $this->merchantId,
                'service_account_email' => $serviceAccountEmail
            ]);

            // Use ShoppingContent service to delete product with correct productId format
            // Format: online:en:GB:SKU12345
            $this->shoppingContentService->products->delete($this->merchantId, $productId);

            Log::info('GMC Delete Product - Success', [
                'offer_id' => $offerId,
                'product_id' => $productId,
                'merchant_id' => $this->merchantId
            ]);

            return [
                'success' => true,
                'message' => 'Product successfully deleted from Google Merchant Center',
                'product_id' => $productId,
                'offer_id' => $offerId
            ];
        } catch (\Google\Service\Exception $e) {
            $errorMessage = $this->parseGoogleServiceError($e);

            // Get service account email from credentials for better error message
            $serviceAccountEmail = $this->getServiceAccountEmail();

            Log::error('GMC Delete Error', [
                'error' => $errorMessage,
                'offer_id' => $offerId,
                'product_id' => $productId,
                'merchant_id' => $this->merchantId,
                'channel' => $channel,
                'language' => $language,
                'country' => $country,
                'google_error' => $e->getMessage(),
                'service_account_email' => $serviceAccountEmail,
                'troubleshooting' => 'If you see "account_access_denied", make sure the service account email is added to Google Merchant Center with Admin or Standard access.'
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'message' => 'Failed to delete product from Google Merchant Center'
            ];
        } catch (\Exception $e) {
            Log::error('GMC Delete Error', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId,
                'product_id' => $productId,
                'merchant_id' => $this->merchantId,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to delete product from Google Merchant Center',
                'product_id' => $productId,
                'offer_id' => $offerId
            ];
        }
    }

    /**
     * Get service account email from credentials file
     * Same approach as used in upload functions
     */
    private function getServiceAccountEmail(): ?string
    {
        if (!$this->credentialsPath) {
            return null;
        }

        try {
            // Resolve credentials path (same logic as initializeClient)
            $resolvedPath = null;
            $storageAppPath = storage_path('app');

            $possiblePaths = [
                $this->credentialsPath,
                storage_path('app/' . $this->credentialsPath),
                $storageAppPath . DIRECTORY_SEPARATOR . basename($this->credentialsPath),
                $storageAppPath . DIRECTORY_SEPARATOR . str_replace('app/', '', $this->credentialsPath),
                $storageAppPath . DIRECTORY_SEPARATOR . str_replace('app\\', '', $this->credentialsPath),
                Storage::exists($this->credentialsPath) ? Storage::path($this->credentialsPath) : null,
            ];

            $possiblePaths = array_filter(array_unique($possiblePaths));

            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_file($path)) {
                    $resolvedPath = $path;
                    break;
                }
            }

            if ($resolvedPath && file_exists($resolvedPath)) {
                $credentials = json_decode(file_get_contents($resolvedPath), true);
                return $credentials['client_email'] ?? null;
            }
        } catch (\Exception $e) {
            // Ignore if cannot read credentials
            Log::debug('Cannot read service account email from credentials', [
                'error' => $e->getMessage(),
                'credentials_path' => $this->credentialsPath
            ]);
        }

        return null;
    }

    /**
     * Get product from Google Merchant Center
     */
    public function getProduct($offerId): ?array
    {
        try {
            // Use ShoppingContent service to get product
            $product = $this->shoppingContentService->products->get($this->merchantId, $offerId);

            return [
                'success' => true,
                'product' => [
                    'id' => $product->getId(),
                    'offerId' => $product->getOfferId(),
                    'title' => $product->getTitle(),
                    'price' => $product->getPrice() ? [
                        'value' => $product->getPrice()->getValue(),
                        'currency' => $product->getPrice()->getCurrency()
                    ] : null
                ]
            ];
        } catch (\Google\Service\Exception $e) {
            if ($e->getCode() == 404) {
                return null;
            }

            Log::error('GMC Get Product Error', [
                'error' => $this->parseGoogleServiceError($e),
                'offer_id' => $offerId
            ]);

            return null;
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                return null;
            }

            Log::error('GMC Get Product Error', [
                'error' => $e->getMessage(),
                'offer_id' => $offerId
            ]);

            return null;
        }
    }

    /**
     * Parse Google Service Exception error messages
     */
    private function parseGoogleServiceError(\Google\Service\Exception $e): string
    {
        $errors = $e->getErrors();
        if (!empty($errors)) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = ($error['message'] ?? 'Unknown error') .
                    (isset($error['reason']) ? ' (Reason: ' . $error['reason'] . ')' : '');
            }
            return implode('; ', $errorMessages);
        }

        return $e->getMessage();
    }

    /**
     * Parse HTTP error messages (for backward compatibility)
     */
    private function parseHttpError(\Exception $e): string
    {
        // Try to parse JSON error response from Guzzle HTTP exceptions
        if ($e instanceof \GuzzleHttp\Exception\ClientException || $e instanceof \GuzzleHttp\Exception\ServerException) {
            $response = $e->getResponse();
            if ($response) {
                try {
                    $body = $response->getBody()->getContents();
                    $errorData = json_decode($body, true);
                    if (isset($errorData['error']['message'])) {
                        return $errorData['error']['message'];
                    }
                } catch (\Exception $parseError) {
                    // If parsing fails, continue to return original message
                }
            }
        }

        return $e->getMessage();
    }


    /**
     * Check if service is properly configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->merchantId) && !empty($this->dataSourceId) && !empty(config('services.google.merchant_credentials_path'));
    }

    /**
     * Prepare product data for API (without sending)
     * Useful for preview/debugging
     */
    public function prepareProductData($productData): array
    {
        // Validate required fields
        if (!isset($productData['price']) || empty($productData['price'])) {
            throw new \InvalidArgumentException('Product price is required and cannot be empty');
        }

        // Build product data according to new API format
        $product = [
            'offerId' => $productData['offer_id'],
            'title' => $productData['title'],
            'description' => $productData['description'],
            'link' => $productData['link'],
            'imageLink' => $productData['image_link'],
            'contentLanguage' => $productData['content_language'] ?? $this->contentLanguage,
            'targetCountry' => $productData['target_country'] ?? $this->targetCountry,
            'channel' => 'online',
            'availability' => $productData['availability'] ?? 'in stock',
            'price' => [
                'value' => $productData['price'],
                'currency' => $productData['currency'] ?? $this->currency
            ],
        ];

        // Condition
        if (isset($productData['condition'])) {
            $product['condition'] = $productData['condition'];
        }

        // Brand
        if (isset($productData['brand'])) {
            $product['brand'] = $productData['brand'];
        }

        // Google Product Category
        if (isset($productData['google_product_category'])) {
            $product['googleProductCategory'] = $productData['google_product_category'];
        }

        // Product Type
        if (isset($productData['product_type'])) {
            $product['productTypes'] = [$productData['product_type']];
        }

        // MPN
        if (isset($productData['mpn'])) {
            $product['mpn'] = $productData['mpn'];
        }

        // Additional Images
        if (isset($productData['additional_image_links']) && is_array($productData['additional_image_links']) && !empty($productData['additional_image_links'])) {
            $product['additionalImageLinks'] = array_values($productData['additional_image_links']);
        }

        // Shipping (optional)
        if (isset($productData['shipping'])) {
            $product['shipping'] = $productData['shipping'];
        }

        // Size system and size type (for Clothing/Apparel products)
        if (isset($productData['size_system'])) {
            $product['sizeSystem'] = $productData['size_system'];
        }

        if (isset($productData['size_type'])) {
            $product['sizeType'] = $productData['size_type'];
        }

        return $product;
    }

    /**
     * Get API endpoint URL
     */
    public function getApiEndpoint(): string
    {
        $parent = "accounts/{$this->merchantId}/dataSources/{$this->dataSourceId}";
        return "https://merchantapi.googleapis.com/merchantapi/v1/{$parent}/productInputs";
    }
}
