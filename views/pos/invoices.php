<?php
ob_start();
$pageTitle = 'Invoices — Pos-Cashier';
?>

<div class="page-header">
    <div>
        <div class="page-title">Today's Invoices</div>
        <div class="page-subtitle">
            <?= count($invoices) ?> invoice<?= count($invoices) !== 1 ? 's' : '' ?> today
        </div>
    </div>
    <a href="/MiniLedger/CASHIER/public/pos/dashboard" class="btn btn-primary" style="border-radius: var(--radius-lg);">
        <i class="bi bi-plus-lg"></i> New Sale
    </a>
</div>

<?php if (empty($invoices)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="bi bi-receipt"></i>
        </div>
        <div class="empty-state-title">No invoices today</div>
        <div class="empty-state-text">Start selling to see invoices here.</div>
        <a href="/MiniLedger/CASHIER/public/pos/dashboard" class="btn btn-primary" style="margin-top: 16px;">
            <i class="bi bi-cart"></i> Go to POS
        </a>
    </div>
<?php else: ?>
    <div class="invoices-grid">
        <?php foreach ($invoices as $inv): ?>
            <div class="invoice-card">

                <div class="invoice-card-header">
                    <div class="invoice-card-number">
                        <i class="bi bi-receipt"></i>
                        Invoice #<?= $inv['id'] ?>
                    </div>
                    <?php if ($inv['is_synced']): ?>
                        <span class="invoice-badge synced">
                            <i class="bi bi-check-circle-fill"></i> Synced
                        </span>
                    <?php else: ?>
                        <span class="invoice-badge pending">
                            <i class="bi bi-clock-fill"></i> Pending
                        </span>
                    <?php endif; ?>
                </div>

                <div class="invoice-card-body">
                    <div class="invoice-card-row">
                        <span><i class="bi bi-person"></i> Customer</span>
                        <span style="font-weight: 500; color: var(--text-primary);">
                            <?= !empty($inv['customer_name']) ? htmlspecialchars($inv['customer_name']) : 'Walk-in' ?>
                        </span>
                    </div>
                    <div class="invoice-card-row">
                        <span><i class="bi bi-tag"></i> Type</span>
                        <span style="font-weight: 500; color: var(--text-primary);">
                            <?= ucfirst($inv['type']) ?>
                        </span>
                    </div>
                    <div class="invoice-card-row">
                        <span><i class="bi bi-clock"></i> Time</span>
                        <span style="font-weight: 500; color: var(--text-primary);">
                            <?= date('h:i A', strtotime($inv['created_at'])) ?>
                        </span>
                    </div>
                    <div class="invoice-card-total">
                        <?= number_format($inv['total_amount'], 2) ?> EGP
                    </div>
                </div>

                <a href="/MiniLedger/CASHIER/public/invoice/preview/<?= $inv['id'] ?>" class="invoice-card-footer">
                    <span>View Details</span>
                    <i class="bi bi-arrow-right"></i>
                </a>

            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
/* ── Empty State (Consistent with Cart) ── */
.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: var(--surface);
    border: 1px dashed var(--border);
    border-radius: var(--radius-xl, 16px);
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

/* ── Grid & Cards ── */
.invoices-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 24px;
}

.invoice-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl, 16px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
}

.invoice-card:hover {
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    transform: translateY(-4px);
    border-color: var(--primary-light, #bfdbfe);
}

.invoice-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2, #f8fafc);
}

.invoice-card-number {
    font-size: 15px;
    font-weight: 700;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 8px;
}

.invoice-card-number i {
    color: var(--primary);
    font-size: 16px;
}

/* ── Badges ── */
.invoice-badge {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 99px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.invoice-badge i {
    font-size: 12px;
}

.invoice-badge.synced {
    background: var(--success-light, #dcfce7);
    color: var(--success, #16a34a);
}

.invoice-badge.pending {
    background: var(--warning-light, #fef3c7);
    color: var(--warning, #d97706);
}

/* ── Card Body ── */
.invoice-card-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.invoice-card-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
}

.invoice-card-row span:first-child {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-muted);
}

.invoice-card-total {
    font-size: 22px;
    font-weight: 800;
    color: var(--primary);
    margin-top: 12px;
    padding-top: 16px;
    border-top: 1px dashed var(--border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.invoice-card-total::before {
    content: 'Total';
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-secondary);
    letter-spacing: 0.5px;
}

/* ── Card Footer ── */
.invoice-card-footer {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    background: var(--surface);
    border-top: 1px solid var(--border);
    font-size: 14px;
    font-weight: 600;
    color: var(--primary);
    text-decoration: none;
    transition: all 0.2s ease;
}

.invoice-card-footer:hover {
    background: var(--primary-light, #eff6ff);
    gap: 12px; /* بيعمل حركة ظريفة للسهم */
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>