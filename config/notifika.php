<?php

return [
    'max_attempts' => env('NOTIFIKA_MAX_ATTEMPTS', 3),
    'queue' => env('NOTIFIKA_QUEUE', 'notifications'),
];
