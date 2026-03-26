<?php

namespace App\Core;

class Auth
{
    // ─── Get session file path ────────────────────────────────────────
    private static function path(): string
    {
        return Config::get('session_path');
    }

    // ─── Save token and email after login ─────────────────────────────

    // Saves session to disk as JSON file instead of $_SESSION
    // This ensures the cashier stays logged in even if the browser is closed or XAMPP restarts
    public static function login(string $token, string $email): void
    {
        $data = [
            'token'        => $token,
            'email'        => $email,
            'logged_in_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents(
            self::path(),
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    // ─── Clear token on logout ────────────────────────────────────────
    public static function logout(): void
    {
        $path = self::path();
        if (file_exists($path)) {
            file_put_contents($path, json_encode([]));
        }
    }

    // ─── Check if cashier is logged in ───────────────────────────────
    public static function check(): bool
    {
        $session = self::read();
        return !empty($session['token']);
    }

    // ─── Get current token ────────────────────────────────────────────
    public static function token(): ?string
    {
        return self::read()['token'] ?? null;
    }

    // ─── Get cashier email ────────────────────────────────────────────
    // Used to match pending invoices on next login
    public static function email(): ?string
    {
        return self::read()['email'] ?? null;
    }

    // ─── Read session.json file ───────────────────────────────────────
    private static function read(): array
    {
        $path = self::path();

        if (!file_exists($path)) return [];

        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : [];
    }
}



/*

    session.json content:
    {
    "token":        "3|abc123",
    "email":        "ahmed@pos.com",
    "logged_in_at": "2026-03-14 10:00:00"
    }
    
 */