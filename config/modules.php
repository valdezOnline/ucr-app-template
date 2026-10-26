<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Module Paths
    |--------------------------------------------------------------------------
    |
    | These paths are scanned for module directories. Each module can define
    | optional route files and providers without changing core bootstrap code.
    |
    */
    'paths' => [
        base_path('modules'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Route File Names
    |--------------------------------------------------------------------------
    |
    | Route files are loaded from each module when present.
    |
    */
    'route_files' => [
        'web' => 'routes/web.php',
        'api' => 'routes/api.php',
    ],
];
