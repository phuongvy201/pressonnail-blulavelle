<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;

class ReviewsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    protected array $errors = [];
    protected int $successCount = 0;
    protected User $user;

    /** @var array<int, Product> cache product_id => Product */
    protected array $productCache = [];

    public function __construct(?User $user = null)
    {
        $this->user = $user ?? auth()->user();
    }

    public function model(array $row)
    {
        $productId = $this->resolveProductId($row);
        if (!$productId) {
            $ident = $row['product_id'] ?? $row['product_sku'] ?? '?';
            $this->errors[] = "Sản phẩm không tìm thấy hoặc không có quyền: {$ident}";
            return null;
        }

        $rating = isset($row['rating']) ? (int) $row['rating'] : 5;
        $rating = max(1, min(5, $rating));

        $rawImageUrl = trim((string) ($row['image_url'] ?? ''));
        $imageUrl = $rawImageUrl !== '' ? $this->uploadImageToS3($rawImageUrl) : null;
        if ($rawImageUrl !== '' && $imageUrl === null) {
            $imageUrl = $rawImageUrl; // fallback giữ URL gốc nếu upload S3 thất bại
        }

        $review = new Review([
            'product_id' => $productId,
            'user_id' => null,
            'customer_name' => trim((string) ($row['customer_name'] ?? '')),
            'customer_email' => trim((string) ($row['customer_email'] ?? '')) ?: null,
            'rating' => $rating,
            'review_text' => trim((string) ($row['review_text'] ?? '')) ?: null,
            'image_url' => $imageUrl,
            'title' => trim((string) ($row['title'] ?? '')) ?: null,
            'is_verified_purchase' => $this->parseBool($row['is_verified_purchase'] ?? true),
            'is_approved' => $this->parseBool($row['is_approved'] ?? true),
        ]);

        $this->successCount++;
        return $review;
    }

    protected function resolveProductId(array $row): ?int
    {
        $productId = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $productSku = isset($row['product_sku']) ? trim((string) $row['product_sku']) : null;

        $query = Product::query();
        if (!$this->user->hasRole('admin')) {
            $query->where('user_id', $this->user->id);
        }

        if ($productId) {
            $product = $this->productCache[$productId] ?? $query->clone()->find($productId);
            if ($product) {
                $this->productCache[$productId] = $product;
                return $product->id;
            }
        }

        if ($productSku !== null && $productSku !== '') {
            $product = $query->clone()->where('sku', $productSku)->first();
            if ($product) {
                $this->productCache[$product->id] = $product;
                return $product->id;
            }
        }

        return null;
    }

    protected function isGoogleDriveUrl(string $url): bool
    {
        return str_contains($url, 'drive.google.com');
    }

    /**
     * Lấy file ID từ link chia sẻ Google Drive.
     * Hỗ trợ: /file/d/FILE_ID/view, ?id=FILE_ID, /open?id=FILE_ID, /uc?id=FILE_ID
     */
    protected function getGoogleDriveFileId(string $url): ?string
    {
        if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return $m[1];
        }
        $parsed = parse_url($url);
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $q);
            if (!empty($q['id'])) {
                return $q['id'];
            }
        }
        return null;
    }

    /**
     * Tải file từ Google Drive (link share) về nội dung binary.
     * Xử lý cả trường hợp file lớn (trang xác nhận virus scan).
     * Trả về ['content' => string, 'extension' => string] hoặc null.
     */
    protected function downloadFromGoogleDrive(string $shareUrl): ?array
    {
        $fileId = $this->getGoogleDriveFileId($shareUrl);
        if ($fileId === null) {
            Log::warning("ReviewsImport: không parse được file ID từ Google Drive URL", ['url' => $shareUrl]);
            return null;
        }

        $downloadUrl = 'https://drive.google.com/uc?export=download&id=' . $fileId;

        try {
            $response = Http::timeout(30)->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->get($downloadUrl);

            if (!$response->successful()) {
                Log::warning("ReviewsImport: Google Drive request không thành công", ['file_id' => $fileId, 'status' => $response->status()]);
                return null;
            }

            $body = $response->body();

            // Google Drive với file lớn trả về HTML có form "virus scan warning", cần lấy confirm token
            $confirm = null;
            if (preg_match('#confirm=([0-9A-Za-z_-]+)#', $body, $m)) {
                $confirm = $m[1];
            } elseif (preg_match('#name="confirm"\s+value="([^"]+)"#', $body, $m)) {
                $confirm = $m[1];
            }
            if ($confirm !== null) {
                $downloadUrl = 'https://drive.google.com/uc?export=download&id=' . $fileId . '&confirm=' . $confirm;
                $response = Http::timeout(30)->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->get($downloadUrl);
                if (!$response->successful()) {
                    return null;
                }
                $body = $response->body();
            }

            // Nếu vẫn là HTML (lỗi hoặc trang đăng nhập) thì bỏ
            if (str_starts_with(trim($body), '<!') || str_starts_with(trim($body), '<html')) {
                Log::warning("ReviewsImport: Google Drive trả về HTML thay vì file (có thể link chưa public)", ['file_id' => $fileId]);
                return null;
            }

            if (strlen($body) === 0 || strlen($body) > 10 * 1024 * 1024) {
                Log::warning("ReviewsImport: Google Drive file rỗng hoặc quá lớn (>10MB)", ['file_id' => $fileId]);
                return null;
            }

            $extension = 'jpg';
            $contentType = $response->header('Content-Disposition');
            if ($contentType && preg_match('#filename[*]?=(?:UTF-8\'\')?["\']?[^"\']*\.([a-z0-9]+)#i', $contentType, $m)) {
                $ext = strtolower($m[1]);
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $extension = $ext === 'jpeg' ? 'jpg' : $ext;
                }
            }
            $contentType = $response->header('Content-Type');
            if ($contentType && preg_match('#image/(jpeg|jpg|png|gif|webp)#i', $contentType, $m)) {
                $ext = strtolower($m[1]);
                $extension = $ext === 'jpeg' ? 'jpg' : $ext;
            }

            return ['content' => $body, 'extension' => $extension];
        } catch (\Throwable $e) {
            Log::warning("ReviewsImport: lỗi tải Google Drive", ['url' => $shareUrl, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Đẩy ảnh lên AWS S3: từ URL (http/https), Google Drive share, hoặc đường dẫn local (storage/..., public/...).
     * Nếu đã là URL S3 thì giữ nguyên. Trả về URL S3 hoặc null nếu lỗi.
     */
    protected function uploadImageToS3(string $imageUrl): ?string
    {
        $imageUrl = trim($imageUrl);
        if ($imageUrl === '') {
            return null;
        }

        $s3Base = 'https://s3.us-east-1.amazonaws.com/image.bluprinter/';
        if (str_contains($imageUrl, 'amazonaws.com') || str_contains($imageUrl, 's3.')) {
            return $imageUrl;
        }

        $fileContent = null;
        $extension = 'jpg';

        if (str_starts_with($imageUrl, 'http://') || str_starts_with($imageUrl, 'https://')) {
            if ($this->isGoogleDriveUrl($imageUrl)) {
                $result = $this->downloadFromGoogleDrive($imageUrl);
                if ($result !== null) {
                    return $this->uploadReviewImageContentToS3($result['content'], $result['extension'], $s3Base);
                }
                return null;
            }
            return $this->downloadAndUploadReviewImageToS3($imageUrl, $s3Base);
        }

        // Đường dẫn local
        $localPath = $imageUrl;
        if (str_starts_with($localPath, 'storage/')) {
            $localPath = storage_path('app/public/' . Str::after($localPath, 'storage/'));
        } elseif (str_starts_with($localPath, 'public/')) {
            $localPath = public_path(Str::after($localPath, 'public/'));
        } elseif (!str_contains($localPath, '://')) {
            $localPath = public_path($localPath);
        }
        if (!is_file($localPath) || !is_readable($localPath)) {
            Log::warning("ReviewsImport: file local không tồn tại hoặc không đọc được", ['path' => $imageUrl]);
            return null;
        }
        $fileContent = file_get_contents($localPath);
        if ($fileContent === false || strlen($fileContent) > 10 * 1024 * 1024) {
            return null;
        }
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        $extension = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? ($ext === 'jpeg' ? 'jpg' : $ext) : 'jpg';
        return $this->uploadReviewImageContentToS3($fileContent, $extension, $s3Base);
    }

    /**
     * Tải ảnh từ URL (http/https) và đẩy lên S3 — tham khảo ProductsImport::downloadAndUploadToS3.
     * Chỉ xử lý ảnh, timeout 90s, retry, headers giống ProductsImport.
     */
    protected function downloadAndUploadReviewImageToS3(string $url, string $s3Base): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            Log::warning("ReviewsImport: URL không hợp lệ", ['url' => $url]);
            return null;
        }

        $internalMaxRetries = 3;
        $response = null;
        $lastError = null;

        for ($attempt = 1; $attempt <= $internalMaxRetries; $attempt++) {
            try {
                $response = Http::timeout(90)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8',
                        'Accept-Language' => 'en-US,en;q=0.9',
                        'Referer' => parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST),
                        'Connection' => 'keep-alive',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Cache-Control' => 'no-cache',
                    ])
                    ->withOptions([
                        'allow_redirects' => true,
                        'max_redirects' => 5,
                        'verify' => false,
                        'curl' => [
                            CURLOPT_TCP_KEEPALIVE => 1,
                            CURLOPT_CONNECTTIMEOUT => 30,
                            CURLOPT_TIMEOUT => 90,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_SSL_VERIFYHOST => false,
                        ],
                    ])
                    ->get($url);

                if ($response->successful()) {
                    break;
                }
                $lastError = "HTTP {$response->status()}";
                if ($attempt < $internalMaxRetries) {
                    usleep(pow(2, $attempt) * 1000000 + rand(500000, 1000000));
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning("ReviewsImport: lỗi tải URL", ['url' => $url, 'attempt' => $attempt, 'error' => $lastError]);
                if ($attempt < $internalMaxRetries) {
                    usleep(pow(2, $attempt) * 1000000 + rand(500000, 1000000));
                }
            }
        }

        if (!$response || !$response->successful()) {
            Log::warning("ReviewsImport: không tải được ảnh sau {$internalMaxRetries} lần", ['url' => $url, 'error' => $lastError]);
            return null;
        }

        $fileContent = $response->body();
        if (empty($fileContent) || strlen($fileContent) > 10 * 1024 * 1024) {
            Log::warning("ReviewsImport: ảnh rỗng hoặc >10MB", ['url' => $url]);
            return null;
        }
        if (strlen($fileContent) < 100) {
            Log::warning("ReviewsImport: ảnh quá nhỏ (có thể là trang lỗi)", ['url' => $url]);
            return null;
        }

        $contentType = $response->header('Content-Type');
        $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
        $extension = $extension ? strtolower(explode('?', $extension)[0]) : '';
        if (empty($extension) && $contentType) {
            $extension = $this->getExtensionFromContentType($contentType);
        }
        if (empty($extension)) {
            $extension = $this->detectExtensionFromContent($fileContent);
        }
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $extension = 'jpg';
        }
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        return $this->uploadReviewImageContentToS3($fileContent, $extension, $s3Base);
    }

    /**
     * Ghi nội dung ảnh vào file tạm, dùng putFileAs lên S3 (giống ProductsImport).
     */
    protected function uploadReviewImageContentToS3(string $fileContent, string $extension, string $s3Base): ?string
    {
        $disk = Storage::disk('s3');
        if (!config('filesystems.disks.s3.key') || !config('filesystems.disks.s3.bucket')) {
            Log::warning("ReviewsImport: S3 chưa cấu hình");
            return null;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'review_import_');
        if ($tempFile === false) {
            Log::warning("ReviewsImport: không tạo được file tạm");
            return null;
        }

        try {
            if (file_put_contents($tempFile, $fileContent) === false) {
                return null;
            }
            $fileObject = new File($tempFile);
            $fileName = time() . '_' . Str::random(10) . '.' . $extension;
            $filePath = $disk->putFileAs('reviews', $fileObject, $fileName);
            if ($filePath && $filePath !== '') {
                return $s3Base . $filePath;
            }
            Log::warning("ReviewsImport: putFileAs trả về rỗng", ['fileName' => $fileName]);
            return null;
        } catch (\Throwable $e) {
            Log::warning("ReviewsImport: lỗi upload S3", ['error' => $e->getMessage()]);
            return null;
        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }

    protected function getExtensionFromContentType(?string $contentType): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if ($contentType) {
            $contentType = strtolower(explode(';', $contentType)[0]);
        }
        return $map[$contentType] ?? 'jpg';
    }

    protected function detectExtensionFromContent(string $content): string
    {
        if (strlen($content) < 12) {
            return 'jpg';
        }
        $header = substr($content, 0, 12);
        if (substr($header, 0, 2) === "\xFF\xD8") {
            return 'jpg';
        }
        if (substr($header, 0, 8) === "\x89PNG\r\n\x1A\n") {
            return 'png';
        }
        if (substr($header, 0, 6) === "GIF87a" || substr($header, 0, 6) === "GIF89a") {
            return 'gif';
        }
        if (substr($header, 0, 4) === "RIFF" && substr($header, 8, 4) === "WEBP") {
            return 'webp';
        }
        return 'jpg';
    }

    protected function parseBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        $v = is_string($value) ? strtolower(trim($value)) : $value;
        if ($v === '' || $v === null) {
            return true;
        }
        return in_array($v, [1, '1', 'yes', 'true', 'y'], true);
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required_without:product_sku',
            'product_sku' => 'required_without:product_id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'rating' => 'nullable|integer|min:1|max:5',
            'review_text' => 'nullable|string',
            'image_url' => 'nullable|string|max:2048',
            'title' => 'nullable|string|max:255',
            'is_verified_purchase' => 'nullable',
            'is_approved' => 'nullable',
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'customer_name.required' => 'Tên khách hàng là bắt buộc.',
        ];
    }

    public function onFailure(\Maatwebsite\Excel\Validators\Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->errors[] = 'Dòng ' . $failure->row() . ': ' . implode(', ', $failure->errors());
        }
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}
