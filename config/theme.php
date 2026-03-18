<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Theme & Site defaults (fallback when DB settings are empty)
    |--------------------------------------------------------------------------
    | Giá trị xuất từ DB bởi lệnh: php artisan settings:export-theme
    | Commit file này lên git để màu sắc không mất khi deploy/cập nhật bản mới.
    */

    'header_bg' => null,
    'header_border' => null,
    'footer_faq_bg' => null,
    'footer_bg' => '#242B3D',
    'testimonials_bg' => null,
    'promo_banner' => 'Free Shipping on Orders Over $100 • Premium Press-on Nails',
    'promo_banner_bg' => null,

    // Mail layout (logo & brand trong email)
    'mail_logo_url' => null,
    'mail_brand_name' => null,

];