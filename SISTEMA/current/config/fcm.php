<?php
return [
    'driver' => env('FCM_PROTOCOL', 'http'),
    'log_enabled' => false,
    'http' => [
        'sender_id' => env('FCM_SENDER_ID', ''),
        'server_key' => env('FCM_SERVER_KEY', ''),
        'server_send_url' => 'https://fcm.googleapis.com/fcm/send',
        'server_group_url' => 'https://android.googleapis.com/gcm/notification',
        'timeout' => 30.0, // in second
    ],
];