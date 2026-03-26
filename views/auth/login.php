<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Pos-Cashier</title>
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

        .login-wrap {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: fadeInScale 0.4s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .login-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-xl, 16px);
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .login-logo {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo-icon {
            width: 64px;
            height: 64px;
            background: var(--primary-light, #eff6ff);
            border-radius: var(--radius-lg, 12px);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 28px;
            color: var(--primary, #3b82f6);
        }

        .login-logo-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--text-primary);
            letter-spacing: -0.8px;
        }

        .login-logo-title span { color: var(--primary, #3b82f6); }

        .login-logo-sub {
            font-size: 14px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .login-form { display: flex; flex-direction: column; gap: 20px; }

        .input-group { display: flex; flex-direction: column; gap: 8px; }

        .input-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .input-control-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-control-wrap i {
            position: absolute;
            left: 14px;
            color: var(--text-muted);
            font-size: 16px;
        }

        .input-field {
            padding: 12px 14px 12px 42px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md, 10px);
            font-size: 15px;
            color: var(--text-primary);
            outline: none;
            transition: all 0.2s;
            width: 100%;
        }

        .input-field:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            background: #fff;
        }

        /* Error Box with Shake Animation */
        .error-box {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: var(--radius-md, 10px);
            padding: 12px 16px;
            font-size: 13.5px;
            color: #b91c1c;
            display: none;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .btn-login {
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            width: 100%;
            border-radius: var(--radius-md, 10px);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            cursor: pointer;
            transition: transform 0.1s;
        }

        .btn-login:active { transform: scale(0.98); }

        .login-footer {
            text-align: center;
            margin-top: 28px;
            font-size: 13px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        /* Inline Spinner for Button */
        .btn-spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="login-wrap">
    <div class="login-card">

        <div class="login-logo">
            <div class="login-logo-icon">
                <i class="bi bi-lightning-charge-fill"></i>
            </div>
            <div class="login-logo-title">Pos<span>-Cashier</span></div>
            <div class="login-logo-sub">Welcome back! Please sign in.</div>
        </div>

        <div class="error-box" id="errorBox">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span id="errorMsg"></span>
        </div>

        <div class="login-form">

            <div class="input-group">
                <label class="input-label">Email Address</label>
                <div class="input-control-wrap">
                    <i class="bi bi-envelope"></i>
                    <input
                        type="email"
                        id="email"
                        class="input-field"
                        placeholder="name@company.com"
                        autocomplete="email"
                        autofocus
                    >
                </div>
            </div>

            <div class="input-group">
                <label class="input-label">Password</label>
                <div class="input-control-wrap">
                    <i class="bi bi-lock"></i>
                    <input
                        type="password"
                        id="password"
                        class="input-field"
                        placeholder="••••••••"
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button class="btn btn-primary btn-login" id="loginBtn" onclick="doLogin()">
                <div id="loginContent" style="display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Sign In</span>
                </div>
                <div id="loginLoading" style="display: none; align-items: center; gap: 10px;">
                    <div class="btn-spinner"></div>
                    <span>Verifying...</span>
                </div>
            </button>

        </div>
    </div>

    <div class="login-footer">
        <i class="bi bi-shield-lock"></i>
        <span>Secured by MiniLedger POS System</span>
    </div>
</div>

<script>
const BASE = '/MiniLedger/CASHIER/public';

async function doLogin() {
    const email      = document.getElementById('email').value.trim();
    const password   = document.getElementById('password').value;
    const errorBox   = document.getElementById('errorBox');
    const btn        = document.getElementById('loginBtn');
    const content    = document.getElementById('loginContent');
    const loading    = document.getElementById('loginLoading');

    // Reset UI
    errorBox.style.display = 'none';

    if (!email || !password) {
        showError('Please enter both email and password.');
        return;
    }

    // State: Loading
    btn.disabled = true;
    content.style.display = 'none';
    loading.style.display = 'flex';

    try {
        const res = await fetch(BASE + '/auth/login', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ email, password }),
        });

        const json = await res.json();

        if (json.status === 'success') {
            window.location.href = BASE + json.data.redirect;
        } else {
            showError(json.message || 'Invalid credentials. Please try again.');
        }

    } catch (e) {
        showError('Connection failed. Please check your internet.');
    } finally {
        // State: Reset
        btn.disabled = false;
        content.style.display = 'flex';
        loading.style.display = 'none';
    }
}

function showError(msg) {
    const box = document.getElementById('errorBox');
    document.getElementById('errorMsg').textContent = msg;
    box.style.display = 'flex';
    
    // Logic to re-trigger animation if already visible
    box.style.animation = 'none';
    box.offsetHeight; // trigger reflow
    box.style.animation = null; 
}

// Enter Key Support
document.addEventListener('keydown', e => {
    if (e.key === 'Enter') doLogin();
});
</script>

</body>
</html>