<?php

return [
    'marpel_api_token' => env('MARPEL_API_TOKEN'),

    'google_calendar' => [
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_CALENDAR_REDIRECT_URI'),
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'avisos_chat_id' => env('TELEGRAM_AVISOS_CHAT_ID'),
    ],
];
