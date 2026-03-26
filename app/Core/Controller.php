<?php

namespace App\Core;

class Controller
{
    // ─── Load a view file and pass data to it ────────────────────────
    // Example: $this->view('auth/login', ['error' => 'Wrong password'])
    protected function view(string $view, array $data = []): void
    {
        extract($data);

        $viewPath = __DIR__ . '/../../views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            $this->abort(500, "View [{$view}] not found.");
            return;
        }

        require $viewPath;
    }

    // ─── Return JSON response ─────────────────────────────────────────
    // Example: $this->json(['status' => 'success', 'data' => $data])
    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ─── Redirect to another page ─────────────────────────────────────
    // Example: $this->redirect('/auth/login')
    protected function redirect(string $path): void
    {
        $base = Config::get('base_url', '');
        header('Location: ' . $base . $path);
        exit;
    }

    // ─── Return error response ────────────────────────────────────────
    protected function abort(int $code, string $message): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }

    // ─── Get one input value from POST, GET, or JSON body ────────────
    // Example: $this->input('email')
    protected function input(string $key, mixed $default = null): mixed
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (isset($body[$key]))  return $body[$key];
        if (isset($_POST[$key])) return $_POST[$key];
        if (isset($_GET[$key]))  return $_GET[$key];
        return $default;
    }

    // ─── Get all input values at once ────────────────────────────────
    protected function all(): array
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        return array_merge($_GET, $_POST, $body);
    }

    // ─── Check if user is logged in, redirect to login if not ────────
    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/auth/login');
        }
    }
}