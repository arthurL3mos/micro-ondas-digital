<?php

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

return [
    'app' => [
        'env' => $_ENV['APP_ENV'] ?? 'production',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
    'api' => [
        'base_path' => $_ENV['API_BASE_PATH'] ?? '/api',
    ],
    'paths' => [
        'src' => __DIR__ . '/../src',
    ],
    'auth' => [ 
        'secret_key' => $_ENV['AUTH_SECRET_KEY'] ?? '82bEfdVQoiIh6aCqF/4p8wQqaoq/OhpitY3Ydya1y78=',
        'users' => [
            'admin' => [
                'password' => $_ENV['ADMIN_PASSWORD'] ?? password_hash('senha123', PASSWORD_BCRYPT),
                'role' => 'admin'
            ],
            'user' => [
                'password' => $_ENV['USER_PASSWORD'] ?? password_hash('user123', PASSWORD_BCRYPT),
                'role' => 'user'
            ]
        ],
        'token_expiration' => $_ENV['TOKEN_EXPIRATION'] ?? 3600
    ]
];