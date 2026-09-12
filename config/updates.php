<?php

return [
    'provider' => env('ZFY_UPDATE_PROVIDER', 'gitee'),
    'repository' => env('ZFY_UPDATE_REPOSITORY', 'c528609062/zfy-blog'),
    'token' => env('ZFY_UPDATE_TOKEN'),
    'public_key' => env('ZFY_UPDATE_PUBLIC_KEY'),
    'max_bytes' => 300 * 1024 * 1024,
    'timeout' => 120,
];
