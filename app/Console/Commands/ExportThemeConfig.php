<?php

namespace App\Console\Commands;

use App\Support\Settings;
use Illuminate\Console\Command;

class ExportThemeConfig extends Command
{
    protected $signature = 'settings:export-theme';

    protected $description = 'Xuất màu theme/site từ database ra file config/theme.php để lưu vĩnh viễn (commit lên git, không mất khi cập nhật bản mới)';

    public function handle(): int
    {
        $keys = [
            'header_bg' => 'theme.header_bg',
            'header_border' => 'theme.header_border',
            'footer_faq_bg' => 'theme.footer_faq_bg',
            'footer_bg' => 'theme.footer_bg',
            'testimonials_bg' => 'theme.testimonials_bg',
            'promo_banner' => 'site.promo_banner',
            'promo_banner_bg' => 'site.promo_banner_bg',
            'mail_logo_url' => 'mail.logo_url',
            'mail_brand_name' => 'mail.brand_name',
        ];

        $out = [];
        foreach ($keys as $configKey => $settingsKey) {
            $value = Settings::get($settingsKey, config("theme.{$configKey}"));
            $out[$configKey] = $value;
        }

        $defaults = [
            'header_bg' => null,
            'header_border' => null,
            'footer_faq_bg' => null,
            'footer_bg' => '#242B3D',
            'testimonials_bg' => null,
            'promo_banner' => 'Free Shipping on Orders Over $100 • Premium Press-on Nails',
            'promo_banner_bg' => null,
            'mail_logo_url' => null,
            'mail_brand_name' => null,
        ];

        $lines = [
            '<?php',
            '',
            'return [',
            '',
            '    /*',
            '    |--------------------------------------------------------------------------',
            '    | Theme & Site defaults (fallback when DB settings are empty)',
            '    |--------------------------------------------------------------------------',
            '    | Giá trị xuất từ DB bởi lệnh: php artisan settings:export-theme',
            '    | Commit file này lên git để màu sắc không mất khi deploy/cập nhật bản mới.',
            '    */',
            '',
        ];

        foreach (array_keys($out) as $key) {
            $value = $out[$key] ?? $defaults[$key] ?? null;
            $export = $this->exportValue($value);
            $lines[] = "    '{$key}' => {$export},";
        }

        $lines[] = '';
        $lines[] = '];';

        $path = config_path('theme.php');
        if (!is_dir(dirname($path))) {
            $this->error('Thư mục config không tồn tại.');
            return self::FAILURE;
        }

        if (file_put_contents($path, implode("\n", $lines)) === false) {
            $this->error('Không ghi được file: ' . $path);
            return self::FAILURE;
        }

        $this->info('Đã xuất theme ra: ' . $path);
        $this->line('Bạn có thể commit file này lên git để lưu màu vĩnh viễn.');
        return self::SUCCESS;
    }

    private function exportValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'null';
        }
        return var_export((string) $value, true);
    }
}
