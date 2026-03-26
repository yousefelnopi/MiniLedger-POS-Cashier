const BASE = '/MiniLedger/CASHIER/public';

/*
|--------------------------------------------------------------------------
| Toast Notifications
|--------------------------------------------------------------------------
*/
function showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast       = document.createElement('div');
    toast.className   = `toast toast-${type}`;
    toast.textContent = message;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity    = '0';
        toast.style.transform  = 'translateX(100%)';
        toast.style.transition = 'all .3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

/*
|--------------------------------------------------------------------------
| Sync Status
|--------------------------------------------------------------------------
*/
function updateSyncStatus(pendingCount, online = true) {
    const dot   = document.getElementById('syncDot');
    const label = document.getElementById('syncLabel');
    const badge = document.getElementById('pendingBadge');

    if (!dot || !label) return;

    if (!online) {
        dot.className     = 'sync-dot offline';
        label.textContent = 'Offline';
    } else if (pendingCount > 0) {
        dot.className     = 'sync-dot pending';
        label.textContent = 'Pending';
    } else {
        dot.className     = 'sync-dot online';
        label.textContent = 'Synced';
    }

    if (badge) {
        badge.textContent   = pendingCount;
        badge.style.display = pendingCount > 0 ? 'inline-flex' : 'none';
    }
}

/*
|--------------------------------------------------------------------------
| Auto Sync
|--------------------------------------------------------------------------
*/
async function autoSync() {
    try {
        const statusRes  = await fetch(BASE + '/sync/status');
        const statusJson = await statusRes.json();
        const pendingCount = statusJson.data?.pending_count ?? 0;
        updateSyncStatus(pendingCount, true);

        if (pendingCount > 0) {
            const syncRes  = await fetch(BASE + '/sync/run', { method: 'POST' });
            const syncJson = await syncRes.json();

            if (syncJson.data?.synced > 0) {
                showToast(`${syncJson.data.synced} invoice(s) synced.`, 'success');
                updateSyncStatus(syncJson.data.pending_count ?? 0, true);
            }
            if (syncJson.data?.failed > 0) {
                showToast(`${syncJson.data.failed} invoice(s) failed to sync.`, 'warning');
            }
        }
    } catch (e) {
        updateSyncStatus(0, false);
    }
}

/*
|--------------------------------------------------------------------------
| Manual Sync
|--------------------------------------------------------------------------
*/
async function manualSync() {
    const btn     = document.getElementById('syncBtn');
    const btnText = document.getElementById('syncBtnText');

    if (btn) btn.disabled = true;
    if (btnText) btnText.textContent = 'Syncing...';

    try {
        const res  = await fetch(BASE + '/sync/run', { method: 'POST' });
        const json = await res.json();

        if (json.data?.synced > 0) {
            showToast(`${json.data.synced} invoice(s) sent successfully.`, 'success');
        } else if (json.data?.pending_count === 0) {
            showToast('Everything is synced.', 'info');
        } else if (!json.data?.server_available) {
            showToast('Server unavailable. Will retry automatically.', 'warning');
        }

        updateSyncStatus(json.data?.pending_count ?? 0, json.data?.server_available ?? false);

    } catch (e) {
        showToast('Could not connect to server.', 'error');
        updateSyncStatus(0, false);
    } finally {
        if (btn) btn.disabled = false;
        if (btnText) btnText.textContent = 'Sync';
    }
}

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/
async function doLogout(force = false) {
    try {
        const res  = await fetch(BASE + '/auth/logout', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ force }),
        });
        const json = await res.json();

        if (json.status === 'success') {
            window.location.href = BASE + json.data.redirect;

        } else if (res.status === 409) {
            const count = json.errors?.pending_count ?? 0;
            const ok    = window.confirm(
                `You have ${count} unsent invoice(s).\n\nLogout anyway?`
            );
            if (ok) doLogout(true);

        } else {
            showToast(json.message || 'Logout failed.', 'error');
        }
    } catch (e) {
        showToast('Could not connect to server.', 'error');
    }
}

/*
|--------------------------------------------------------------------------
| Add to Cart
|--------------------------------------------------------------------------
*/
async function addToCart(productId, maxStock) {
    const qtyInput = document.getElementById('qty-' + productId);
    const quantity = parseInt(qtyInput?.value) || 1;

    if (quantity < 1 || quantity > maxStock) {
        showToast('Invalid quantity.', 'error');
        return;
    }

    try {
        const res  = await fetch(BASE + '/cart/add', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ product_id: productId, quantity }),
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

/*
|--------------------------------------------------------------------------
| Init
|--------------------------------------------------------------------------
*/
document.addEventListener('DOMContentLoaded', () => {
    autoSync();
    setInterval(autoSync, 30000);
});


/*
|--------------------------------------------------------------------------
| Confirm Invoice — used in cart page
|--------------------------------------------------------------------------
*/
async function confirmInvoice() {
    const btn = document.getElementById('confirmBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';

    const payload = {
        type        : document.getElementById('invoiceType').value,
        customer_id : document.getElementById('customerId').value || null,
        discount    : parseFloat(document.getElementById('discount').value) || 0,
        tax         : parseFloat(document.getElementById('tax').value) || 0,
    };

    try {
        const res  = await fetch(BASE + '/invoice/store', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const json = await res.json();

        if (json.status === 'success') {
            if (json.data.server_available && json.data.synced > 0) {
                showToast('Invoice saved and sent to server', 'success');
            } else {
                showToast('Invoice saved locally. Will sync when connected.', 'warning');
            }
            setTimeout(() => {
                window.location.href = BASE + '/invoice/preview/' + json.data.invoice_id;
            }, 1200);
        } else {
            showToast(json.message || 'Failed to save invoice.', 'error');
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirm Sale';
        }
    } catch (e) {
        showToast('Could not connect to server.', 'error');
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-check-lg"></i> Confirm Sale';
    }
}