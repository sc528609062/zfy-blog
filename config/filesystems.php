<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),
    'downloads' => env('DOWNLOADS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => false,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'media' => [
            'driver' => 'local',
            'root' => storage_path('app/public/media'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/media',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'oss' => [
            'driver' => 'oss', 'access_key' => env('OSS_ACCESS_KEY_ID'), 'secret_key' => env('OSS_ACCESS_KEY_SECRET'),
            'region' => env('OSS_REGION', 'cn-hangzhou'), 'bucket' => env('OSS_BUCKET'),
            'endpoint' => env('OSS_ENDPOINT'), 'isCName' => false, 'root' => '',
            'signatureVersion' => 'v4', 'visibility' => 'private', 'throw' => true,
        ],
        'cos' => [
            'driver' => 'cos', 'secret_id' => env('COS_SECRET_ID'), 'secret_key' => env('COS_SECRET_KEY'),
            'region' => env('COS_REGION', 'ap-guangzhou'), 'bucket' => env('COS_BUCKET'),
            'app_id' => env('COS_APP_ID'), 'signed_url' => true, 'use_https' => true,
            'visibility' => 'private', 'throw' => true,
            'guzzle' => ['timeout' => 30, 'connect_timeout' => 10],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
