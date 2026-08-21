<?php

return [

    'api_key' => env('INTEGRATION_API_KEY'),

    'shared_database' => (bool) env('SHARED_DATABASE', false),

    'webhook' => [
        'url' => env('INTEGRATION_WEBHOOK_URL'),
        'secret' => env('INTEGRATION_WEBHOOK_SECRET'),
    ],

];
