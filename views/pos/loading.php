<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading — Pos-Cashier</title>
    <link rel="stylesheet" href="/MiniLedger/CASHIER/public/assets/css/app.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg);
            margin: 0;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .loading-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl, 16px);
            padding: 48px 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            max-width: 380px;
            width: 100%;
            margin: 20px;
            animation: slideUpFade 0.5s ease-out forwards;
        }

        @keyframes slideUpFade {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .loading-logo {
            width: 64px;
            height: 64px;
            background: var(--primary-light, #eff6ff);
            border-radius: var(--radius-lg, 12px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 28px;
            color: var(--primary, #3b82f6);
            animation: pulseLogo 2s infinite ease-in-out;
            box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4);
        }

        @keyframes pulseLogo {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.4); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }

        .loading-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .loading-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 32px;
        }

        /* Modern Dual Ring Spinner */
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--surface-2, #f1f5f9);
            border-top-color: var(--primary, #3b82f6);
            border-radius: 50%;
            animation: spin 0.8s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            margin: 0 auto 16px;
        }

        @keyframes spin { 
            to { transform: rotate(360deg); } 
        }

        .loading-step {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            min-height: 20px; /* To prevent jumping text */
        }

        /* Error Box Styling */
        .error-box {
            background: var(--danger-light, #fef2f2);
            border: 1px solid var(--danger-border, #fecaca);
            border-radius: var(--radius, 8px);
            padding: 12px 16px;
            font-size: 13px;
            color: var(--danger, #ef4444);
            margin-top: 20px;
            display: none;
            align-items: center;
            gap: 10px;
            text-align: left;
            animation: fadeIn 0.3s ease-in;
        }

        .error-box i {
            font-size: 18px;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Retry Button */
        .btn-retry {
            width: 100%;
            margin-top: 20px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 600;
        }
    </style>
</head>
<body>

<div class="loading-card">

    <div class="loading-logo">
        <i class="bi bi-lightning-charge-fill"></i>
    </div>

    <div class="loading-title">Setting up your POS</div>
    <div class="loading-subtitle">Please wait while we load your data...</div>

    <div class="loading-spinner" id="spinner"></div>

    <div class="loading-step" id="loadingStep">
        Connecting to server...
    </div>

    <div class="error-box" id="errorBox">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <span id="errorMsg"></span>
    </div>

    <div style="display:none;" id="retryWrap">
        <button class="btn btn-primary btn-retry" onclick="startPull()">
            <i class="bi bi-arrow-repeat"></i> Try Again
        </button>
    </div>

</div>

<script>
const BASE = '/MiniLedger/CASHIER/public';

async function startPull() {
    const step      = document.getElementById('loadingStep');
    const spinner   = document.getElementById('spinner');
    const errorBox  = document.getElementById('errorBox');
    const retryWrap = document.getElementById('retryWrap');
    const logo      = document.querySelector('.loading-logo');

    // إعادة ضبط الواجهة
    spinner.style.display   = 'block';
    errorBox.style.display  = 'none';
    retryWrap.style.display = 'none';

    try {
        step.textContent = 'Connecting to server...';

        const res  = await fetch(BASE + '/sync/pull', { method: 'POST' });
        const json = await res.json();

        if (json.status === 'success') {
            // حالة النجاح الكامل
            handleSuccess(step, json.data.redirect);
        } 
        else {
            // التحقق من "فواتير غير مزامنة" لتخطي شاشة التحميل
            const isPendingInvoices = json.message && (
                json.message.toLowerCase().includes('pending') || 
                json.message.toLowerCase().includes('invoice') ||
                json.message.toLowerCase().includes('unsynced')
            );

            if (isPendingInvoices) {
                // تخطي ذكي: السيرفر رد بوجود فواتير معلقة، إذن الجلسة صالحة
                step.textContent = 'Pending invoices found. Entering POS...';
                
                // تغيير لون اللوجو للبرتقالي (تنبيه بسيط) بدل الأحمر
                logo.style.animation = 'none';
                logo.style.background = '#fff7ed'; 
                logo.style.color = '#f97316';

                setTimeout(() => {
                    window.location.href = BASE + '/pos/dashboard';
                }, 1200);
            } else {
                // خطأ حقيقي يمنع الدخول
                showError(json.message || 'Failed to load data.');
            }
        }

    } catch (e) {
        // فشل الاتصال تماماً بالسيرفر المحلي
        showError('Could not connect to server. Check your local environment.');
    }
}

function handleSuccess(step, redirectPath) {
    step.textContent = 'Data loaded! Redirecting...';
    const logo = document.querySelector('.loading-logo');
    
    logo.style.animation = 'none';
    logo.style.background = 'var(--success-light, #dcfce7)';
    logo.style.color = 'var(--success, #22c55e)';

    setTimeout(() => {
        window.location.href = BASE + redirectPath;
    }, 800);
}

function showError(msg) {
    const logo = document.querySelector('.loading-logo');
    document.getElementById('spinner').style.display   = 'none';
    document.getElementById('errorBox').style.display  = 'flex';
    document.getElementById('retryWrap').style.display = 'block';
    document.getElementById('errorMsg').textContent    = msg;
    document.getElementById('loadingStep').textContent = 'Sync interrupted.';
    
    logo.style.animation = 'none';
    logo.style.background = 'var(--danger-light, #fef2f2)';
    logo.style.color = 'var(--danger, #ef4444)';
}

document.addEventListener('DOMContentLoaded', () => {
    startPull();
});
</script>

</body>
</html>