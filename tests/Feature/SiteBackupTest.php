<?php

use App\Models\User;
use App\Services\SiteBackupService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config([
        'backup.disk_path' => storage_path('framework/testing/backups'),
        'backup.s3.enabled' => false,
        'backup.local_keep' => 0,
        'backup.retention.daily' => 7,
        'backup.retention.weekly' => 4,
        'backup.retention.monthly' => 12,
    ]);
    File::deleteDirectory(config('backup.disk_path'));
    File::ensureDirectoryExists(config('backup.disk_path'));
    foreach (File::glob(storage_path('app/tmp-s3-restore-*')) ?: [] as $tmp) {
        File::delete($tmp);
    }
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/backups'));
    foreach (File::glob(storage_path('app/tmp-s3-restore-*')) ?: [] as $tmp) {
        File::delete($tmp);
    }
});

function adminUser(): User
{
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('admin');

    return $user;
}

test('admin can create a backup zip containing the database dump', function () {
    $this->actingAs(adminUser());

    $this->post(route('admin.backups.store'))
        ->assertRedirect();

    $backups = app(SiteBackupService::class)->list();
    expect($backups)->not->toBeEmpty();

    $path = app(SiteBackupService::class)->pathFor($backups[0]['filename']);
    $zip = new ZipArchive;
    expect($zip->open($path))->toBeTrue();
    expect($zip->locateName('database.sql'))->not->toBeFalse();
    expect($zip->locateName('manifest.json'))->not->toBeFalse();
    $zip->close();
});

test('backup restore brings back a deleted user row', function () {
    $admin = adminUser();
    $marker = User::factory()->create(['email' => 'restore-marker@example.com']);

    $created = app(SiteBackupService::class)->create();
    $marker->forceDelete();
    expect(User::withTrashed()->where('email', 'restore-marker@example.com')->exists())->toBeFalse();

    app(SiteBackupService::class)->restoreFromZip($created['path']);

    expect(User::withTrashed()->where('email', 'restore-marker@example.com')->exists())->toBeTrue();
});

test('guests cannot open the backup page', function () {
    $this->get(route('admin.backups.index'))->assertRedirect();
});

test('backup page shows vietnamese restore guidance and policy', function () {
    $this->actingAs(adminUser());

    $this->get(route('admin.backups.index'))
        ->assertOk()
        ->assertSee('Sao lưu &amp; khôi phục', false)
        ->assertSee('Khôi phục sẽ thay toàn bộ dữ liệu hiện tại')
        ->assertSee('Chính sách sao lưu hiện tại')
        ->assertSee('Hàng ngày lúc 02:00')
        ->assertSee('Tạo bản sao lưu')
        ->assertSee('Khôi phục từ máy tính')
        ->assertSee('KHÔI PHỤC');
});

test('restore from the admin page requires confirmation', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    $created = app(SiteBackupService::class)->create();

    $this->post(route('admin.backups.restore', $created['filename']), [
        'confirmation' => 'KHÔI PHỤC',
    ])->assertSessionHasErrors('understood');

    $this->post(route('admin.backups.restore', $created['filename']), [
        'understood' => '1',
        'confirmation' => 'sai',
    ])->assertSessionHasErrors('confirmation');
});

test('admin can restore from the admin page after confirming', function () {
    $admin = adminUser();
    $this->actingAs($admin);

    $created = app(SiteBackupService::class)->create();

    $this->post(route('admin.backups.restore', $created['filename']), [
        'understood' => '1',
        'confirmation' => 'khoi phuc',
    ])->assertRedirect()->assertSessionHas('success');
});

test('backup uploads to s3 when enabled', function () {
    Storage::fake('s3_backups');

    config([
        'backup.s3.enabled' => true,
        'backup.s3.disk' => 's3_backups',
        'backup.s3.prefix' => 'site-backups',
        'filesystems.disks.s3_backups.key' => 'testing',
        'filesystems.disks.s3_backups.secret' => 'testing',
        'filesystems.disks.s3_backups.bucket' => 'test-backups',
        'backup.local_keep' => 0,
    ]);

    $created = app(SiteBackupService::class)->create();

    expect($created['uploaded_to_s3'])->toBeTrue();
    expect($created['path'])->toBeNull();
    Storage::disk('s3_backups')->assertExists('site-backups/'.$created['filename']);
    expect(File::files(config('backup.disk_path')))->toBeEmpty();

    $listed = app(SiteBackupService::class)->list();
    expect($listed[0]['on_s3'])->toBeTrue();
    expect($listed[0]['on_local'])->toBeFalse();
    expect($listed[0]['location_label'])->toContain('AWS S3');
});

test('restore from s3 downloads to a temp file then cleans up', function () {
    Storage::fake('s3_backups');

    config([
        'backup.s3.enabled' => true,
        'backup.s3.disk' => 's3_backups',
        'backup.s3.prefix' => 'site-backups',
        'filesystems.disks.s3_backups.key' => 'testing',
        'filesystems.disks.s3_backups.secret' => 'testing',
        'filesystems.disks.s3_backups.bucket' => 'test-backups',
        'backup.local_keep' => 0,
    ]);

    $marker = User::factory()->create(['email' => 's3-restore-marker@example.com']);
    $created = app(SiteBackupService::class)->create();
    $marker->forceDelete();

    $before = File::glob(storage_path('app/tmp-s3-restore-*')) ?: [];

    app(SiteBackupService::class)->restoreByFilename($created['filename']);

    expect(User::withTrashed()->where('email', 's3-restore-marker@example.com')->exists())->toBeTrue();
    expect(File::glob(storage_path('app/tmp-s3-restore-*')) ?: [])->toEqual($before);
    expect(File::files(config('backup.disk_path')))->toBeEmpty();
});
test('gfs retention deletes backups outside daily weekly monthly windows', function () {
    config([
        'backup.retention.daily' => 2,
        'backup.retention.weekly' => 1,
        'backup.retention.monthly' => 1,
    ]);

    $service = app(SiteBackupService::class);
    $deleted = [];

    $items = [
        ['id' => 'recent-1', 'mtime' => now()->timestamp],
        ['id' => 'recent-2', 'mtime' => now()->subDay()->timestamp],
        ['id' => 'old-daily', 'mtime' => now()->subDays(10)->timestamp],
        ['id' => 'old-month', 'mtime' => now()->subMonths(3)->timestamp],
    ];

    $method = new ReflectionMethod(SiteBackupService::class, 'pruneByGfs');
    $method->setAccessible(true);
    $method->invoke($service, $items, function (string $id) use (&$deleted): void {
        $deleted[] = $id;
    });

    expect($deleted)->toContain('old-daily');
    expect($deleted)->toContain('old-month');
    expect($deleted)->not->toContain('recent-1');
    expect($deleted)->not->toContain('recent-2');
});
