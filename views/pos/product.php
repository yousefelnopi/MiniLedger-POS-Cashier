<?php
ob_start();
$pageTitle = ($item['name'] ?? 'Product') . ' — Pos-Cashier';
$isLow = $item['stock_qty'] > 0 && $item['stock_qty'] <= $item['alert_stock'];
$isOut = $item['stock_qty'] <= 0;
?>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:16px">
        <a href="/MiniLedger/CASHIER/public/pos/products?id=<?= $categoryId ?>"
            class="btn btn-secondary btn-sm" style="border-radius: var(--radius-lg);">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <div class="page-title"><?= htmlspecialchars($item['name']) ?></div>
            <div class="page-subtitle">Product Details</div>
        </div>
    </div>
    <a href="/MiniLedger/CASHIER/public/pos/cart" class="btn btn-primary">
        <i class="bi bi-cart3"></i> Cart
        <?php if ($cartCount > 0): ?>
            <span class="badge" style="background:rgba(255,255,255,.25); color:#fff; margin-left:4px;"><?= $cartCount ?></span>
        <?php endif; ?>
    </a>
</div>

<div class="product-detail-wrap">

    <div class="product-detail-img">
        <?php if (!empty($item['image'])): ?>
            <img
                src="/MiniLedger/CASHIER/public/<?= htmlspecialchars($item['image']) ?>"
                alt="<?= htmlspecialchars($item['name']) ?>"
                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
            <div class="product-detail-img-fallback" style="display:none">
                <i class="bi bi-box-seam"></i>
            </div>
        <?php else: ?>
            <div class="product-detail-img-fallback">
                <i class="bi bi-box-seam"></i>
            </div>
        <?php endif; ?>
    </div>

    <div class="product-detail-info">

        <div class="product-detail-name"><?= htmlspecialchars($item['name']) ?></div>

        <div class="product-detail-price"><?= number_format($item['sell_price'], 2) ?> EGP</div>

        <div class="product-detail-stock <?= $isLow ? 'low' : ($isOut ? 'out' : '') ?>">
            <?php if ($isOut): ?>
                <i class="bi bi-x-circle"></i> Out of stock
            <?php elseif ($isLow): ?>
                <i class="bi bi-exclamation-triangle"></i> Low stock: <?= $item['stock_qty'] ?> left
            <?php else: ?>
                <i class="bi bi-check-circle"></i> In stock: <?= $item['stock_qty'] ?>
            <?php endif; ?>
        </div>

        <div class="divider" style="margin: 8px 0; border-top: 1px solid var(--border);"></div>

        <div class="product-detail-meta">
            <div class="product-detail-row">
                <span class="product-detail-label">
                    <i class="bi bi-upc"></i> SKU
                </span>
                <span class="product-detail-value"><?= htmlspecialchars($item['sku']) ?></span>
            </div>

            <?php if (!empty($item['part_number'])): ?>
                <div class="product-detail-row">
                    <span class="product-detail-label">
                        <i class="bi bi-hash"></i> Part Number
                    </span>
                    <span class="product-detail-value"><?= htmlspecialchars($item['part_number']) ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($item['location_code'])): ?>
                <div class="product-detail-row">
                    <span class="product-detail-label">
                        <i class="bi bi-geo-alt"></i> Location
                    </span>
                    <span class="product-detail-value"><?= htmlspecialchars($item['location_code']) ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($item['car_models'])): ?>
            <div class="product-detail-section">
                <div class="product-detail-section-title">
                    <i class="bi bi-car-front"></i> Compatible Vehicles
                </div>
                <div class="car-models-grid">
                    <?php foreach ($item['car_models'] as $cm): ?>
                        <div class="car-model-tag">
                            <i class="bi bi-check2"></i>
                            <?= htmlspecialchars($cm['brand'] . ' ' . $cm['model']) ?>
                            <?= $cm['year'] ? ' — ' . $cm['year'] : '' ?>
                            <?= $cm['engine'] ? ' (' . $cm['engine'] . ')' : '' ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$isOut): ?>
            <div class="product-detail-actions">
                <input
                    type="number"
                    class="qty-input"
                    id="qty-<?= $item['id'] ?>"
                    value="1"
                    min="1"
                    max="<?= $item['stock_qty'] ?>"
                    style="width:90px; text-align: center;">
                <button
                    class="btn btn-primary btn-lg"
                    style="flex:1"
                    onclick="addToCart(<?= $item['id'] ?>, <?= $item['stock_qty'] ?>)">
                    <i class="bi bi-cart-plus"></i> Add to Cart
                </button>
            </div>
        <?php endif; ?>

        <div style="margin-top:16px">
            <button
                class="btn btn-secondary btn-sm"
                style="width:100%; border: 1px dashed var(--border); background: var(--surface); color: var(--text-secondary);"
                onclick="toggleAdjustForm()">
                <i class="bi bi-arrow-repeat"></i> Request Stock Adjustment
            </button>
        </div>

        <div id="adjust-form" style="display:none">
            <div class="adjust-form-wrap">

                <div class="adjust-row">
                    <label class="adjust-label">Type</label>
                    <select id="adjust-type" class="form-select">
                        <option value="add">Add Stock</option>
                        <option value="sub">Reduce Stock</option>
                    </select>
                </div>

                <div class="adjust-row">
                    <label class="adjust-label">Quantity</label>
                    <input
                        type="number"
                        id="adjust-qty"
                        class="form-input"
                        min="1"
                        placeholder="e.g. 5">
                </div>

                <div class="adjust-row">
                    <label class="adjust-label">Reason</label>
                    <input
                        type="text"
                        id="adjust-reason"
                        class="form-input"
                        placeholder="e.g. Damaged items found">
                </div>

                <div style="display:flex;gap:12px;margin-top:12px">
                    <button
                        class="btn btn-secondary btn-sm"
                        style="flex:1"
                        onclick="toggleAdjustForm()">
                        Cancel
                    </button>
                    <button
                        class="btn btn-warning btn-sm"
                        style="flex:1; background: var(--warning); color: #fff; border:none;"
                        onclick="submitAdjustRequest(<?= $item['id'] ?>)">
                        <i class="bi bi-send"></i> Send Request
                    </button>
                </div>

            </div>
        </div>
        </div>
