<?php

namespace App\Models;

use App\Core\Model;

class CarModel extends Model
{
    protected string $table = 'car_models';

    // ─── Get all car models ordered by brand ─────────────────────────
    public function allOrdered(): array
    {
        return $this->db->query(
            "SELECT * FROM car_models ORDER BY brand ASC, model ASC"
        );
    }

    // ─── Search by brand, model, or year ─────────────────────────────
    public function search(string $term): array
    {
        $like = '%' . $term . '%';
        return $this->db->query(
            "SELECT * FROM car_models
             WHERE brand LIKE ? OR model LIKE ? OR year LIKE ?
             ORDER BY brand ASC",
            [$like, $like, $like]
        );
    }

    // ─── Get all products compatible with a car model ─────────────────
    public function products(int $carModelId): array
    {
        return $this->db->query(
            "SELECT p.*
             FROM products p
             JOIN car_model_product cmp ON cmp.product_id = p.id
             WHERE cmp.car_model_id = ? AND p.is_active = 1
             ORDER BY p.name ASC",
            [$carModelId]
        );
    }

    /*
    ---

    Additional Methods (extending the Base Model)

    The following methods are implemented on top of the Base Model to provide car model-specific functionality:

    allOrdered()   → Retrieve all car models ordered by brand and model name

    search()       → Search car models by brand, model name, or year

    products()     → Retrieve all active products compatible with a specific car model

    */
}
