<?php

namespace App\Models;

use App\Core\Model;

class Customer extends Model
{
    protected string $table = 'customers';

    // ─── Get all customers ordered by name ───────────────────────────
    public function allOrdered(): array
    {
        return $this->db->query(
            "SELECT * FROM customers ORDER BY name ASC"
        );
    }

    // ─── Search by name or phone ──────────────────────────────────────
    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        return $this->db->query(
            "SELECT * FROM customers
             WHERE name LIKE ? OR phone LIKE ?
             ORDER BY name ASC",
            [$like, $like]
        );
    }

    /*
    ---

    Additional Methods (extending the Base Model)

    The following methods are implemented on top of the Base Model to provide customer-specific functionality:

    allOrdered()   → Retrieve all customers ordered alphabetically by name

    search()       → Search customers by name or phone number

    */
}