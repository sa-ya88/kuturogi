<?php

return [

    'api_key' => env('INTEGRATION_API_KEY'),

    'webhook' => [
        'url' => env('INTEGRATION_WEBHOOK_URL'),
        'secret' => env('INTEGRATION_WEBHOOK_SECRET'),
    ],

];
