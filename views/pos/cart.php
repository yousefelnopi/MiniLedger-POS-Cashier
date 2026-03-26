<?php
$cartTotalJs = (float)$cartTotal;
ob_start();
$pageTitle = 'Cart — Pos-Cashier';
?>

<div class="page-header">
    <div>
        <div class="page-title">Cart</div>
        <div class="page-subtitle">
            <?= count($cart) ?> item<?= count($cart) !== 1 ? 's' : '' ?>
            — Total: <strong><?= number_format($cartTotal, 2) ?> EGP</strong>
        </div>
    </div>
    <a href="/MiniLedger/CASHIER/public/pos/dashboard" class="btn btn-secondary" style="border-radius: var(--radius-lg);">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if (empty($cart)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="bi bi-cart-x"></i>
        </div>
        <div class="empty-state-title">Your cart is empty</div>
        <div class="empty-state-text">Go back and add some products.</div>
        <a href="/MiniLedger/CASHIER/public/pos/dashboard" class="btn btn-primary" style="margin-top: 16px;">
            <i class="bi bi-grid"></i> Browse Products
        </a>
    </div>

<?php else: ?>
    <div class="cart-layout">

        <div class="cart-main">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th style="width: 120px;">Quantity</th>
                            <th>Total</th>
                            <th style="width: 60px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart as $item): ?>
                            <tr id="row-<?= $item['product_id'] ?>">
                                <td>
                                    <div style="font-weight:600; color: var(--text-primary); font-size: 14px;">
                                        <?= htmlspecialchars($item['product_name']) ?>
                                    </div>
                                </td>
                                <td style="color: var(--text-secondary);"><?= number_format($item['unit_price'], 2) ?> EGP</td>
                                <td>
                                    <input type="number" class="qty-input-sm"
                                        value="<?= $item['quantity'] ?>" min="1"
                                        onchange="updateQty(<?= $item['product_id'] ?>, this.value)">
                                </td>
                                <td style="font-weight:700; color: var(--primary);">
                                    <?= number_format($item['total_row'], 2) ?> EGP
                                </td>
                                <td style="text-align: center;">
                                    <button class="btn btn-ghost-danger btn-sm action-btn"
                                        onclick="removeItem(<?= $item['product_id'] ?>)" title="Remove item">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="invoice-summary-wrap">
            <div class="invoice-summary">
                <div class="invoice-summary-header">
                    <div class="card-title">
                        <i class="bi bi-receipt"></i> Invoice Details
                    </div>
                </div>
                
                <div class="invoice-summary-body">
                    <div class="form-group">
                        <label class="form-label">Invoice Type</label>
                        <select id="invoiceType" class="form-control form-select">
                            <option value="sale">Sale</option>
                            <option value="return">Return</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Customer (Optional)</label>
                        <div style="display:flex;gap:8px;align-items:flex-start">
                            <div style="flex:1;position:relative">
                                <input
                                    type="text"
                                    id="customerSearch"
                                    class="form-control"
                                    placeholder="Walk-in Customer"
                                    autocomplete="off"
                                    oninput="filterCustomers(this.value)"
                                    onfocus="openCustomerDropdown()"
                                    onblur="setTimeout(closeCustomerDropdown, 200)">

                                <input type="hidden" id="customerId" value="">

                                <div id="customerDropdown" class="custom-dropdown">
                                    <div class="customer-option"
                                         data-id=""
                                         data-name="Walk-in Customer"
                                         onmousedown="selectCustomer('', 'Walk-in Customer')">
                                        <i class="bi bi-person"></i> Walk-in Customer
                                    </div>
                                    <?php foreach ($customers as $c): ?>
                                        <div class="customer-option"
                                             data-id="<?= $c['id'] ?>"
                                             data-name="<?= htmlspecialchars($c['name']) ?>"
                                             onmousedown="selectCustomer(
                                                 '<?= $c['id'] ?>',
                                                 '<?= htmlspecialchars($c['name']) ?><?= $c['phone'] ? ' — ' . $c['phone'] : '' ?>'
                                             )">
                                            <i class="bi bi-person"></i>
                                            <?= htmlspecialchars($c['name']) ?>
                                            <?php if ($c['phone']): ?>
                                                <span class="customer-phone">
                                                    — <?= htmlspecialchars($c['phone']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="btn btn-secondary btn-sm"
                                style="white-space:nowrap; border:1px dashed var(--border); flex-shrink:0; height: 40px; border-radius: var(--radius);"
                                onclick="openNewCustomerModal()">
                                <i class="bi bi-person-plus"></i> New
                            </button>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px;">
                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Discount (EGP)</label>
                            <input type="number" id="discount" class="form-control"
                                   value="0" min="0" step="0.01" oninput="recalculate()">
                        </div>

                        <div class="form-group" style="flex: 1;">
                            <label class="form-label">Tax (%)</label>
                            <input type="number" id="tax" class="form-control"
                                   value="0" min="0" max="100" step="0.01" oninput="recalculate()">
                        </div>
                    </div>

                    <div class="divider" style="margin: 8px 0;"></div>

                    <div class="summary-box">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotalDisplay"><?= number_format($cartTotal, 2) ?> EGP</span>
                        </div>
                        <div class="summary-row text-muted">
                            <span>Discount</span>
                            <span id="discountDisplay">0.00 EGP</span>
                        </div>
                        <div class="summary-row text-muted">
                            <span>Tax</span>
                            <span id="taxDisplay">0.00 EGP</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="totalDisplay"><?= number_format($cartTotal, 2) ?> EGP</span>
                        </div>
                    </div>

                    <button class="btn btn-success btn-lg confirm-btn" id="confirmBtn" onclick="confirmInvoice()">
                        <i class="bi bi-check-lg"></i> Confirm Sale
                    </button>

                </div>
            </div>
        </div>

    </div>
<?php endif; ?>

<div id="customerModal" class="modal-overlay">
    <div class="modal-card">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-person-plus"></i> New Customer
            </div>
            <button class="btn btn-ghost btn-sm" onclick="closeNewCustomerModal()">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="modal-body">
            <div>
                <label class="form-label">
                    Name <span style="color:var(--danger)">*</span>
                </label>
                <input
                    type="text"
                    id="newCustomerName"
                    class="form-control"
                    placeholder="Customer name">
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input
                    type="text"
                    id="newCustomerPhone"
                    class="form-control"
                    placeholder="+201xxxxxxxxx">
            </div>
        </div>

        <div class="modal-footer">
            <button class="btn btn-secondary" style="flex:1" onclick="closeNewCustomerModal()">
                Cancel
            </button>
            <button class="btn btn-primary" style="flex:1" id="saveCustomerBtn" onclick="saveNewCustomer()">
                <i class="bi bi-check-lg"></i> Save
            </button>
        </div>
    </div>
</div>

<style>
    /* ── Layout ── */
    .cart-layout {
        display: grid;
        grid-template-columns: 1fr 360px; /* الجدول ياخد باقي المساحة والملخص 360 بكسل */
        gap: 24px;
        align-items: start;
        margin-top: 16px;
    }

    @media (max-width: 992px) {
        .cart-layout {
            grid-template-columns: 1fr;
        }
    }

    /* ── Empty State ── */
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 60px 20px;
        background: var(--surface);
        border: 1px dashed var(--border);
        border-radius: var(--radius-xl);
        text-align: center;
        margin-top: 24px;
    }

    .empty-state-icon {
        font-size: 56px;
        color: var(--text-muted);
        opacity: 0.5;
        margin-bottom: 16px;
    }

    .empty-state-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .empty-state-text {
        font-size: 14px;
        color: var(--text-secondary);
    }

    /* ── Table Styling ── */
    .table-wrap {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        overflow-x: auto; /* عشان الشاشات الصغيرة */
        box-shadow: var(--shadow-sm);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    thead th {
        padding: 14px 16px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
        background: var(--surface-2);
        border-bottom: 1px solid var(--border);
    }

    tbody td {
        padding: 16px;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        font-size: 14px;
    }

    tbody tr:last-child td {
        border-bottom: none;
    }

    tbody tr:hover {
        background: var(--surface-2); /* Hover Effect خفيف */
    }

    .qty-input-sm {
        width: 80px;
        padding: 8px 12px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        text-align: center;
        font-weight: 600;
        font-size: 14px;
        background: var(--surface);
        transition: border-color var(--transition);
    }

    .qty-input-sm:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(12, 63, 130, 0.1);
    }

    .btn-ghost-danger {
        background: transparent;
        color: var(--danger);
        border: none;
        padding: 8px;
        border-radius: var(--radius);
        transition: all var(--transition);
    }

    .btn-ghost-danger:hover {
        background: var(--danger-light);
    }

    /* ── Invoice Summary Card ── */
    .invoice-summary-wrap {
        position: sticky;
        top: 24px; /* يثبت لما تنزل بالصفحة */
    }

    .invoice-summary {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .invoice-summary-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--surface-2);
    }

    .card-title {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .invoice-summary-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    /* ── Forms inside Summary ── */
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .form-control {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: #ffffff;
        font-size: 14px;
        color: var(--text-primary);
        transition: all var(--transition);
        width: 100%;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(12, 63, 130, 0.1);
    }

    /* ── Custom Dropdown ── */
    .custom-dropdown {
        display: none;
        position: absolute;
        top: calc(100% + 4px);
        left: 0;
        right: 0;
        background: #ffffff;
        border: 1px solid var(--border);
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        max-height: 220px;
        overflow-y: auto;
        z-index: 200;
        padding: 8px 0;
    }

    .customer-option {
        padding: 10px 16px;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        color: var(--text-primary);
        transition: background var(--transition);
    }

    .customer-option:hover { 
        background: var(--surface-2); 
    }

    .customer-option.selected { 
        background: var(--primary-light); 
        color: var(--primary); 
        font-weight: 600;
    }

    .customer-phone {
        color: var(--text-muted);
        font-size: 12px;
        margin-left: auto; /* يزق الرقم للآخر */
    }

    /* ── Totals Box ── */
    .summary-box {
        background: var(--surface-2);
        padding: 16px;
        border-radius: var(--radius-lg);
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        font-weight: 500;
        color: var(--text-primary);
    }
    
    .summary-row.text-muted {
        color: var(--text-secondary);
    }

    .summary-row.total {
        margin-top: 6px;
        padding-top: 12px;
        border-top: 1px dashed var(--border);
        font-size: 18px;
        font-weight: 800;
        color: var(--primary);
    }

    .confirm-btn {
        width: 100%;
        margin-top: 8px;
        font-size: 16px;
        font-weight: 700;
        padding: 14px;
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2); /* ظل أخضر خفيف */
    }

    /* ── Modal Styling ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.4);
        backdrop-filter: blur(4px); /* بيعزل الخلفية */
        z-index: 1000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .modal-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-xl);
        width: 100%;
        max-width: 420px;
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        animation: slideUp 0.3s ease-out forwards;
    }

    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        border-bottom: 1px solid var(--border);
    }

    .modal-title {
        font-size: 18px;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-body {
        padding: 24px;
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .modal-footer {
        padding: 16px 24px;
        background: var(--surface-2);
        border-top: 1px solid var(--border);
        display: flex;
        gap: 12px;
    }
</style>

<script>
// ── اللوجيك لم يتم تعديله إطلاقاً، كله شغال زي ما كان ──

const subtotal = <?= $cartTotalJs ?>;

// ── Totals ────────────────────────────────────────────────────────────
function recalculate() {
    const discount      = parseFloat(document.getElementById('discount').value) || 0;
    const taxPct        = parseFloat(document.getElementById('tax').value) || 0;
    const afterDiscount = Math.max(subtotal - discount, 0);
    const taxAmount     = (afterDiscount * taxPct) / 100;
    const total         = afterDiscount + taxAmount;

    document.getElementById('subtotalDisplay').textContent = subtotal.toFixed(2)  + ' EGP';
    document.getElementById('discountDisplay').textContent = discount.toFixed(2)  + ' EGP';
    document.getElementById('taxDisplay').textContent      = taxAmount.toFixed(2) + ' EGP';
    document.getElementById('totalDisplay').textContent    = total.toFixed(2)     + ' EGP';
}

// ── Cart Actions ──────────────────────────────────────────────────────
async function updateQty(productId, qty) {
    try {
        const res  = await fetch(BASE + '/cart/update', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId, quantity: parseInt(qty) }),
        });
        const json = await res.json();
        if (json.status === 'success') location.reload();
        else showToast(json.message, 'error');
    } catch (e) { showToast('Something went wrong.', 'error'); }
}

