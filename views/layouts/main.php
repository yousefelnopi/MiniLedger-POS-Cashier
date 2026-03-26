<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Pos-Cashier') ?></title>

    <link rel="stylesheet" href="/MiniLedger/CASHIER/public/assets/css/app.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="/MiniLedger/CASHIER/public/assets/js/app.js" defer></script>
    
    </head>

<body>

    <div class="toast-container" id="toastContainer"></div>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="bi bi-shop-window"></i>
            Pos-Cashier
        </div>

        <div class="navbar-spacer"></div>

        <div class="navbar-actions">

            <div class="sync-status" id="syncStatus">
                <span class="sync-dot" id="syncDot"></span>
                <span id="syncLabel">Checking...</span>
                <span class="badge badge-warning" id="pendingBadge" style="display:none">0</span>
            </div>

            <button class="btn btn-secondary btn-sm" id="syncBtn" onclick="manualSync()">
                <i class="bi bi-arrow-repeat"></i>
                <span id="syncBtnText">Sync</span>
            </button>

            <a href="/MiniLedger/CASHIER/public/pos/cart" class="btn btn-secondary btn-sm">
                <i class="bi bi-cart3"></i>
                Cart
                <span class="badge badge-primary" id="cartCount">
                    <?= \App\Services\CartService::count() ?>
                </span>
            </a>

            <a href="/MiniLedger/CASHIER/public/invoices" class="btn btn-secondary btn-sm">
                <i class="bi bi-receipt-cutoff"></i>
                Invoices
            </a>

            <div class="navbar-user">
                <i class="bi bi-person-circle" style="font-size: 16px;"></i>
                <?= htmlspecialchars(\App\Core\Auth::email() ?? '') ?>
            </div>

            <button class="btn btn-logout btn-sm" onclick="doLogout()">
                <i class="bi bi-box-arrow-right"></i>
                Logout
            </button>

        </div>
    </nav>

    <main class="main">
        <?= $content ?? '' ?>
    </main>

</body>

</html>