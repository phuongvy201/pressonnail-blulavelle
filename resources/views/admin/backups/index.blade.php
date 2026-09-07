@extends('layouts.admin')

@section('content')
    <div
        class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 pb-28"
        x-data="backupRestorePage()"
    >
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Sao lưu &amp; khôi phục</h1>
            <p class="text-gray-600 max-w-3xl">
                Sao lưu để giữ một bản copy của website. Khôi phục dùng khi dữ liệu bị mất hoặc cần quay lại thời điểm đã lưu.
            </p>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-8 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-800">
            <p class="font-semibold text-slate-900">Chính sách sao lưu hiện tại</p>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tần suất</dt>
                    <dd class="mt-0.5">{{ $policy['frequency'] }} (scheduler)</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Retention (GFS)</dt>
                    <dd class="mt-0.5">
                        Daily {{ $policy['retention_daily'] }} · Weekly {{ $policy['retention_weekly'] }} · Monthly {{ $policy['retention_monthly'] }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Lưu trữ chính</dt>
                    <dd class="mt-0.5">
                        @if ($policy['s3_enabled'])
                            AWS S3
                            @if ($policy['s3_bucket'])
                                <span class="text-slate-600">· bucket <code class="rounded bg-white px-1 text-xs">{{ $policy['s3_bucket'] }}</code></span>
                            @endif
                            @if ($policy['s3_prefix'])
                                <span class="text-slate-600">/ <code class="rounded bg-white px-1 text-xs">{{ $policy['s3_prefix'] }}</code></span>
                            @endif
                        @else
                            Chỉ trên server — bật <code class="rounded bg-white px-1 text-xs">BACKUP_S3_ENABLED=true</code> trong .env
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Trên server</dt>
                    <dd class="mt-0.5">
                        @if ($policy['s3_enabled'] && ! $policy['keeps_local_copies'])
                            Không giữ zip trên server (chỉ staging tạm khi tạo/khôi phục)
                        @elseif ($policy['s3_enabled'])
                            Giữ {{ $policy['local_keep'] }} bản cache tại
                            <code class="rounded bg-white px-1 text-xs break-all">storage/app/backups</code>
                        @else
                            Lưu tại
                            <code class="rounded bg-white px-1 text-xs break-all">storage/app/backups</code>
                            (S3 chưa bật)
                        @endif
                    </dd>
                </div>
            </dl>
        </div>

        <div class="mb-8 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-950">
            <p class="font-semibold">Khôi phục sẽ thay toàn bộ dữ liệu hiện tại</p>
            <p class="mt-1 text-amber-900/90">
                Đơn hàng, sản phẩm, tài khoản và ảnh upload sẽ trở về đúng như bản sao lưu.
                File <code class="rounded bg-white/70 px-1">.env</code> (mật khẩu, Stripe, Telegram) không nằm trong backup.
            </p>
        </div>

        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-8">
            <div class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">1. Tạo bản sao lưu</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Full backup database + ảnh/upload.
                        @if ($policy['s3_enabled'])
                            Sau khi tạo sẽ đẩy lên AWS S3 rồi xóa zip khỏi server.
                        @else
                            Hiện lưu trên server; nên tải thêm bản về máy hoặc bật S3.
                        @endif
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.backups.store') }}" class="shrink-0" @submit="creating = true">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
                        :disabled="creating"
                    >
                        <span x-show="!creating">Sao lưu ngay</span>
                        <span x-cloak x-show="creating">Đang sao lưu…</span>
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden mb-8">
            <header class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-lg font-bold text-gray-900">2. Bản sao lưu có sẵn</h2>
                <p class="mt-1 text-sm text-gray-600">Danh sách gộp từ server local và AWS S3 (nếu đã bật).</p>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-gray-600">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Thời điểm</th>
                            <th class="px-6 py-3 font-semibold">Dung lượng</th>
                            <th class="px-6 py-3 font-semibold">Nơi lưu</th>
                            <th class="px-6 py-3 font-semibold">File</th>
                            <th class="px-6 py-3 font-semibold text-right">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($backups as $index => $backup)
                            <tr class="{{ $index === 0 ? 'bg-sky-50/60' : '' }}">
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900">{{ $backup['modified_label'] }}</div>
                                    <div class="text-xs text-gray-500">{{ $backup['modified_human'] }}@if ($index === 0) · mới nhất @endif</div>
                                </td>
                                <td class="px-6 py-4 text-gray-700">{{ $backup['size_label'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700">{{ $backup['location_label'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 break-all">{{ $backup['filename'] }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-2 justify-end">
                                        <a href="{{ route('admin.backups.download', $backup['filename']) }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50">Tải về</a>
                                        <button
                                            type="button"
                                            class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700"
                                            @click="openServerRestore(@js($backup['filename']), @js($backup['modified_label']))"
                                        >Khôi phục</button>
                                        <form method="POST" action="{{ route('admin.backups.destroy', $backup['filename']) }}" onsubmit="return confirm('Chỉ xóa file zip này (local + S3 nếu có). Website đang chạy không bị ảnh hưởng. Tiếp tục?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">Xóa file</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                                    Chưa có bản sao lưu. Bấm <strong>Sao lưu ngay</strong> ở bước 1.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">3. Khôi phục từ máy tính</h2>
                    <p class="mt-1 text-sm text-gray-600">Dùng khi file zip nằm trên máy bạn, không có trên server.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50"
                    @click="openUploadRestore()"
                >
                    Chọn file zip…
                </button>
            </div>
        </section>

        <div
            x-cloak
            x-show="restoreOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-end justify-center bg-gray-900/50 p-4 sm:items-center"
            @keydown.escape.window="closeRestore()"
        >
            <div
                class="w-full max-w-lg rounded-2xl bg-white shadow-xl"
                @click.outside="closeRestore()"
                role="dialog"
                aria-modal="true"
                aria-labelledby="restore-title"
            >
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 id="restore-title" class="text-lg font-bold text-gray-900">Khôi phục website</h3>
                    <p class="mt-1 text-sm text-gray-600" x-text="restoreSubtitle"></p>
                </div>
                <div class="space-y-4 px-6 py-5 text-sm text-gray-700">
                    <div class="rounded-xl bg-red-50 px-4 py-3 text-red-900">
                        <p class="font-semibold">Dữ liệu hiện tại sẽ bị thay thế ngay.</p>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li>Database (đơn, sản phẩm, tài khoản, chat…)</li>
                            <li>Ảnh và file đã upload</li>
                            <li>Không thể hoàn tác trừ khi bạn còn một bản backup khác</li>
                        </ul>
                    </div>

                    <form
                        method="POST"
                        :action="formAction"
                        :enctype="mode === 'upload' ? 'multipart/form-data' : 'application/x-www-form-urlencoded'"
                        class="space-y-4"
                        @submit="if (!canSubmit()) { $event.preventDefault(); return; } submitting = true"
                    >
                        @csrf
                        <template x-if="mode === 'upload'">
                            <div>
                                <label class="mb-1 block font-semibold text-gray-900">File backup (.zip)</label>
                                <input
                                    type="file"
                                    name="backup_file"
                                    accept=".zip,application/zip"
                                    required
                                    class="block w-full text-sm text-gray-700"
                                    @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''"
                                >
                                <p class="mt-1 text-xs text-gray-500" x-show="fileName" x-text="'Đã chọn: ' + fileName"></p>
                            </div>
                        </template>

                        <label class="flex items-start gap-3 rounded-xl border border-gray-200 px-3 py-3">
                            <input type="checkbox" name="understood" value="1" class="mt-0.5 rounded border-gray-300" x-model="understood">
                            <span>Tôi hiểu dữ liệu đang chạy trên website sẽ bị thay bằng bản sao lưu này.</span>
                        </label>

                        <div>
                            <label class="mb-1 block font-semibold text-gray-900">
                                Gõ <span class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">KHÔI PHỤC</span> để xác nhận
                            </label>
                            <input
                                type="text"
                                name="confirmation"
                                autocomplete="off"
                                class="w-full rounded-xl border-gray-300"
                                placeholder="KHÔI PHỤC"
                                x-model="confirmation"
                            >
                            <p class="mt-1 text-xs text-gray-500">Có thể gõ không dấu: KHOI PHUC</p>
                        </div>

                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-50" @click="closeRestore()">Hủy</button>
                            <button
                                type="submit"
                                class="rounded-xl bg-amber-600 px-4 py-2 font-semibold text-white hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!canSubmit() || submitting"
                            >
                                <span x-show="!submitting">Khôi phục ngay</span>
                                <span x-cloak x-show="submitting">Đang khôi phục…</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function backupRestorePage() {
            return {
                creating: false,
                restoreOpen: false,
                mode: 'server',
                filename: '',
                label: '',
                fileName: '',
                understood: false,
                confirmation: '',
                submitting: false,
                restoreRoutes: {
                    server: @js(url('/admin/backups')),
                    upload: @js(route('admin.backups.restore-upload')),
                },
                get restoreSubtitle() {
                    if (this.mode === 'upload') {
                        return 'Khôi phục từ file zip trên máy tính của bạn.';
                    }
                    if (this.label) {
                        return 'Thời điểm ' + this.label + ' · ' + this.filename;
                    }
                    return this.filename;
                },
                get formAction() {
                    if (this.mode === 'upload') {
                        return this.restoreRoutes.upload;
                    }
                    return this.restoreRoutes.server + '/' + encodeURIComponent(this.filename) + '/restore';
                },
                normalizePhrase(value) {
                    return String(value || '')
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '')
                        .trim()
                        .toUpperCase()
                        .replace(/\s+/g, ' ');
                },
                canSubmit() {
                    const phrase = this.normalizePhrase(this.confirmation);
                    const okPhrase = phrase === 'KHOI PHUC' || phrase === 'RESTORE';
                    if (!this.understood || !okPhrase) {
                        return false;
                    }
                    if (this.mode === 'upload' && !this.fileName) {
                        return false;
                    }
                    return true;
                },
                openServerRestore(filename, label) {
                    this.mode = 'server';
                    this.filename = filename;
                    this.label = label || '';
                    this.fileName = '';
                    this.understood = false;
                    this.confirmation = '';
                    this.submitting = false;
                    this.restoreOpen = true;
                },
                openUploadRestore() {
                    this.mode = 'upload';
                    this.filename = '';
                    this.label = '';
                    this.fileName = '';
                    this.understood = false;
                    this.confirmation = '';
                    this.submitting = false;
                    this.restoreOpen = true;
                },
                closeRestore() {
                    if (this.submitting) {
                        return;
                    }
                    this.restoreOpen = false;
                },
            };
        }
    </script>
@endpush
