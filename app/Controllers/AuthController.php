<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Controller;
use App\Core\Database;
use App\Database\Migrations\CreateTables;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\User;
use App\Services\SyncService;

class AuthController extends Controller
{
    // ─── Show login page ──────────────────────────────────────────────
    public function showLogin(): void
    {
        // If already logged in → go to dashboard
        if (Auth::check()) {
            $this->redirect('/pos/dashboard');
        }

        $this->view('auth/login');
    }

    // ─── Login process ────────────────────────────────────────────────
public function login(): void
{
    $data = $this->all();

    $v = Validator::make($data)
        ->required('email')
        ->email('email')
        ->required('password')
        ->min('password', 6);

    if ($v->fails()) {
        Response::error('Validation failed.', 422, $v->errors());
    }

    $sync   = new SyncService();
    $result = $sync->login($data['email'], $data['password']);

    if (!$result['success']) {
        Response::error($result['message'] ?? 'Invalid credentials.', 401);
    }

    $token = $result['data']['token'];
    $email = $result['data']['email'];
    $name  = $result['data']['name'];
    $apiId = $result['data']['id'];

    try {
        $dbName = explode('@', $email)[0];
        $dbPath = Config::get('db_storage') . $dbName . '.sqlite';
        $isNew  = !file_exists($dbPath);

        Database::connect($email);

        if ($isNew) {
            (new CreateTables())->run();
        }

        (new User())->saveFromLogin($apiId, $name, $email);
        Auth::login($token, $email);

        Response::success(
            ['redirect' => '/pos/loading'],
            'Logged in successfully.'
        );

    } catch (\Exception $e) {
        Response::error('Setup error: ' . $e->getMessage(), 500);
    }
}

    // ─── Logout process ───────────────────────────────────────────────
    public function logout(): void
    {
        $email        = Auth::email();
        $pendingCount = 0;

        if ($email) {
            $db           = Database::getInstance();
            $row          = $db->queryOne(
                "SELECT COUNT(*) as total FROM invoices WHERE is_synced = 0 AND cashier_email = ?",
                [$email]
            );
            $pendingCount = (int) ($row['total'] ?? 0);
        }

        // Warn if pending invoices exist
        if ($pendingCount > 0) {
            $force = $this->input('force', false);

            if (!$force) {
                Response::error(
                    "You have {$pendingCount} unsent invoice(s). logout anyway?",
                    409,
                    ['pending_count' => $pendingCount]
                );
            }
        }

        // Tell server to delete the token
        $sync = new SyncService();
        $sync->logoutFromServer();

        // Clear local session regardless of server response
        Auth::logout();

        Response::success(
            ['redirect' => '/auth/login'],
            'Logged out successfully.'
        );
    }
}

/*
Authentication Flow

─────────────────────────────────────────
Login Process
─────────────────────────────────────────

login()
    ↓
Validate input (email, password)
    ↓
SyncService::login() → Send credentials to server
    ↓
Server returns: token, email, name, id
    ↓
First login? (cashier.sqlite does not exist)
    ↓ Yes                          ↓ No
Database::connect(email)      Database::connect(email)
CreateTables::run()                ↓
    ↓                         Skip migrations
User::saveFromLogin()
    ↓
Auth::login(token, email) → Save to session.json
    ↓
SyncService::pullData() → Save categories, products, customers, car_models to SQLite
    ↓
Redirect → /pos/dashboard


─────────────────────────────────────────
Logout Process
─────────────────────────────────────────

logout()
    ↓
Check pending invoices for current cashier email
    ↓
Pending invoices exist?
    ↓ Yes                          ↓ No
force = true?             SyncService::logoutFromServer()
    ↓ No                           ↓
Return 409                  Auth::logout()
+ pending_count                    ↓
                            Redirect → /auth/login
    ↓ Yes
SyncService::logoutFromServer() → Server deletes token
    ↓
Auth::logout() → Clear session.json
    ↓
Redirect → /auth/login

*/
