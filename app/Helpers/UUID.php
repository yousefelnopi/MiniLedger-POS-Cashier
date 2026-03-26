<?php

namespace App\Helpers;

class UUID
{
    // ─── Generate a unique UUID v4 ────────────────────────────────────
    // Example: UUID::generate() → "550e8400-e29b-41d4-a716-446655440000"
    public static function generate(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ─── Check if a string is a valid UUID ────────────────────────────
    // Example: UUID::isValid('550e8400-e29b-41d4-a716-446655440000') → true
    public static function isValid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}