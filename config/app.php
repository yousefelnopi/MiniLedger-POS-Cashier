<?php
date_default_timezone_set('Africa/Cairo');
return [

    // Project name
    'app_name' => 'Pos-Cashier',

    // Debug mode — true = show errors / false = hide errors
    'debug' => true,

    // Laravel API base URL — change the IP if server is on another device in LAN
    'server_url' => 'http://127.0.0.1:8000/api',

    // Project base URL on XAMPP
    'base_url' => '/MiniLedger/CASHIER/public',

    // Storage path for SQLite databases
    'db_storage' => __DIR__ . '/../storage/database/',

    // Session file path — stores the token after login
    'session_path' => __DIR__ . '/../storage/cache/session.json',

    // Log file path — records all sync attempts
    'log_path' => __DIR__ . '/../storage/logs/sync.log',

];