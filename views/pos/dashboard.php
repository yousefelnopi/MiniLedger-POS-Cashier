<?php
ob_start();
$pageTitle = 'Dashboard — Pos-Cashier';
?>

<div class="page-header">
    <div>
        <div class="page-title">Categories</div>
        <div class="page-subtitle">Select a category to browse products</div>
    </div>
    <a href="/MiniLedger/CASHIER/public/pos/cart" class="btn btn-primary">
        <i class="bi bi-cart3"></i>
        Cart
        <?php if ($cartCount > 0): ?>
            <span class="badge" style="background:rgba(255,255,255,.25); color:#fff; margin-left:4px;">
                <?= $cartCount ?>
            </span>
        <?php endif; ?>
    </a>
</div>

<?php if (empty($categories)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="bi bi-folder-x"></i>
        </div>
        <div class="empty-state-title">No categories found</div>
        <div class="empty-state-text">Pull data from server first.</div>
    </div>
<?php else: ?>
    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="/MiniLedger/CASHIER/public/pos/products?id=<?= $cat['id'] ?>" class="category-card">

                <div class="category-img">
                    <?php if (!empty($cat['image'])): ?>
                        <img
                            src="/MiniLedger/CASHIER/public/<?= htmlspecialchars($cat['image']) ?>"
                            alt="<?= htmlspecialchars($cat['name']) ?>"
                            onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"
                        >
                        <div class="category-img-fallback" style="display:none">
                            <i class="bi bi-folder2"></i>
                        </div>
                    <?php else: ?>
                        <div class="category-img-fallback">
                            <i class="bi bi-folder2"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="category-name"><?= htmlspecialchars($cat['name']) ?></div>

            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<style>
/* ── Categories Grid (Modern Edition) ─────────────────────────── */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 24px; /* مسافة أوسع شوية تريح العين */
}

.category-card {
    background: var(--surface);
    border: 1px solid rgba(0, 0, 0, 0.03); /* حافة ناعمة جداً */
    border-radius: var(--radius-lg);
    padding: 32px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 16px;
    text-decoration: none;
    transition: all var(--transition);
    box-shadow: var(--shadow); /* ظل خفيف */
}

.category-card:hover {
    border-color: var(--primary-light);
    box-shadow: var(--shadow-lg); /* ظل أعمق في الهوفر */
    transform: translateY(-4px); /* حركة بريميوم للفوق */
}

.category-img {
    width: 110px;
    height: 110px;
    border-radius: var(--radius);
    background: var(--surface-2);
    border: 1px solid var(--border);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition);
}

.category-card:hover .category-img {
    border-color: var(--primary-light);
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(12, 63, 130, 0.08); /* وهج أزرق خفيف حول الصورة */
}

.category-img img {
    width: 100%;
    height: 100%;
    object-fit: contain; /* عشان لو الصورة لوجو تظهر بالكامل */
    padding: 12px;
    transition: transform 0.3s ease;
}

.category-card:hover .category-img img {
    transform: scale(1.1); /* زووم ناعم للصورة */
}

.category-img-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: var(--text-muted);
    transition: color var(--transition);
}

.category-card:hover .category-img-fallback {
    color: var(--primary); /* الأيقونة بتنور أزرق في الهوفر */
}

.category-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
    text-align: center;
    line-height: 1.4;
    transition: color var(--transition);
}

.category-card:hover .category-name {
    color: var(--primary); /* اسم القسم بياخد اللون الأزرق في الهوفر */
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>