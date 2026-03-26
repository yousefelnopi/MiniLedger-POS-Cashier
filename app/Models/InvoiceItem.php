<?php

namespace App\Models;

use App\Core\Model;

class InvoiceItem extends Model
{
    protected string $table = 'invoice_items';

    // ─── Get all items for a specific invoice ─────────────────────────
    public function byInvoice(int $invoiceId): array
    {
        return $this->db->query(
            "SELECT ii.*, p.sku, p.part_number
             FROM invoice_items ii
             LEFT JOIN products p ON p.id = ii.product_id
             WHERE ii.invoice_id = ?",
            [$invoiceId]
        );
    }

    // ─── Insert multiple items at once (bulk insert — single query) ───
    public function insertMany(int $invoiceId, array $items): void
    {
        $placeholders = [];
        $params       = [];

        foreach ($items as $item) {
            $placeholders[] = "(?, ?, ?, ?, ?, ?)";
            $params = array_merge($params, [
                $invoiceId,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['quantity'] * $item['unit_price'],
            ]);
        }

        $sql = "INSERT INTO invoice_items
                (invoice_id, product_id, product_name, quantity, unit_price, total_row)
                VALUES " . implode(', ', $placeholders);

        $this->db->execute($sql, $params);
    }

    /*
    ---

    Additional Methods (extending the Base Model)

    byInvoice()    → Retrieve all items for a specific invoice with SKU and part number

    insertMany()   → Bulk insert all invoice items in a single query (more efficient than looping)

    */
}