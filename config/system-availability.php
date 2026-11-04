<?php

return [
    'enabled' => filter_var(
        env('SYSTEM_OPERATION_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'lock_at' => env('SYSTEM_LOCK_AT'),

    'timezone' => env('SYSTEM_LOCK_TIMEZONE', 'Asia/Manila'),
];