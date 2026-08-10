<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VirtualNailTrial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VirtualNailTrialAdminController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = [
            VirtualNailTrial::STATUS_PENDING,
            VirtualNailTrial::STATUS_PROCESSING,
            VirtualNailTrial::STATUS_COMPLETED,
            VirtualNailTrial::STATUS_FAILED,
        ];

        $perPage = (int) $request->get('per_page', 20);
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = VirtualNailTrial::query()
            ->with(['user:id,name,email', 'product:id,name,slug'])
            ->latest();

        if ($request->filled('status') && in_array($request->status, $statuses, true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('email', 'like', '%'.$search.'%')
                            ->orWhere('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $trials = $query->paginate($perPage)->withQueryString();

        $total = VirtualNailTrial::count();
        $completed = VirtualNailTrial::where('status', VirtualNailTrial::STATUS_COMPLETED)->count();
        $failed = VirtualNailTrial::where('status', VirtualNailTrial::STATUS_FAILED)->count();
        $inProgress = VirtualNailTrial::whereIn('status', [
            VirtualNailTrial::STATUS_PENDING,
            VirtualNailTrial::STATUS_PROCESSING,
        ])->count();

        $stats = [
            'total' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'in_progress' => $inProgress,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 1) : 0,
        ];

        return view('admin.virtual-nail-trials.index', [
            'trials' => $trials,
            'statuses' => $statuses,
            'stats' => $stats,
            'perPage' => $perPage,
        ]);
    }

    public function show(VirtualNailTrial $trial): View
    {
        $trial->load(['user:id,name,email', 'product:id,name,slug']);

        return view('admin.virtual-nail-trials.show', compact('trial'));
    }

    public function handImage(VirtualNailTrial $trial): StreamedResponse
    {
        return $this->streamTrialFile($trial->hand_image_path, 'virtual-nail-hand.jpg');
    }

    public function resultImage(VirtualNailTrial $trial): StreamedResponse
    {
        if (! $trial->result_image_path) {
            abort(404);
        }

        return $this->streamTrialFile($trial->result_image_path, 'virtual-nail-result.png');
    }

    private function streamTrialFile(string $path, string $downloadName): StreamedResponse
    {
        $disk = Storage::disk('local');
        if ($path === '' || ! $disk->exists($path)) {
            abort(404);
        }

        $mime = mime_content_type($disk->path($path)) ?: 'image/png';

        return response()->stream(function () use ($disk, $path) {
            echo $disk->get($path);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.$downloadName.'"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
