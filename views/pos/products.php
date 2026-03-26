<?php
ob_start();
$pageTitle = ($cat['name'] ?? 'Products') . ' — Pos-Cashier';
?>

<div class="page-header">
    <div style="display:flex;align-items:center;gap:16px">
        <a href="/MiniLedger/CASHIER/public/pos/dashboard" class="btn btn-secondary btn-sm" style="border-radius: var(--radius-lg);">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <div>
            <div class="page-title"><?= htmlspecialchars($cat['name'] ?? 'Products') ?></div>
            <div class="page-subtitle" id="productCount">
                <?= count($products) ?> product<?= count($products) !== 1 ? 's' : '' ?>
            </div>
        </div>
    </div>
    <a href="/MiniLedger/CASHIER/public/pos/cart" class="btn btn-primary">
        <i class="bi bi-cart3"></i> Cart
        <?php if ($cartCount > 0): ?>
            <span class="badge" style="background:rgba(255,255,255,.25); color:#fff; margin-left:4px;"><?= $cartCount ?></span>
        <?php endif; ?>
    </a>
</div>

<div class="toolbar">
    <div class="search-box">
        <i class="bi bi-search search-icon"></i>
        <input
            type="text"
            id="searchInput"
            placeholder="Search by name, SKU, part number..."
            value="<?= htmlspecialchars($search ?? '') ?>"
            oninput="liveSearch(this.value)"
            autocomplete="off">
        <button class="search-clear" id="clearBtn" onclick="clearSearch()" style="display:<?= !empty($search) ? 'flex' : 'none' ?>">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
</div>

