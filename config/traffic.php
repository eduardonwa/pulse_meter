<?php

return [
    'internal_ips' => array_filter(
        array_map(
            'trim',
            explode(',', env('TRAFFIC_INTERNAL_IPS', '127.0.0.1'))
        )
    ),
    
    'access_log_path' => env('TRAFFIC_ACCESS_LOG_PATH')
        ?: storage_path('app/testing/dorelog-access-sample.log'),

    'history_timezone' => 'America/Hermosillo',
];