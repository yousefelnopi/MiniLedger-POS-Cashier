<?php

namespace App\Helpers;

class Response
{
    // ─── Success response ─────────────────────────────────────────────
    // Example: Response::success('Invoice saved', $data)
    public static function success(mixed $data = null, string $message = 'Request successful', int $code = 200): void
    {
        self::send([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    // ─── Error response ───────────────────────────────────────────────
    // Example: Response::error('Product not found', 404)
    public static function error(string $message = 'An error occurred', int $code = 400, mixed $errors = null): void
    {
        $response = [
            'status'  => 'error',
            'message' => $message,
        ];

        // Add errors array only if it exists (for validation errors)
        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        self::send($response, $code);
    }

    // ─── Send JSON response and stop execution ────────────────────────
    private static function send(array $data, int $code): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}