<div id="productsContainer">
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <div class="empty-state-title">No products found</div>
            <div class="empty-state-text">
                <?= !empty($search) ? 'Try a different search term.' : 'No products in this category.' ?>
            </div>
        </div>
    <?php else: ?>
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $p):
                $isLow = $p['stock_qty'] > 0 && $p['stock_qty'] <= $p['alert_stock'];
                $isOut = $p['stock_qty'] <= 0;
            ?>
                <div class="product-card <?= $isLow ? 'low-stock' : '' ?> <?= $isOut ? 'out-of-stock' : '' ?>">

                    <div class="product-img">
                        <?php if (!empty($p['image'])): ?>
                            <img
                                src="/MiniLedger/CASHIER/public/<?= htmlspecialchars($p['image']) ?>"
                                alt="<?= htmlspecialchars($p['name']) ?>"
                                onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="product-img-fallback" style="display:none">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        <?php else: ?>
                            <div class="product-img-fallback">
                                <i class="bi bi-box-seam"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="/MiniLedger/CASHIER/public/pos/product?id=<?= $p['id'] ?>"
                        class="product-name" style="text-decoration:none;">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>
                    
                    <div style="display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px;">
                        <div class="product-sku">
                            <i class="bi bi-upc"></i> <?= htmlspecialchars($p['sku']) ?>
                        </div>

                        <?php if (!empty($p['part_number'])): ?>
                            <div class="product-sku">
                                <i class="bi bi-hash"></i> <?= htmlspecialchars($p['part_number']) ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($p['location_code'])): ?>
                            <div class="product-location">
                                <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($p['location_code']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="product-price"><?= number_format($p['sell_price'], 2) ?> EGP</div>

                    <div class="product-stock <?= $isLow ? 'low' : ($isOut ? 'out' : '') ?>">
                        <?php if ($isOut): ?>
                            <i class="bi bi-x-circle"></i> Out of stock
                        <?php elseif ($isLow): ?>
                            <i class="bi bi-exclamation-triangle"></i> Low: <?= $p['stock_qty'] ?> left
                        <?php else: ?>
                            <i class="bi bi-check-circle"></i> In stock: <?= $p['stock_qty'] ?>
                        <?php endif; ?>
                    </div>

                    <?php if (!$isOut): ?>
                        <div class="product-actions">
                            <input
                                type="number"
                                class="qty-input"
                                id="qty-<?= $p['id'] ?>"
                                value="1" min="1"
                                max="<?= $p['stock_qty'] ?>">
                            <button
                                class="btn btn-primary btn-sm"
                                style="flex:1"
                                onclick="addToCart(<?= $p['id'] ?>, <?= $p['stock_qty'] ?>)">
                                <i class="bi bi-plus-lg"></i> Add
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    /* ── Search Bar Enhancements ── */
    .search-box {
        position: relative;
        width: 100%;
        max-width: 500px; /* لتحديد عرض مناسب للبحث */
    }

    .search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: var(--surface-2);
        border: 1px solid var(--border);
        cursor: pointer;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        transition: all var(--transition);
        font-size: 10px;
    }

    .search-clear:hover {
        background: var(--danger-light);
        color: var(--danger);
        border-color: var(--danger-light);
    }

    /* ── Product Card & Image Enhancements ── */
    .product-name {
        font-size: 15px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.4;
        transition: color var(--transition);
    }

    .product-card:hover .product-name {
        color: var(--primary);
    }

    .product-img {
        width: 100%;
        height: 140px; /* مساحة أكبر للصورة */
        border-radius: var(--radius);
        overflow: hidden;
        background: #ffffff; /* خلفية بيضاء تبرز المنتجات */
        border: 1px solid var(--border);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 10px;
        transition: all var(--transition);
    }

    .product-card:hover .product-img {
        border-color: var(--primary-light);
        box-shadow: 0 4px 12px rgba(12, 63, 130, 0.05); /* وهج ناعم جداً */
    }

    .product-img img {
        width: 100%;
        height: 100%;
        object-fit: contain; /* عشان الصورة متتقصش */
        transition: transform 0.4s ease;
    }

    .product-card:hover .product-img img {
        transform: scale(1.1); /* زووم احترافي عند الهوفر */
    }

    .product-img-fallback {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 38px;
        color: var(--text-muted);
        opacity: .6;
        transition: color var(--transition);
    }

    .product-card:hover .product-img-fallback {
        color: var(--primary);
        opacity: 1;
    }

    /* ── Skeleton Loading Animation ── */
    .skeleton {
        background: linear-gradient(90deg, var(--surface-2) 25%, #e2e8f0 50%, var(--surface-2) 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite linear; /* أنيميشن أنعم */
        border-radius: var(--radius);
    }

    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>

<script>
    const categoryId = <?= (int)$categoryId ?>;

    // ── Live Search ───────────────────────────────────────────────────
    let searchTimer;

    function liveSearch(term) {
        const clearBtn = document.getElementById('clearBtn');
        clearBtn.style.display = term.length > 0 ? 'flex' : 'none';

        clearTimeout(searchTimer);

        if (term.length === 0) {
            loadProducts('');
            return;
        }

        if (term.length < 2) return;

        searchTimer = setTimeout(() => loadProducts(term), 400);
    }

    function clearSearch() {
        document.getElementById('searchInput').value = '';
        document.getElementById('clearBtn').style.display = 'none';
        loadProducts('');
    }

    async function loadProducts(term) {
        const container = document.getElementById('productsContainer');

        // Show loading with new modern skeleton structure
        container.innerHTML = `
        <div class="products-grid">
            ${Array(6).fill('<div class="product-card"><div class="skeleton" style="height:140px;margin-bottom:12px"></div><div class="skeleton" style="height:18px;width:70%;margin-bottom:8px"></div><div class="skeleton" style="height:14px;width:50%;margin-bottom:12px"></div><div class="skeleton" style="height:24px;width:40%"></div></div>').join('')}
        </div>`;

        try {
            const url = term.length >= 2 ?
                BASE + '/search/products?q=' + encodeURIComponent(term) + '&category_id=' + categoryId :
                BASE + '/pos/products?id=' + categoryId + '&ajax=1';

            const res = await fetch(url);
            const json = await res.json();

            if (json.status === 'success') {
                renderProducts(json.data.results ?? [], term);
                document.getElementById('productCount').textContent =
                    (json.data.count ?? 0) + ' product' + (json.data.count !== 1 ? 's' : '');
            }
        } catch (e) {
            // fallback to page reload
            window.location.href = BASE + '/pos/products?id=' + categoryId + '&q=' + encodeURIComponent(term);
        }
    }

    function renderProducts(products, term) {
        const container = document.getElementById('productsContainer');

        if (products.length === 0) {
            container.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon"><i class="bi bi-box-seam"></i></div>
                <div class="empty-state-title">No products found</div>
                <div class="empty-state-text">Try a different search term.</div>
            </div>`;
            return;
        }

        const grid = products.map(p => {
            const isLow = p.stock_qty > 0 && p.stock_qty <= p.alert_stock;
            const isOut = p.stock_qty <= 0;

            const imgHtml = p.image ?
                `<img src="${BASE}/${p.image}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
               <div class="product-img-fallback" style="display:none"><i class="bi bi-box-seam"></i></div>` :
                `<div class="product-img-fallback"><i class="bi bi-box-seam"></i></div>`;

            const stockHtml = isOut ?
                `<div class="product-stock out"><i class="bi bi-x-circle"></i> Out of stock</div>` :
                isLow ?
                `<div class="product-stock low"><i class="bi bi-exclamation-triangle"></i> Low: ${p.stock_qty} left</div>` :
                `<div class="product-stock"><i class="bi bi-check-circle"></i> In stock: ${p.stock_qty}</div>`;

            const actionsHtml = !isOut ?
                `<div class="product-actions">
                <input type="number" class="qty-input" id="qty-${p.id}" value="1" min="1" max="${p.stock_qty}">
                <button class="btn btn-primary btn-sm" style="flex:1" onclick="addToCart(${p.id}, ${p.stock_qty})">
                    <i class="bi bi-plus-lg"></i> Add
                </button>
               </div>` :
                '';

            // Fixed the structure to match the new PHP layout perfectly
            return `
            <div class="product-card ${isLow ? 'low-stock' : ''} ${isOut ? 'out-of-stock' : ''}">
                <div class="product-img">${imgHtml}</div>
                <a href="${BASE}/pos/product?id=${p.id}" class="product-name" style="text-decoration:none;">${p.name}</a>
                
                <div style="display: flex; flex-direction: column; gap: 4px; margin-bottom: 8px;">
                    <div class="product-sku"><i class="bi bi-upc"></i> ${p.sku}</div>
                    ${p.part_number ? `<div class="product-sku"><i class="bi bi-hash"></i> ${p.part_number}</div>` : ''}
                    ${p.location_code ? `<div class="product-location"><i class="bi bi-geo-alt"></i> ${p.location_code}</div>` : ''}
                </div>

                <div class="product-price">${parseFloat(p.sell_price).toFixed(2)} EGP</div>
                ${stockHtml}
                ${actionsHtml}
            </div>`;
        }).join('');

        container.innerHTML = `<div class="products-grid">${grid}</div>`;
    }

    // ── Add to Cart ───────────────────────────────────────────────────
    async function addToCart(productId, maxStock) {
        const qtyInput = document.getElementById('qty-' + productId);
        const quantity = parseInt(qtyInput?.value) || 1;

        if (quantity < 1 || quantity > maxStock) {
            showToast('Invalid quantity.', 'error');
            return;
        }

        try {
            const res = await fetch(BASE + '/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity
                }),
            });
            const json = await res.json();

            if (json.status === 'success') {
                document.getElementById('cartCount').textContent = json.data.cart_count;
                if (qtyInput) qtyInput.value = 1;
                showToast('Added to cart', 'success');
            } else {
                showToast(json.message, 'error');
            }
        } catch (e) {
            showToast('Something went wrong.', 'error');
        }
    }
</script>