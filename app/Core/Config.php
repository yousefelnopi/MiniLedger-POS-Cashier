<?php

namespace App\Core;

class Config
{
    // Stores config data after first load
    private static ?array $config = null;

    // Load config file once only
    private static function load(): void
    {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/app.php';
        }
    }

    // ─── Get value by key ─────────────────────────────────────────────
    // Example: Config::get('server_url')
    // Example: Config::get('debug', false)
    public static function get(string $key, mixed $default = null): mixed
    {
        self::load();
        return self::$config[$key] ?? $default;
    }

    // ─── Get all config values ────────────────────────────────────────
    public static function all(): array
    {
        self::load();
        return self::$config;
    }
}