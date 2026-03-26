<?php

namespace App\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';

    // ─── Get all active products ──────────────────────────────────────
    public function allActive(): array
    {
        return $this->db->query(
            "SELECT p.*, c.name as category_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.is_active = 1
             ORDER BY p.name ASC"
        );
    }

    // ─── Get active products by category ─────────────────────────────
    public function byCategory(int $categoryId): array
    {
        return $this->db->query(
            "SELECT * FROM products
             WHERE category_id = ? AND is_active = 1
             ORDER BY name ASC",
            [$categoryId]
        );
    }

    // ─── Search by name, sku, part_number, or car model brand/model ───
    public function search(string $term, int $categoryId = 0): array
    {
        $words  = array_filter(explode(' ', trim($term)));
        $params = [];
        $conditions = [];

        foreach ($words as $word) {
            $like = '%' . $word . '%';
            $conditions[] = "(
            p.name        LIKE ? OR
            p.sku         LIKE ? OR
            p.part_number LIKE ? OR
            cm.brand      LIKE ? OR
            cm.model      LIKE ? OR
            cm.year       LIKE ? OR
            cm.engine     LIKE ? OR
            cm.trim       LIKE ?
        )";
            $params = array_merge($params, array_fill(0, 8, $like));
        }

        $sql = "SELECT DISTINCT p.*, c.name as category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN car_model_product cmp ON cmp.product_id = p.id
            LEFT JOIN car_models cm ON cm.id = cmp.car_model_id
            WHERE p.is_active = 1
            AND " . implode(' AND ', $conditions);

        if ($categoryId > 0) {
            $sql     .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY p.name ASC";

        return $this->db->query($sql, $params);
    }

    // ─── Get compatible car models for a product ──────────────────────
    public function carModels(int $productId): array
    {
        return $this->db->query(
            "SELECT cm.*
             FROM car_models cm
             JOIN car_model_product cmp ON cmp.car_model_id = cm.id
             WHERE cmp.product_id = ?",
            [$productId]
        );
    }

    // ─── Check if product has enough stock ────────────────────────────
    public function hasStock(int $productId, int $quantity): bool
    {
        $product = $this->find($productId);
        if (!$product) return false;
        return $product['stock_qty'] >= $quantity;
    }

    // ─── Check if stock is below alert threshold ──────────────────────
    public function isLowStock(int $productId): bool
    {
        $product = $this->find($productId);
        if (!$product) return false;
        return $product['stock_qty'] <= $product['alert_stock'];
    }

    // ─── Get product with car models ──────────────────────────────────
    public function findWithCarModels(int $id): ?array
    {
        $product = $this->find($id);
        if (!$product) return null;

        $product['car_models'] = $this->db->query(
            "SELECT cm.* FROM car_models cm
         JOIN car_model_product cmp ON cmp.car_model_id = cm.id
         WHERE cmp.product_id = ?",
            [$id]
        );

        return $product;
    }
}


    /*

    ---

    Additional Methods (extending the Base Model)

    The following methods are implemented on top of the Base Model to provide product-specific functionality:

    allActive()    → Retrieve all active products along with their category name

    byCategory()   → Retrieve all products belonging to a specific category

    search()       → Search products by name, SKU, or part_number

    carModels()    → Retrieve all car models compatible with the product

    hasStock()     → Check if the product has sufficient stock available

    isLowStock()   → Determine whether the product stock is below the alert threshold

    */