</div>

<style>
    /* ── Layout ── */
    .product-detail-wrap {
        display: grid;
        grid-template-columns: 380px 1fr; /* عرضت صورة المنتج شوية */
        gap: 40px; /* مسافة أوسع تريح العين */
        align-items: start;
        max-width: 1000px;
        margin: 0 auto; /* توسيط المحتوى */
        padding-top: 16px;
    }

    @media (max-width: 768px) {
        .product-detail-wrap {
            grid-template-columns: 1fr;
            gap: 24px;
        }
    }

    /* ── Image Section ── */
    .product-detail-img {
        width: 100%;
        aspect-ratio: 1;
        border-radius: var(--radius-xl);
        overflow: hidden;
        background: #ffffff;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--shadow);
        padding: 24px; /* مساحة داخلية للصورة */
        transition: all var(--transition);
    }

    .product-detail-img:hover {
        border-color: var(--primary-light);
        box-shadow: 0 8px 24px rgba(12, 63, 130, 0.08); /* وهج عند الهوفر */
    }

    .product-detail-img img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* عشان متتقصش */
        transition: transform 0.4s ease;
    }

    .product-detail-img:hover img {
        transform: scale(1.08); /* زووم ناعم للصورة */
    }

    .product-detail-img-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 80px;
        color: var(--text-muted);
        opacity: .3;
    }

    /* ── Info Section ── */
    .product-detail-info {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .product-detail-name {
        font-size: 28px;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1.3;
        letter-spacing: -.5px;
    }

    .product-detail-price {
        font-size: 36px;
        font-weight: 800;
        color: var(--primary);
        letter-spacing: -.5px;
    }

    /* ── Badges ── */
    .product-detail-stock {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 99px;
        background: var(--success-light);
        color: var(--success);
        width: fit-content;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .product-detail-stock.low {
        background: var(--warning-light);
        color: var(--warning);
    }

    .product-detail-stock.out {
        background: var(--danger-light);
        color: var(--danger);
    }

    /* ── Meta Info (SKU, etc) ── */
    .product-detail-meta {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-sm);
    }

    .product-detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        font-size: 14px;
        transition: background var(--transition);
    }

    .product-detail-row:hover {
        background: var(--surface-2); /* تظليل خفيف عند الهوفر */
    }

    .product-detail-row:last-child {
        border-bottom: none;
    }

    .product-detail-label {
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-secondary);
        font-weight: 500;
    }

    .product-detail-value {
        font-weight: 700;
        color: var(--text-primary);
        font-family: 'Courier New', Courier, monospace; /* خط مناسب للأكواد */
        font-size: 15px;
    }

    /* ── Compatible Vehicles ── */
    .product-detail-section {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: 8px;
    }

    .product-detail-section-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .car-models-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .car-model-tag {
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 14px;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        font-size: 13px;
        font-weight: 500;
        color: var(--text-secondary);
        box-shadow: var(--shadow-sm);
        transition: all var(--transition);
    }
    
    .car-model-tag:hover {
        border-color: var(--primary-light);
        color: var(--primary);
    }

    /* ── Actions ── */
    .product-detail-actions {
        display: flex;
        gap: 16px;
        align-items: stretch; /* عشان الـ input والزرار يبقوا نفس الطول */
        padding-top: 16px;
    }

    .qty-input {
        padding: 0 16px;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        background: var(--surface);
    }
    
    .qty-input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(12, 63, 130, 0.1);
    }

    /* ── Adjust Form ── */
    .adjust-form-wrap {
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 12px;
        box-shadow: var(--shadow-sm);
    }

    .adjust-row {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .adjust-label {
        font-size: 12px;
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .form-select,
    .form-input {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--surface-2);
        color: var(--text-primary);
        font-size: 14px;
        width: 100%;
        transition: all var(--transition);
    }

    .form-select:focus,
    .form-input:focus {
        outline: none;
        border-color: var(--primary);
        background: #ffffff;
        box-shadow: 0 0 0 3px rgba(12, 63, 130, 0.1);
    }
</style>

<script>
    // ── Logic remains 100% untouched ──
    function toggleAdjustForm() {
        const form = document.getElementById('adjust-form');
        form.style.display = form.style.display === 'none' ? 'block' : 'none';
    }

    async function submitAdjustRequest(productId) {
        const type = document.getElementById('adjust-type').value;
        const qty = document.getElementById('adjust-qty').value;
        const reason = document.getElementById('adjust-reason').value.trim();

        if (!qty || parseInt(qty) < 1) {
            showToast('Enter a valid quantity.', 'error');
            return;
        }

        if (!reason) {
            showToast('Please enter a reason.', 'error');
            return;
        }

        try {
            const res = await fetch(BASE + '/inventory/request-adjust', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    type: type,
                    quantity: parseInt(qty),
                    reason: reason,
                }),
            });

            const json = await res.json();

            if (json.status === 'success') {
                showToast('Request sent to admin.', 'success');
                toggleAdjustForm();
                document.getElementById('adjust-qty').value = '';
                document.getElementById('adjust-reason').value = '';
            } else {
                showToast(json.message || 'Failed to send request.', 'error');
            }

        } catch (e) {
            showToast('Could not reach server.', 'error');
        }
    }
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>