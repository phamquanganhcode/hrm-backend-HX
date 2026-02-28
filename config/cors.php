<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // 1. Cho phép CORS hoạt động trên tất cả các link bắt đầu bằng /api/
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // 2. Cho phép tất cả các phương thức (GET, POST, PUT, DELETE...)
    'allowed_methods' => ['*'],

    // 3. Danh sách các "Khách VIP" được phép vào lấy dữ liệu (Vercel và Local của bạn)
    'allowed_origins' => [
        'https://frontend-hr-hai-xom-v2.vercel.app',
        'http://localhost:5173',
    ],

    'allowed_origins_patterns' => [],

    // 4. Cho phép tất cả các loại Headers (đặc biệt là cái header mang Token)
    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // 5. Thường dùng cho Cookie/Session. Vì bạn dùng Bearer Token nên để false là chuẩn.
    'supports_credentials' => false,

];