<?php
return [
    'app' => [
        'name' => 'Commercial Web Counter',
        'base_url' => 'https://counter.example.com',
        'timezone' => 'Asia/Shanghai',
        'allow_registration' => true,
    ],

    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'counter_saas',
        'username' => 'counter_user',
        'password' => 'change-me',
        'charset' => 'utf8mb4',
    ],

    'security' => [
        'cors_allowed_origins' => ['*'],
        'cooldown_seconds' => 600,
        'trust_proxy_headers' => false,
        'store_raw_ip' => true,
        'visitor_hash_salt' => 'replace-with-random-secret',
        'session_name' => 'COUNTERSESSID',
    ],
];
