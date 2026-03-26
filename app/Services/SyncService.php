<?php

namespace App\Services;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Database;

class SyncService
{
    private string $baseUrl; // api path 
    private string $storageUrl; // storage path To Downlad Images

    public function __construct()
    {
        $this->baseUrl    = Config::get('server_url');  // Get API base URL
        
        // بناء URL الصور من server_url
        // http://127.0.0.1:8000/api → http://127.0.0.1:8000/storage
        
        $this->storageUrl = str_replace('/api', '/storage', $this->baseUrl); // get storage url
    }

    // ─── Get database instance ────────────────────────────────────────
    private function getDb(): Database  // Connection of database
    {
        return Database::getInstance(); // Database::connect('ahmed@gmail.com')
    }

    // ─── Login — send credentials to server ──────────────────────────
    public function login(string $email, string $password): array  // login('ahmed@gmail.com', '123456') to server
    {
        return $this->request('POST', '/cashier/login', [  // Specific for send request to Server in correct way
            'email'    => $email,
            'password' => $password,
        ], false);
    }

    // ─── Logout — tell server to delete token ────────────────────────
    public function logoutFromServer(): array
    {
        return $this->request('POST', '/cashier/logout');  // The Same request but for logout
    }

    // ─── Pull all data from server and save to SQLite ─────────────────
    public function pullData(): bool   // pull data from server 
    {
        $result = $this->request('GET', '/pull-data');  // send the request and get the response

        if (!$result['success']) return false;  // if the request failed

        $data = $result['data']; // put the data in variable

        $db   = $this->getDb();  // get the database

        // ← الـ PRAGMA لازم يكون قبل الـ transaction وعلى الـ PDO مباشرة
        $db->getConnection()->exec('PRAGMA foreign_keys = OFF');  // Make foreign keys off to avoid errors when deleting

        $db->beginTransaction(); // start transaction
        try { //delete all data and save new data

            $db->execute("DELETE FROM car_model_product");
            $db->execute("DELETE FROM car_models");
            $db->execute("DELETE FROM products");
            $db->execute("DELETE FROM categories");
            $db->execute("DELETE FROM customers");

            $this->saveCategories($data['categories'] ?? []);
            $this->saveCarModels($data['car_models']  ?? []);
            $this->saveProducts($data['products']     ?? []);
            $this->saveCustomers($data['customers']   ?? []);

            $db->commit(); // commit transaction
            $db->getConnection()->exec('PRAGMA foreign_keys = ON'); // Make foreign keys on
            return true;
        } catch (\Exception $e) {
            $db->rollback();
            $db->getConnection()->exec('PRAGMA foreign_keys = ON');
            $this->log('pullData error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Sync pending invoices to server ──────────────────────────────
    public function syncPending(): array // Check Pending Invoices and send to server
    {
        $db       = $this->getDb(); // get the database
        
        $email    = Auth::email(); // get the email
        
        $invoices = $db->query( // get the pending invoices

            "SELECT * FROM invoices WHERE is_synced = 0 AND cashier_email = ? ORDER BY created_at ASC",
            [$email]
        );

        if (empty($invoices)) { // if there are no pending invoices
            return [
                'synced'  => 0,
                'failed'  => 0,
                'online'  => true,
                'message' => 'No pending invoices.',
            ];
        }

        $batch = [];
        foreach ($invoices as $invoice) {
            $items = $db->query(
                "SELECT ii.*, p.api_id as product_api_id
                 FROM invoice_items ii
                 JOIN products p ON p.id = ii.product_id
                 WHERE ii.invoice_id = ?",
                [$invoice['id']]
            );

            $batch[] = [
                'uuid'        => $invoice['uuid'],
                'type'        => $invoice['type'],
                'customer_id' => $this->resolveCustomerApiId($invoice['customer_id']),
                'discount'    => $invoice['discount'],
                'tax'         => $invoice['tax'],
                'items'       => array_map(fn($item) => [
                    'product_id' => $item['product_api_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ], $items),
            ];
        }

        $result = $this->request('POST', '/sync/sale', ['invoices' => $batch]);

        $synced = 0;
        $failed = 0;

        if ($result['success']) {
            foreach ($result['data']['saved'] ?? [] as $uuid) {
                $db->execute(
                    "UPDATE invoices SET is_synced = 1 WHERE uuid = ?",
                    [$uuid]
                );
                $db->execute(
                    "UPDATE sync_log SET status = 'synced', last_attempt_at = ?
                     WHERE invoice_uuid = ?",
                    [date('Y-m-d H:i:s'), $uuid]
                );
                $synced++;
            }
            foreach ($result['data']['failed'] ?? [] as $err) {
                // ← ضيف السطر ده بس
                $this->log('Invoice failed: uuid=' . ($err['uuid'] ?? '?') . ' | error=' . ($err['error'] ?? 'unknown'));

                $db->execute(
                    "UPDATE sync_log SET status = 'failed', error_message = ?,
         attempts = attempts + 1, last_attempt_at = ?
         WHERE invoice_uuid = ?",
                    [$err['error'] ?? 'Unknown error', date('Y-m-d H:i:s'), $err['uuid']]
                );
                $failed++;
            }
        } else {
            foreach ($invoices as $invoice) {
                $db->execute(
                    "UPDATE sync_log SET attempts = attempts + 1,
                     last_attempt_at = ?, error_message = ?
                     WHERE invoice_uuid = ?",
                    [date('Y-m-d H:i:s'), $result['message'] ?? 'Server unavailable', $invoice['uuid']]
                );
                $failed++;
            }
        }

        $this->log("Sync done — synced: {$synced}, failed: {$failed}");

        return [
            'synced'  => $synced,
            'failed'  => $failed,
            'online'  => $result['success'],
            'message' => $result['message'] ?? '',
        ];
    }

    // ─── Check product availability before invoice store ──────────────
    public function checkAvailability(array $items): array
    {
        $result = $this->request('POST', '/sync/check', [ // ← هنا
            'items' => $items,
        ]);

        if (!$result['success']) {
            return ['all_available' => true, 'results' => []];
        }

        $results      = $result['data'] ?? [];
        $allAvailable = !in_array(false, array_column($results, 'available'), true);

        return [
            'all_available' => $allAvailable,
            'results'       => $results,
        ];
    }

    // ─── Request stock adjustment from cashier ────────────────────────
    public function requestAdjust(array $data): array
    {
        $result = $this->request('POST', '/sync/adjust-request', $data);

        // مؤقتاً للـ debug
        $this->log('requestAdjust result: ' . json_encode($result));

        return $result;
    }

    // ─── Create customer ────────────────────────────────────
    public function createCustomer(array $data): array
    {
        $result = $this->request('POST', '/customers', $data);
        $this->log('createCustomer result: ' . json_encode($result));
        return $result;
    }

    
    // ─── Save categories to SQLite ────────────────────────────────────
    private function saveCategories(array $categories): void
    {
        $db = $this->getDb();
        foreach ($categories as $cat) {
            // Download image if exists
            $localImage = null;
            if (!empty($cat['image'])) {
                $localImage = $this->downloadImage(
                    $cat['image'],
                    'categories'
                );
            }

            $exists = $db->queryOne(
                "SELECT id FROM categories WHERE api_id = ?",
                [$cat['id']]
            );

            if ($exists) {
                $db->execute(
                    "UPDATE categories SET name = ?, image = ? WHERE api_id = ?",
                    [$cat['name'], $localImage ?? $exists['image'] ?? null, $cat['id']]
                );
            } else {
                $db->execute(
                    "INSERT INTO categories (api_id, name, image) VALUES (?, ?, ?)",
                    [$cat['id'], $cat['name'], $localImage]
                );
            }
        }
    }

    // ─── Save products to SQLite ──────────────────────────────────────
    private function saveProducts(array $products): void
    {
        $db = $this->getDb();
        foreach ($products as $p) {
            $category        = $db->queryOne(
                "SELECT id FROM categories WHERE api_id = ?",
                [$p['category_id'] ?? 0]
            );
            $localCategoryId = $category['id'] ?? null;

            // Download image if exists
            $localImage = null;
            if (!empty($p['image'])) {
                $localImage = $this->downloadImage(
                    $p['image'],
                    'products'
                );
            }

            $exists = $db->queryOne(
                "SELECT id, image FROM products WHERE api_id = ?",
                [$p['id']]
            );

            if ($exists) {
                $db->execute(
                    "UPDATE products SET
                        name = ?, sku = ?, part_number = ?, sell_price = ?,
                        stock_qty = ?, alert_stock = ?, location_code = ?,
                        is_active = ?, category_id = ?, image = ?
                     WHERE api_id = ?",
                    [
                        $p['name'],
                        $p['sku'],
                        $p['part_number']  ?? null,
                        $p['sell_price'],
                        $p['stock_qty'],
                        $p['alert_stock'],
                        $p['location_code'] ?? null,
                        $p['is_active']    ?? 1,
                        $localCategoryId,
                        $localImage ?? $exists['image'] ?? null,
                        $p['id'],
                    ]
                );
            } else {
                $db->execute(
                    "INSERT INTO products
                        (api_id, category_id, name, sku, part_number, sell_price,
                         stock_qty, alert_stock, location_code, is_active, image)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $p['id'],
                        $localCategoryId,
                        $p['name'],
                        $p['sku'],
                        $p['part_number']  ?? null,
                        $p['sell_price'],
                        $p['stock_qty'],
                        $p['alert_stock'],
                        $p['location_code'] ?? null,
                        $p['is_active']    ?? 1,
                        $localImage,
                    ]
                );
            }

            // Save car model compatibility (pivot)
            $localProduct = $db->queryOne(
                "SELECT id FROM products WHERE api_id = ?",
                [$p['id']]
            );

            if ($localProduct) {
                foreach ($p['car_models'] ?? [] as $cm) {
                    $localCarModel = $db->queryOne(
                        "SELECT id FROM car_models WHERE api_id = ?",
                        [$cm['id']]
                    );
                    if ($localCarModel) {
                        $pivotExists = $db->queryOne(
                            "SELECT 1 FROM car_model_product
                             WHERE car_model_id = ? AND product_id = ?",
                            [$localCarModel['id'], $localProduct['id']]
                        );
                        if (!$pivotExists) {
                            $db->execute(
                                "INSERT INTO car_model_product (car_model_id, product_id)
                                 VALUES (?, ?)",
                                [$localCarModel['id'], $localProduct['id']]
                            );
                        }
                    }
                }
            }
        }
    }

    // ─── Save customers to SQLite ─────────────────────────────────────
    private function saveCustomers(array $customers): void
    {
        $db = $this->getDb();
        foreach ($customers as $c) {
            $exists = $db->queryOne(
                "SELECT id FROM customers WHERE api_id = ?",
                [$c['id']]
            );
            if ($exists) {
                $db->execute(
                    "UPDATE customers SET name = ?, phone = ? WHERE api_id = ?",
                    [$c['name'], $c['phone'] ?? null, $c['id']]
                );
            } else {
                $db->execute(
                    "INSERT INTO customers (api_id, name, phone) VALUES (?, ?, ?)",
                    [$c['id'], $c['name'], $c['phone'] ?? null]
                );
            }
        }
    }

    // ─── Save car models to SQLite ────────────────────────────────────
    private function saveCarModels(array $carModels): void
    {
        $db = $this->getDb();
        foreach ($carModels as $cm) {
            $exists = $db->queryOne(
                "SELECT id FROM car_models WHERE api_id = ?",
                [$cm['id']]
            );
            if ($exists) {
                $db->execute(
                    "UPDATE car_models SET brand=?, model=?, year=?, engine=?, trim=?
                     WHERE api_id=?",
                    [
                        $cm['brand'],
                        $cm['model'],
                        $cm['year']   ?? null,
                        $cm['engine'] ?? null,
                        $cm['trim']   ?? null,
                        $cm['id']
                    ]
                );
            } else {
                $db->execute(
                    "INSERT INTO car_models (api_id, brand, model, year, engine, trim)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [
                        $cm['id'],
                        $cm['brand'],
                        $cm['model'],
                        $cm['year']   ?? null,
                        $cm['engine'] ?? null,
                        $cm['trim']   ?? null
                    ]
                );
            }
        }
    }

    // ─── Download image from server and save locally ──────────────────
    // Example: categories/image.jpg → storage/images/categories/image.jpg
    private function downloadImage(string $imagePath, string $type): ?string
    {
        try {
            $imageUrl  = $this->storageUrl . '/' . $imagePath;
            $filename  = basename($imagePath);
            $localDir  = __DIR__ . '/../../storage/images/' . $type . '/';
            $localPath = $localDir . $filename;

            // Create directory if not exists
            if (!is_dir($localDir)) {
                mkdir($localDir, 0755, true);
            }

            // Skip if already downloaded
            if (file_exists($localPath)) {
                return 'storage/images/' . $type . '/' . $filename;
            }

            // Download image
            $ch = curl_init($imageUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $imageData = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $imageData) {
                file_put_contents($localPath, $imageData);
                return 'storage/images/' . $type . '/' . $filename;
            }

            return null;
        } catch (\Exception $e) {
            $this->log('Image download error: ' . $e->getMessage());
            return null;
        }
    }

    // ─── cURL request helper ──────────────────────────────────────────
    private function request(string $method, string $endpoint, array $data = [], bool $withAuth = true): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch  = curl_init($url);

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($withAuth && Auth::token()) {
            $headers[] = 'Authorization: Bearer ' . Auth::token();
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->log("cURL error [{$endpoint}]: {$error}");
            return ['success' => false, 'message' => 'Could not connect to server.'];
        }

        $json = json_decode($response, true);

        return [
            'success' => in_array($httpCode, [200, 201, 207]),
            'message' => $json['message'] ?? '',
            'data'    => $json['data']    ?? [],
        ];
    }

    // ─── Write to sync.log ────────────────────────────────────────────
    private function log(string $message): void
    {
        $path = Config::get('log_path');
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL;
        file_put_contents($path, $line, FILE_APPEND);
    }

    private function resolveCustomerApiId(?int $localId): ?int
    {
        if (!$localId) return null;

        $db       = $this->getDb();
        $customer = $db->queryOne(
            "SELECT api_id, name, phone FROM customers WHERE id = ?",
            [$localId]
        );

        if (!$customer) return null;

        // لو عنده api_id يبقى متزامن — ابعته
        if (!empty($customer['api_id'])) {
            return (int) $customer['api_id'];
        }

        // لو api_id = NULL يبقى لسه مش على السيرفر — ابعته الأول
        $result = $this->createCustomer([
            'name'  => $customer['name'],
            'phone' => $customer['phone'] ?? null,
        ]);

        if ($result['success'] && !empty($result['data']['id'])) {
            $apiId = $result['data']['id'];

            // حفظ الـ api_id محلياً عشان المرة الجاية
            $db->execute(
                "UPDATE customers SET api_id = ? WHERE id = ?",
                [$apiId, $localId]
            );

            return $apiId;
        }

        // لو السيرفر مش متاح — ابعت null وهيتسجل كـ Walk-in
        return null;
    }
}
