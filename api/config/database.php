<?

return [

    'default' => 'main',
    'migrations' => 'migrations',
    'connections' => [

        // Default
        'main' => [
            'driver' => 'mysql',
            'host' => env('DB_MAIN_HOST'),
            'port' => env('DB_MAIN_PORT'),
            'database' => env('DB_MAIN_NAME'),
            'username' => env('DB_MAIN_USERNAME'),
            'password' => env('DB_MAIN_PASSWORD'),
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null
        ],

        // MongoDB Log
        'log' => [
            'driver' => 'mongodb',
            'host' => env('DB_LOG_HOST'),
            'port' => env('DB_LOG_PORT'),
            'database' => env('DB_LOG_NAME'),
            'username' => env('DB_LOG_USERNAME'),
            'password' => env('DB_LOG_PASSWORD'),
            'options' => []
        ]
        
    ]    

];