async function removeItem(productId) {
    try {
        const res  = await fetch(BASE + '/cart/remove', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_id: productId }),
        });
        const json = await res.json();
        if (json.status === 'success') location.reload();
        else showToast(json.message, 'error');
    } catch (e) { showToast('Something went wrong.', 'error'); }
}

// ── Customer Search Dropdown ──────────────────────────────────────────
function openCustomerDropdown() {
    document.getElementById('customerDropdown').style.display = 'block';
}

function closeCustomerDropdown() {
    document.getElementById('customerDropdown').style.display = 'none';
}

function selectCustomer(id, label) {
    document.getElementById('customerId').value     = id;
    document.getElementById('customerSearch').value = id ? label : '';
    document.getElementById('customerSearch').placeholder = id ? label : 'Walk-in Customer';

    document.querySelectorAll('.customer-option').forEach(el => {
        el.classList.toggle('selected', el.dataset.id == id);
    });

    closeCustomerDropdown();
}

function filterCustomers(term) {
    const lower   = term.toLowerCase();
    const options = document.querySelectorAll('.customer-option');

    openCustomerDropdown();
    options.forEach(el => {
        const name = el.dataset.name.toLowerCase();
        el.style.display = name.includes(lower) ? 'flex' : 'none';
    });
}

// ── New Customer Modal ────────────────────────────────────────────────
function openNewCustomerModal() {
    document.getElementById('customerModal').style.display = 'flex';
    setTimeout(() => document.getElementById('newCustomerName').focus(), 100);
}

