<?php
ob_start();
$pageTitle = 'Invoice #' . $invoice['id'] . ' — Pos-Cashier';
?>

<div class="page-header no-print">
    <div>
        <div class="page-title">Invoice #<?= $invoice['id'] ?></div>
        <div class="page-subtitle">
            <?= $invoice['is_synced'] ? 'Synced with server' : 'Pending sync' ?>
        </div>
    </div>
    <div style="display:flex;gap:12px">
        <button class="btn btn-secondary" onclick="window.print()" style="background: var(--surface); border: 1px solid var(--border);">
            <i class="bi bi-printer"></i> Print
        </button>
        <a href="/MiniLedger/CASHIER/public/pos/dashboard" class="btn btn-primary" style="border-radius: var(--radius-lg);">
            <i class="bi bi-plus-lg"></i> New Sale
        </a>
    </div>
</div>

<div class="invoice-document">

    <div class="inv-header">
        <div class="inv-company">
            <div class="inv-company-name">Pos-Cashier</div>
            <div class="inv-company-desc">Car Spare Parts POS System</div>
        </div>
        <div class="inv-meta">
            <div class="inv-number">Invoice #<?= $invoice['id'] ?></div>
            <div class="inv-date"><?= date('d M Y, h:i A', strtotime($invoice['created_at'])) ?></div>
            <div class="inv-badges">
                <?php if ($invoice['is_synced']): ?>
                    <span class="inv-badge synced">
                        <i class="bi bi-check-circle-fill"></i> Synced
                    </span>
                <?php else: ?>
                    <span class="inv-badge pending">
                        <i class="bi bi-clock-fill"></i> Pending
                    </span>
                <?php endif; ?>
                <span class="inv-badge type">
                    <?= ucfirst($invoice['type']) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="inv-info-section">
        <div class="inv-info-block">
            <div class="inv-info-label">Cashier</div>
            <div class="inv-info-value"><?= htmlspecialchars($invoice['cashier_email']) ?></div>
        </div>
        <div class="inv-info-block">
            <div class="inv-info-label">Customer</div>
            <div class="inv-info-value" style="font-weight: 600;">
                <?= !empty($invoice['customer_name']) ? htmlspecialchars($invoice['customer_name']) : 'Walk-in Customer' ?>
            </div>
        </div>
        <div class="inv-info-block reference">
            <div class="inv-info-label">Reference ID</div>
            <div class="inv-info-value reference-text"><?= $invoice['uuid'] ?></div>
        </div>
    </div>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead>
                <tr>
                    <th style="text-align:left; width: 5%;">#</th>
                    <th style="text-align:left; width: 45%;">Product</th>
                    <th style="text-align:center; width: 15%;">Qty</th>
                    <th style="text-align:right; width: 15%;">Unit Price</th>
                    <th style="text-align:right; width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $i => $item): ?>
                    <tr>
                        <td class="text-muted"><?= $i + 1 ?></td>
                        <td class="fw-bold text-primary"><?= htmlspecialchars($item['product_name']) ?></td>
                        <td style="text-align:center;"><?= $item['quantity'] ?></td>
                        <td style="text-align:right;"><?= number_format($item['unit_price'], 2) ?></td>
                        <td style="text-align:right; font-weight: 600;" class="text-primary">
                            <?= number_format($item['total_row'], 2) ?> EGP
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="inv-totals-wrap">
        <div class="inv-totals">
            <div class="inv-total-row">
                <span>Subtotal</span>
                <span><?= number_format($invoice['total_amount'] + $invoice['discount'] - $invoice['tax'], 2) ?> EGP</span>
            </div>

            <?php if ($invoice['discount'] > 0): ?>
            <div class="inv-total-row discount">
                <span>Discount</span>
                <span>- <?= number_format($invoice['discount'], 2) ?> EGP</span>
            </div>
            <?php endif; ?>

            <?php if ($invoice['tax'] > 0): ?>
            <div class="inv-total-row">
                <span>Tax</span>
                <span>+ <?= number_format($invoice['tax'], 2) ?> EGP</span>
            </div>
            <?php endif; ?>

            <div class="inv-total-final">
                <span>Total</span>
                <span><?= number_format($invoice['total_amount'], 2) ?> EGP</span>
            </div>
        </div>
    </div>

    <div class="inv-footer">
        Thank you for your business — Pos-Cashier System
    </div>

