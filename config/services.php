<?php

return [

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'provider' => env('WHATSAPP_PROVIDER', 'venom'),
        'process_immediately' => env('WHATSAPP_PROCESS_IMMEDIATELY', false),
    ],

    'venom' => [
        'base_url' => env('VENOM_BASE_URL'),
        'session' => env('VENOM_SESSION'),
        'token' => env('VENOM_TOKEN'),
        'node_command' => env('VENOM_NODE_COMMAND', 'node server.js'),
        'node_workdir' => env('VENOM_NODE_WORKDIR', 'C:/venom-bot'),
        'queue_command' => env('WHATSAPP_QUEUE_WORKER_COMMAND', 'php artisan queue:work --queue=default --tries=1 --timeout=120'),
        'queue_workdir' => env('WHATSAPP_QUEUE_WORKER_WORKDIR', base_path()),
    ],

];
