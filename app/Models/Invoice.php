<?php

namespace App\Models;

use App\Core\Model;

class Invoice extends Model
{
    protected string $table = 'invoices';

    // ─── Get all invoices with customer name ──────────────────────────
    public function allWithCustomer(): array
    {
        return $this->db->query(
            "SELECT i.*, c.name as customer_name
             FROM invoices i
             LEFT JOIN customers c ON c.id = i.customer_id
             ORDER BY i.created_at DESC"
        );
    }

    // ─── Get all pending invoices for a specific cashier ─────────────
    public function pendingByCashier(string $email): array
    {
        return $this->db->query(
            "SELECT * FROM invoices
             WHERE is_synced = 0 AND cashier_email = ?
             ORDER BY created_at ASC",
            [$email]
        );
    }

    // ─── Count pending invoices for a specific cashier ────────────────
    public function pendingCount(string $email): int
    {
        $row = $this->db->queryOne(
            "SELECT COUNT(*) as total FROM invoices
             WHERE is_synced = 0 AND cashier_email = ?",
            [$email]
        );
        return (int) ($row['total'] ?? 0);
    }

    // ─── Mark invoice as synced ───────────────────────────────────────
    public function markSynced(string $uuid): void
    {
        $this->db->execute(
            "UPDATE invoices SET is_synced = 1 WHERE uuid = ?",
            [$uuid]
        );
    }

    // ─── Get invoice with all its items ──────────────────────────────
    public function withItems(int $id): ?array
    {
        $invoice = $this->db->queryOne(
            "SELECT i.*, c.name as customer_name
         FROM invoices i
         LEFT JOIN customers c ON c.id = i.customer_id
         WHERE i.id = ?",
            [$id]
        );

        if (!$invoice) return null;

        $invoice['items'] = $this->db->query(
            "SELECT * FROM invoice_items WHERE invoice_id = ?",
            [$id]
        );

        return $invoice;
    }

    // ─── Get today's invoices for a specific cashier ──────────────────
    public function todayByCashier(string $email): array
    {
        return $this->db->query(
            "SELECT i.*, c.name as customer_name
         FROM invoices i
         LEFT JOIN customers c ON c.id = i.customer_id
         WHERE i.cashier_email = ?
         AND date(i.created_at) = date('now')
         ORDER BY i.created_at DESC",
            [$email]
        );
    }
}

/*
---

Additional Methods (extending the Base Model)

The following methods are implemented on top of the Base Model to provide invoice-specific functionality:

allWithCustomer()      → Retrieve all invoices along with the customer name

pendingByCashier()     → Retrieve all unsynced invoices for a specific cashier email

pendingCount()         → Count unsynced invoices for a specific cashier email

markSynced()           → Mark an invoice as synced by UUID

withItems()            → Retrieve a single invoice along with all its items

*/