</div>

<style>
/* Document Wrapper */
.invoice-document {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl, 16px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    max-width: 760px;
    margin: 20px auto;
    overflow: hidden;
}

/* Header */
.inv-header {
    padding: 36px 40px;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px solid var(--border);
}

.inv-company-name {
    font-size: 26px;
    font-weight: 800;
    color: var(--primary, #3b82f6);
    letter-spacing: -0.5px;
}

.inv-company-desc {
    font-size: 14px;
    color: var(--text-muted);
    margin-top: 4px;
}

.inv-meta {
    text-align: right;
}

.inv-number {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
}

.inv-date {
    font-size: 13px;
    color: var(--text-secondary);
    margin-top: 4px;
}

.inv-badges {
    margin-top: 12px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.inv-badge {
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

.inv-badge.synced { background: var(--success-light); color: var(--success); }
.inv-badge.pending { background: var(--warning-light); color: var(--warning); }
.inv-badge.type { background: var(--surface-2); color: var(--text-secondary); border: 1px solid var(--border); }

/* Info Section */
.inv-info-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    padding: 24px 40px;
    background: var(--surface-2, #f8fafc);
    border-bottom: 1px solid var(--border);
}

.inv-info-block { display: flex; flex-direction: column; gap: 4px; }
.inv-info-block.reference { grid-column: 1 / -1; }

.inv-info-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

.inv-info-value {
    font-size: 14px;
    color: var(--text-primary);
}

.reference-text {
    font-size: 12px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    color: var(--text-secondary);
    background: var(--surface);
    padding: 6px 10px;
    border: 1px solid var(--border);
    border-radius: 6px;
    display: inline-block;
    width: fit-content;
}

/* Table */
.inv-table-wrap {
    padding: 0;
}

.inv-table {
    width: 100%;
    border-collapse: collapse;
}

.inv-table th {
    padding: 16px 40px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid var(--border);
    background: #fff;
}

/* Adjust padding for first and last th/td to align with wrapper */
.inv-table th:first-child, .inv-table td:first-child { padding-left: 40px; }
.inv-table th:last-child, .inv-table td:last-child { padding-right: 40px; }

.inv-table td {
    padding: 16px 40px;
    font-size: 14px;
    border-bottom: 1px solid var(--border);
    color: var(--text-secondary);
}

/* Text helpers for table */
.text-muted { color: var(--text-muted); }
.text-primary { color: var(--text-primary); }
.fw-bold { font-weight: 600; }

/* Totals */
.inv-totals-wrap {
    padding: 24px 40px;
    display: flex;
    justify-content: flex-end;
}

.inv-totals {
    width: 100%;
    max-width: 320px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.inv-total-row {
    display: flex;
    justify-content: space-between;
    font-size: 14px;
    color: var(--text-secondary);
}

.inv-total-row.discount { color: var(--success, #16a34a); }

.inv-total-final {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 22px;
    font-weight: 800;
    color: var(--primary);
    padding-top: 16px;
    margin-top: 4px;
    border-top: 2px dashed var(--border);
}

/* Footer */
.inv-footer {
    padding: 20px 40px;
    background: var(--surface-2, #f8fafc);
    border-top: 1px solid var(--border);
    text-align: center;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-muted);
}

/* Print Styles */
@media print {
    body { background: #fff !important; }
    .navbar, .no-print { display: none !important; }
    .main { padding: 0 !important; margin: 0 !important; }
    
    .invoice-document {
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        max-width: 100% !important;
        border-radius: 0 !important;
    }

    .inv-header, .inv-info-section, .inv-totals-wrap, .inv-footer {
        padding-left: 0 !important;
        padding-right: 0 !important;
        background: transparent !important;
    }
    
    .inv-table th:first-child, .inv-table td:first-child { padding-left: 0 !important; }
    .inv-table th:last-child, .inv-table td:last-child { padding-right: 0 !important; }
    
    .reference-text { border: none; padding: 0; }
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>