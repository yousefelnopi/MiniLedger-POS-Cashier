<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected string $table = 'users';

    // ─── Find by email ────────────────────────────────────────────────
    public function findByEmail(string $email): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM users WHERE email = ?",
            [$email]
        );
    }

    // ─── Save or update cashier after login ───────────────────────────
    // Called once after successful login from server
    public function saveFromLogin(int $apiId, string $name, string $email): void
    {
        $exists = $this->findByEmail($email);

        if ($exists) {
            $this->update($exists['id'], [
                'name' => $name,
            ]);
        } else {
            $this->insert([
                'api_id' => $apiId,
                'name'   => $name,
                'email'  => $email,
            ]);
        }
    }

    /*
    ---

    Additional Methods (extending the Base Model)

    The following methods are implemented on top of the Base Model to provide user-specific functionality:

    findByEmail()      → Retrieve a cashier record by email address

    saveFromLogin()    → Insert or update cashier data after a successful login from the server

    */
}
