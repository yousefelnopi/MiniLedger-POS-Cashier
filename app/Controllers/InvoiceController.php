<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Response;
use App\Helpers\UUID;
use App\Helpers\Validator;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\CartService;
use App\Services\SyncService;

class InvoiceController extends Controller
{
    // ─── Show all invoices ────────────────────────────────────────────
    public function index(): void
    {
        $this->requireAuth();

        $invoice  = new Invoice();
        $invoices = $invoice->allWithCustomer();

        $this->view('pos/invoices', compact('invoices'));
    }

    // ─── Store invoice locally in SQLite ──────────────────────────────
    public function store(): void
    {
        $this->requireAuth();

        // Cart must not be empty
        if (CartService::isEmpty()) {
            Response::error('Cart is empty. Add products first.', 422);
        }

        $data = $this->all();

        // Validate input
        $v = Validator::make($data)
            ->in('type', ['sale', 'return']);

        if ($v->fails()) {
            Response::error('Validation failed.', 422, $v->errors());
        }

        $cart     = CartService::getCart();
        $discount = (float) ($data['discount'] ?? 0);
        $tax      = (float) ($data['tax']      ?? 0);

        // Calculate totals
        $subtotal      = CartService::total();
        $afterDiscount = max($subtotal - $discount, 0);
        $taxAmount     = ($afterDiscount * $tax) / 100;
        $totalAmount   = $afterDiscount + $taxAmount;

        $uuid = UUID::generate();
        $db   = Database::getInstance();

        $db->beginTransaction();

        try {
            // 💡 1. تأمين رقم العميل لمنع أخطاء الـ Foreign Key
            $customerId = null;
            if (isset($data['customer_id']) && is_numeric($data['customer_id']) && (int)$data['customer_id'] > 0) {
                $customerId = (int) $data['customer_id'];
            }

            // 2. Save invoice
            $invoice   = new Invoice();
            $invoiceType = $data['type'] ?? 'sale';

            $invoiceId = $invoice->insert([
                'uuid'          => $uuid,
                'cashier_email' => Auth::email(),
                'customer_id'   => $customerId, // 💡 استخدام المتغير المؤمن
                'discount'      => $discount,
                'tax'           => $taxAmount,
                'total_amount'  => $totalAmount,
                'type'          => $invoiceType,
                'is_synced'     => 0,
            ]);

            // 3. Save invoice items
            $invoiceItem = new InvoiceItem();
            $invoiceItem->insertMany($invoiceId, array_values($cart));

            // 💡 4. Update local stock (تحديث المخزون محلياً)
            foreach ($cart as $item) {
                $qty = (int) $item['quantity'];
                $pid = (int) $item['product_id'];

                if ($invoiceType === 'sale') {
                    // خصم من المخزون في حالة البيع
                    $db->execute("UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?", [$qty, $pid]);
                } elseif ($invoiceType === 'return') {
                    // تزويد المخزون في حالة المرتجع
                    $db->execute("UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?", [$qty, $pid]);
                }
            }

            // 5. Create sync log entry
            $db->execute(
                "INSERT INTO sync_log (invoice_uuid, status, attempts) VALUES (?, 'pending', 0)",
                [$uuid]
            );

            $db->commit();

            // Clear cart after saving
            CartService::clear();

            // Try to sync immediately if server is available
            $sync       = new SyncService();
            $syncResult = $sync->syncPending();

            // Get updated pending count
            $pendingCount = $invoice->pendingCount(Auth::email());

            Response::success([
                'invoice_id'       => $invoiceId,
                'uuid'             => $uuid,
                'total_amount'     => $totalAmount,
                'pending_count'    => $pendingCount,
                'synced'           => $syncResult['synced'],
                'failed'           => $syncResult['failed'],
                'server_available' => $syncResult['online'],
                'server_message'   => $syncResult['message'],
            ], 'Invoice saved successfully.');
        } catch (\Exception $e) {
            $db->rollback();
            Response::error('Failed to save invoice: ' . $e->getMessage(), 500);
        }
    }

    // ─── Show invoice preview after save ──────────────────────────────
    public function preview(int $id): void
    {
        $this->requireAuth();

        $invoice = new Invoice();
        $data    = $invoice->withItems($id);

        if (!$data) {
            $this->abort(404, 'Invoice not found.');
        }

        // Make sure cashier owns this invoice
        if ($data['cashier_email'] !== \App\Core\Auth::email()) {
            $this->abort(403, 'Access denied.');
        }

        $this->view('pos/invoice-preview', ['invoice' => $data]);
    }
}
/*
Invoice Creation Flow

POST /invoice/store
    ↓
Check if the cart is empty
    → If empty: return error response
    ↓
Validate invoice type (sale / return)
    ↓
Calculate invoice totals
    subtotal → discount → tax → total
    ↓
Generate unique invoice identifier
    UUID::generate()
    ↓
Begin database transaction
    ↓
Insert invoice record
    Invoice::insert()
    ↓
Insert invoice items
    InvoiceItem::insertMany()
    ↓
Create sync log entry
    sync_log → status = "pending"
    ↓
Commit transaction
    ↓
Clear the cart
    CartService::clear()
    ↓
Try immediate sync
    SyncService::syncPending()
    ↓
server_available = true        server_available = false
synced > 0                     online = false
    ↓                              ↓
"Invoice sent                  "Server unavailable.
 successfully."                 Invoice saved locally."
    ↓                              ↓
failed > 0                     pending_count++
    ↓                          will sync later
"Server error:                 via run() or auto sync
 [message]"
*/
