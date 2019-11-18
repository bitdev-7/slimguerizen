<?php declare(strict_types=1);

return [
    'settings' => [
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => getenv('DISPLAY_ERROR_DETAILS'),
        'db' => [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOSTNAME'),
            'port' => getenv('DB_PORT'),
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'charset'   => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix'    => '',
            'schema' => 'public',
        ],

        // Monolog settings
        'logger'                 => [
            'name'  => getenv('APP_NAME'),
            'path'  => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        'mailer' => [
            'smtp_server' => getenv('SMTP_SERVER'),
            'smtp_port' => getenv('SMTP_PORT'),
            'smtp_user' => getenv('SMTP_USER'),
            'smtp_pwd' => getenv('SMTP_PASSWORD'),
        ],

        'app_domain' => getenv('APP_DOMAIN'),

        'auth_server' => [
            'access_lifetime' => 3 * 3600,
            'access_token_table' => 'public.oauth_access_tokens',
            'user_table' => 'public.user',
        ],

    ],
];
