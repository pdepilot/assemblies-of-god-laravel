<?php

return [
    'ip_salt' => env('TRAFFIC_IP_SALT', 'ag-ikenebgu-site-traffic-v1'),
    'retention_days' => 90,
    'geo_ttl_days' => 30,
    'rate_limit_max' => 120,
    'rate_limit_window' => 300,
    'geo_lookup_enabled' => env('TRAFFIC_GEO_LOOKUP', true),
    'beacon_cors_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRAFFIC_BEACON_CORS_ORIGINS', '*'))
    ))),
];
