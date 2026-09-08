<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ApiV1\ApiUploadService;
use App\Support\ApiV1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(private ApiUploadService $uploads)
    {
    }

    public function presign(Request $request): JsonResponse
    {
        return $this->uploads->presign($request);
    }

    public function putContent(Request $request, string $uploadId): JsonResponse
    {
        return $this->uploads->putContent($request, $uploadId);
    }

    public function complete(Request $request, string $uploadId): JsonResponse
    {
        return $this->uploads->complete($request, $uploadId);
    }

    public function destroy(Request $request, string $uploadId): JsonResponse
    {
        return $this->uploads->destroy($request, $uploadId);
    }

    public function download(string $uploadId)
    {
        return $this->uploads->streamDownload($uploadId);
    }
}
