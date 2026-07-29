<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Site traffic beacon CORS is handled by TrafficBeaconCors middleware
    | (config/traffic.php → TRAFFIC_BEACON_CORS_ORIGINS). Keep api/* out of
    | this list so the two layers do not fight over Access-Control headers.
    |
    */

    'paths' => ['sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
