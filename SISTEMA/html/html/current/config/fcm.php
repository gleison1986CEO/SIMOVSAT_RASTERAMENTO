<?php

return [
    'sound' => env('FCM_SOUND', 'default'),
    'channel_id' => env('FCM_CHANNEL_ID'),
    'http' => [
        'sender_id' => env('FCM_SENDER_ID'), // todo: remove after all servers are migrated to v1
        'bridge_url' => 'http://rastreogps.app/api/firebase',
    ],
];