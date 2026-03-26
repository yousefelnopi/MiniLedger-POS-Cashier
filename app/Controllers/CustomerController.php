<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Helpers\UUID;
use App\Core\Controller;
use App\Helpers\Response;
use App\Helpers\Validator;
use App\Services\SyncService;
use App\Core\Database;

class CustomerController extends Controller
{
    public function store(): void
    {
        $this->requireAuth();

        $data = $this->all();

        $v = Validator::make($data)->required('name');

        if ($v->fails()) {
            Response::error('Validation failed.', 422, $v->errors());
        }

        $name  = htmlspecialchars(trim($data['name']));
        $phone = htmlspecialchars(trim($data['phone'] ?? ''));
        $phone = str_replace('+2', '', $phone);

        $uuid = UUID::generate();

        $sync   = new SyncService();
        $result = $sync->createCustomer([
            'uuid'  => $uuid,
            'name'  => $name,
            'phone' => $phone ?: null,
        ]);

        $db    = Database::getInstance();
        
        // ← لو السيرفر رفض (422 أو أي error) أو مفيش نت
        if (empty($result['success'])) {

            $db->execute(
                "INSERT OR IGNORE INTO customers (api_id, name, phone) VALUES (?, ?, ?)",
                [null, $name, $phone ?: null]
            );

            // نجيب الـ ID اللي لسه محفوظ دلوقتي
            $localId = $db->lastInsertId();
            if (!$localId) {
                // لو العميل متسجل قبل كده (بسبب IGNORE) نجيب الـ ID بتاعه
                $localId = $db->queryOne("SELECT id FROM customers WHERE name = ? AND phone = ?", [$name, $phone ?: null])['id'] ?? null;
            }

            // 💡 التعديل الأهم: نرجع success مع الـ ID عشان الفاتورة تشوفه!
            Response::success([
                'id'    => $localId,
                'name'  => $name,
                'phone' => $phone ?: null,
            ], 'Customer saved locally.', 201);
            
            return;
        }

        // ← السيرفر قبل — احفظ محلياً بـ api_id الحقيقي
        $apiId = $result['data']['id'] ?? null;

        $db->execute(
            "INSERT OR IGNORE INTO customers (api_id, name, phone) VALUES (?, ?, ?)",
            [$apiId, $name, $phone ?: null]
        );

        $localId = $db->queryOne(
            "SELECT id FROM customers WHERE api_id = ?",
            [$apiId]
        )['id'] ?? null;

        Response::success([
            'id'    => $localId,
            'name'  => $name,
            'phone' => $phone ?: null,
        ], 'Customer added successfully.', 201);
    }
}