<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Attendance Device Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default attendance device driver that will be
    | used to connect and fetch attendance records. You may set this to any
    | of the drivers defined in the "devices" array below.
    |
    | Supported: "zkteco", "dahua", "null" (for testing)
    |
    */

    'default' => env('ATTENDANCE_DRIVER', 'dahua'),

    /*
    |--------------------------------------------------------------------------
    | Attendance Device Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the device drivers for your application.
    | Each driver can have its own specific configuration options.
    |
    */

    'devices' => [

        'zkteco' => [
            'driver' => 'zkteco',
            'ip' => env('ATTENDANCE_DEVICE_IP', '192.168.1.201'),
            'port' => env('ATTENDANCE_DEVICE_PORT', 4370),
        ],

        'dahua' => [
            'driver' => 'dahua',
            'connection' => env('DAHUA_DB_CONNECTION', 'local_attendance'),
            'table' => env('DAHUA_DB_TABLE', 'attendance_records'),
            'fetch_minutes' => env('DAHUA_FETCH_MINUTES', 10),
            'duplicate_threshold' => env('DAHUA_DUPLICATE_THRESHOLD', 1), // minutes
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Remote API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the remote server where attendance data will be sent.
    |
    */

    'remote_api' => [
        'url' => env('ATTENDANCE_API_URL', 'https://api.example.com/api/v1'),
        'key' => env('ATTENDANCE_API_KEY', ''),
        'timeout' => env('ATTENDANCE_API_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Settings
    |--------------------------------------------------------------------------
    |
    | Settings for synchronization process.
    |
    */

    'sync' => [
        'batch_size' => env('ATTENDANCE_SYNC_BATCH_SIZE', 100),
        'auto_clear_device' => env('ATTENDANCE_AUTO_CLEAR', false),
        'retry_failed' => env('ATTENDANCE_RETRY_FAILED', true),
        'max_retries' => env('ATTENDANCE_MAX_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable detailed logging for debugging.
    |
    */

    'debug' => env('ATTENDANCE_DEBUG', false),

];
