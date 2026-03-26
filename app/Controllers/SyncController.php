<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Models\Invoice;
use App\Services\SyncService;

class SyncController extends Controller
{
    // ─── Manual sync — triggered by button ───────────────────────────
    public function run(): void
    {
        $this->requireAuth();

        $email        = Auth::email();
        $invoice      = new Invoice();
        $pendingCount = $invoice->pendingCount($email);

        if ($pendingCount === 0) {
            Response::success([
                'synced'           => 0,
                'failed'           => 0,
                'pending_count'    => 0,
                'server_available' => true,
                'server_message'   => '',
            ], 'No pending invoices.');
        }

        $sync   = new SyncService();
        $result = $sync->syncPending();

        Response::success([
            'synced'           => $result['synced'],
            'failed'           => $result['failed'],
            'pending_count'    => $invoice->pendingCount($email),
            'server_available' => $result['online'],
            'server_message'   => $result['message'],
        ], 'Sync completed.');
    }

    // ─── Pull data from server ────────────────────────────────────────
    public function pull(): void
    {
        $this->requireAuth();

        // تحقق من الفواتير المعلقة الأول
        $invoice      = new Invoice();
        $pendingCount = $invoice->pendingCount(Auth::email());

        if ($pendingCount > 0) {
            Response::error(
                'You have ' . $pendingCount . ' unsynced invoices. Sync them first before pulling new data.',
                409,
                ['pending_count' => $pendingCount]
            );
            return;
        }

        $sync   = new SyncService();
        $result = $sync->pullData();

        if ($result) {
            Response::success(['redirect' => '/pos/dashboard'], 'Data loaded successfully.');
        } else {
            Response::error('Failed to load data. Check server connection.', 500);
        }
    }


    // ─── Get pending invoices count — used by JS polling ─────────────
    public function status(): void
    {
        // Require authentication before accessing this method
        $this->requireAuth();

        // Get the email of the currently authenticated user
        
        $email        = Auth::email();
        $invoice      = new Invoice();
        $pendingCount = $invoice->pendingCount($email);
        $pending      = $invoice->pendingByCashier($email);

        Response::success([
            'pending_count' => $pendingCount,
            'pending'       => $pending,
        ], 'OK');
    }
    /*******  3b93143e-5c6a-495d-973d-1477d8906ad2  *******/
    // ─── Request stock adjustment ─────────────────────────────────────
    public function requestAdjust(): void
    {
        $this->requireAuth();

        $data = $this->all();

        // 1. التحقق من البيانات اللي جاية من الجافاسكريبت
        $v = Validator::make($data)
            ->required('product_id')
            ->required('type')
            ->required('quantity')
            ->required('reason');

        if ($v->fails()) {
            Response::error('Validation failed.', 422, $v->errors());
        }

        $db = Database::getInstance();

        // 2. 💡 الأهم: نجيب الـ api_id بتاع المنتج من الداتابيز المحلية عشان السيرفر يفهمه
        $product = $db->queryOne(
            "SELECT api_id FROM products WHERE id = ?",
            [(int) $data['product_id']]
        );

        if (!$product || !$product['api_id']) {
            Response::error('Product not found or not synced with server.', 404);
        }

        // 3. نبعت الطلب للسيرفر المركزي باستخدام SyncService
        $sync = new SyncService();
        $result = $sync->requestAdjust([
            'product_id' => $product['api_id'], // بنبعت الـ ID الخاص بالسيرفر
            'type'       => $data['type'], // addition أو deduction
            'quantity'   => (int) $data['quantity'],
            'reason'     => htmlspecialchars(trim($data['reason']))
        ]);

        // 4. نرد على الفرونت إند (ملف product.php)
        if (!empty($result['success'])) {
            Response::success([], 'Request sent to admin successfully.');
        } else {
            // لو السيرفر واقع أو رفض الطلب
            Response::error($result['message'] ?? 'Failed to send request to server. Please check your connection.', 500);
        }
    }
}

/**
 * 
 * Sync Routes

    POST /sync/run -> Button Send Request Manually
        → Triggers a manual synchronization with the server.
        Used when the cashier presses the "Sync" button in the POS interface.

    GET /sync/status -> Button Send Request Automatically After 30 Second 
        → Returns the current synchronization status.
        Typically called by the frontend using periodic polling
        (e.g., every 30 seconds via JavaScript).
 */
