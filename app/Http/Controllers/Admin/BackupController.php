<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SiteBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(private readonly SiteBackupService $backups)
    {
    }

    public function index(): View
    {
        return view('admin.backups.index', [
            'backups' => $this->backups->list(),
            'policy' => $this->backups->policySummary(),
        ]);
    }

    public function store(): RedirectResponse
    {
        @set_time_limit(0);

        try {
            $backup = $this->backups->create();
        } catch (Throwable $e) {
            return back()->with('error', 'Không tạo được bản sao lưu: '.$e->getMessage());
        }

        $msg = 'Đã tạo bản sao lưu '.$backup['filename'].'.';
        if (! empty($backup['uploaded_to_s3'])) {
            $msg .= ' Đã đẩy lên AWS S3 và không giữ bản zip trên server.';
        } else {
            $msg .= ' Lưu trên server (S3 chưa bật). Bạn nên tải thêm một bản về máy.';
        }

        return back()->with('success', $msg);
    }

    public function download(string $filename): BinaryFileResponse|StreamedResponse
    {
        return $this->backups->downloadResponse($filename);
    }

    public function destroy(string $filename): RedirectResponse
    {
        try {
            $this->backups->delete($filename);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Đã xóa file sao lưu. Website đang chạy không bị ảnh hưởng.');
    }

    public function restore(Request $request, string $filename): RedirectResponse
    {
        $this->assertRestoreConfirmation($request);

        @set_time_limit(0);

        try {
            $this->backups->restoreByFilename($filename);
        } catch (Throwable $e) {
            return back()->with('error', 'Khôi phục thất bại: '.$e->getMessage());
        }

        return back()->with('success', 'Đã khôi phục database và file upload từ '.$filename.'.');
    }

    public function restoreUpload(Request $request): RedirectResponse
    {
        $this->assertRestoreConfirmation($request);

        $request->validate([
            'backup_file' => ['required', 'file', 'mimes:zip', 'max:512000'],
        ]);

        @set_time_limit(0);

        try {
            $filename = $this->backups->storeUploadedZip($request->file('backup_file'));
            $this->backups->restoreByFilename($filename);
        } catch (Throwable $e) {
            return back()->with('error', 'Khôi phục thất bại: '.$e->getMessage());
        }

        return back()->with('success', 'Đã tải file lên và khôi phục website.');
    }

    private function assertRestoreConfirmation(Request $request): void
    {
        $request->validate([
            'understood' => ['accepted'],
            'confirmation' => ['required', 'string'],
        ], [
            'understood.accepted' => 'Hãy đánh dấu ô xác nhận trước khi khôi phục.',
            'confirmation.required' => 'Hãy gõ KHÔI PHỤC để tiếp tục.',
        ]);

        $normalized = Str::of((string) $request->input('confirmation'))
            ->trim()
            ->ascii()
            ->upper()
            ->replaceMatches('/\s+/', ' ')
            ->toString();

        if (! in_array($normalized, ['KHOI PHUC', 'RESTORE'], true)) {
            throw ValidationException::withMessages([
                'confirmation' => 'Gõ đúng chữ KHÔI PHỤC (có thể bỏ dấu) để xác nhận.',
            ]);
        }
    }
}
