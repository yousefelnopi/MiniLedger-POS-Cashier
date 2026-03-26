<?php

namespace App\Models;

use App\Core\Model;

class Category extends Model
{
    protected string $table = 'categories';

    // ─── Get all categories ordered by name ──────────────────────────
    public function allOrdered(): array
    {
        return $this->db->query(
            "SELECT * FROM categories ORDER BY name ASC"
        );
    }

    // ─── Find by api_id — used when saving pull-data ──────────────────
    // Example: $category->findByApiId(5)
    public function findByApiId(int $apiId): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM categories WHERE api_id = ?",
            [$apiId]
        );
    }
}


/*

---

Because the model extends the base `Model` class, it automatically inherits the following methods:

all()        → Retrieve all categories
find($id)    → Retrieve a category by its local ID
findBy()     → Retrieve a category by any column
insert()     → Create a new category
update()     → Update an existing category
delete()     → Delete a category

Additional methods implemented in this model:

allOrdered()   → Retrieve all categories ordered by sort_order
findByApiId()  → Retrieve a category using the server API ID (api_id)

*/