function closeNewCustomerModal() {
    document.getElementById('customerModal').style.display  = 'none';
    document.getElementById('newCustomerName').value        = '';
    document.getElementById('newCustomerPhone').value       = '';
}

async function saveNewCustomer() {
    const name  = document.getElementById('newCustomerName').value.trim();
    const phone = document.getElementById('newCustomerPhone').value.trim();

    if (!name) {
        showToast('Customer name is required.', 'error');
        document.getElementById('newCustomerName').focus();
        return;
    }

    const btn = document.getElementById('saveCustomerBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass"></i> Saving...';

    try {
        const res  = await fetch(BASE + '/customer/store', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ name, phone }),
        });

        const json = await res.json();

        if (json.status === 'success') {
            const customer = json.data;
            const label    = customer.name + (customer.phone ? ' — ' + customer.phone : '');

            // ضيف في الـ dropdown
            const dropdown  = document.getElementById('customerDropdown');
            const div       = document.createElement('div');
            div.className   = 'customer-option';
            div.dataset.id  = customer.id;
            div.dataset.name = customer.name;
            div.innerHTML   = `<i class="bi bi-person"></i> ${customer.name}` +
                              (customer.phone ? ` <span class="customer-phone">— ${customer.phone}</span>` : '');
            div.onmousedown = () => selectCustomer(customer.id, label);
            dropdown.appendChild(div);

            // حدده تلقائياً
            selectCustomer(customer.id, label);

            showToast('Customer added.', 'success');
            closeNewCustomerModal();
        } else {
            showToast(json.message || 'Failed to save customer.', 'error');
        }
    } catch (e) {
        showToast('Something went wrong.', 'error');
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Save';
    }
}

// إغلاق الـ modal بالضغط برا
document.getElementById('customerModal').addEventListener('click', function(e) {
    if (e.target === this) closeNewCustomerModal();
});
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>