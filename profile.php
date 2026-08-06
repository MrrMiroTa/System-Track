<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

$host = "localhost";
$db_name = "tracker_db";
$admin_user = "root";
$admin_pass = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $admin_user, $admin_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed."]);
    exit;
}

if (empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, role, created_at FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$userId = $user['id'];
$isAdmin = $user['role'] === 'admin';

    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(CASE WHEN type = 'income' AND currency = 'KHR' THEN amount ELSE 0 END), 0) as income_khr, COALESCE(SUM(CASE WHEN type = 'expense' AND currency = 'KHR' THEN amount ELSE 0 END), 0) as expense_khr, COALESCE(SUM(CASE WHEN type = 'income' AND currency = 'USD' THEN amount ELSE 0 END), 0) as income_usd, COALESCE(SUM(CASE WHEN type = 'expense' AND currency = 'USD' THEN amount ELSE 0 END), 0) as expense_usd FROM transactions WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $stats = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM transactions WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $txCount = $stmt->fetch()['cnt'];

$lastTx = null;
if ($txCount > 0) {
    $stmt = $pdo->prepare("SELECT created_at FROM transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $lastTx = $stmt->fetch();
}

$roleBadgeClass = $isAdmin ? 'income' : 'expense';
$createdDate = $user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '-';
$lastActivity = $lastTx ? date('d M Y H:i', strtotime($lastTx['created_at'])) : '-';
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="uzita.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4">
    <style>
        .profile-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 2.5rem 1rem;
        }

        .profile-hero {
            background: linear-gradient(135deg, var(--primary) 0%, #7C3AED 100%);
            color: #fff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .profile-hero-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            border: 2px solid rgba(255,255,255,0.35);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .profile-hero-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .profile-hero-subtitle {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.85);
            margin-top: 0.25rem;
        }

        .profile-hero-subtitle .badge {
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            margin-left: 0.5rem;
            border: 1px solid rgba(255,255,255,0.25);
        }

        .profile-hero-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .profile-hero-actions .btn-link {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
            color: #fff;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            text-decoration: none;
            transition: all 0.15s;
            backdrop-filter: blur(4px);
        }

        .profile-hero-actions .btn-link:hover {
            background: rgba(255,255,255,0.28);
            border-color: rgba(255,255,255,0.45);
            transform: translateY(-1px);
        }

        .profile-section {
            margin-bottom: 1.5rem;
        }

        .profile-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.75rem;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .profile-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 1.25rem 1.35rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .profile-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .profile-card-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.6rem;
        }

        .profile-card-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .profile-card-icon.blue { background: var(--primary-soft); color: var(--primary); }
        .profile-card-icon.green { background: var(--income-bg); color: var(--income); }
        .profile-card-icon.red { background: var(--expense-bg); color: var(--expense); }
        .profile-card-icon.slate { background: var(--surface); color: var(--text-sec); }

        .profile-card-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            line-height: 1.3;
        }

        .profile-card-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.01em;
            line-height: 1.2;
        }

        .profile-card-meta {
            font-size: 0.78rem;
            color: var(--text-sec);
            margin-top: 0.25rem;
        }

        .profile-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .profile-actions .spacer {
            flex: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.6rem 1.1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s;
            font-family: var(--font);
            line-height: 1.4;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
            box-shadow: var(--shadow);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: var(--card);
            color: var(--text-sec);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background: var(--surface);
            color: var(--text);
        }

        .btn-danger {
            background: var(--expense-bg);
            color: var(--expense);
            border: 1px solid #FECACA;
        }

        .btn-danger:hover {
            background: #FEE2E2;
        }

        .btn-link {
            background: none;
            border: none;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            padding: 0.5rem 0.2rem;
            text-decoration: none;
            transition: color 0.15s;
        }

        .btn-link:hover { color: var(--primary-hover); }

        @media (max-width: 640px) {
            .profile-hero {
                padding: 1.75rem 1.25rem;
                text-align: center;
                justify-content: center;
            }
            .profile-hero-left {
                flex-direction: column;
                text-align: center;
            }
            .profile-hero-title { font-size: 1.35rem; }
            .profile-grid { grid-template-columns: 1fr; }
            .profile-hero-actions { justify-content: center; }
            .profile-actions { flex-direction: column; align-items: stretch; }
            .profile-actions .spacer { display: none; }
            .profile-actions .btn { justify-content: center; }
        }
        .profile-card-value + .profile-card-value {
            margin-top: 0.15rem;
        }

        .monthly-view {
            display: none;
            margin-top: 1.5rem;
        }

        .monthly-view.active {
            display: block;
        }

        .monthly-view-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .monthly-view-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        .btn-monthly-toggle {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.45rem 0.9rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-family: var(--font);
        }

        .btn-monthly-toggle:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-monthly-toggle.active {
            background: var(--expense);
        }

        .btn-monthly-toggle.active:hover {
            background: #DC2626;
        }

        .monthly-table-wrapper {
            overflow-x: auto;
            border-radius: var(--radius);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .monthly-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card);
            font-size: 0.85rem;
            min-width: 600px;
        }

        .monthly-table th {
            background: var(--surface);
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.72rem;
            padding: 0.7rem 0.85rem;
            border-bottom: 2px solid var(--border);
            text-align: left;
        }

        .monthly-table th:nth-child(2) {
            text-align: center;
        }

        .monthly-table td {
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid var(--border);
        }

        .monthly-table td:nth-child(2) {
            text-align: center;
        }

        .monthly-table tr:last-child td {
            border-bottom: none;
        }

        .monthly-table tbody tr:hover {
            background: var(--surface);
        }

        .month-month {
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
        }

        .month-txns {
            text-align: center;
            color: var(--text-sec);
            font-weight: 500;
        }

        .month-row {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .month-row .currency-primary {
            font-weight: 600;
        }

        .month-row .currency-secondary {
            font-size: 0.78rem;
            color: var(--text-sec);
            font-weight: 400;
        }

        .text-income {
            color: var(--income);
        }

        .text-expense {
            color: var(--expense);
        }

        .text-balance {
            font-weight: 700;
        }

        .text-balance.positive {
            color: var(--income);
        }

        .text-balance.negative {
            color: var(--expense);
        }

        .monthly-empty {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="profile-page">
        <div class="profile-hero">
            <div class="profile-hero-left">
                <div class="profile-avatar"><?php echo strtoupper(mb_substr($user['username'], 0, 1)); ?></div>
                <div>
                    <div class="profile-hero-title"><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-hero-subtitle">
                        <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>
                        <span class="badge"><?php echo htmlspecialchars($isAdmin ? 'Admin' : 'User', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="profile-hero-subtitle" style="margin-top:0.35rem;opacity:0.8;">Joined <?php echo htmlspecialchars($createdDate, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-hero-actions" style="margin-top:0.75rem;">
                        <button class="btn btn-link" id="btn-change-username" style="display:none;color:rgba(255,255,255,0.9);">Change Username</button>
                        <button class="btn btn-link" id="btn-change-password" style="display:none;color:rgba(255,255,255,0.9);">Change Password</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Overview</div>
            <div class="profile-grid">
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon slate">📊</div>
                        <div class="profile-card-label">Transactions</div>
                    </div>
                    <div class="profile-card-value"><?php echo (int)$txCount; ?></div>
                    <div class="profile-card-meta">Total records</div>
                </div>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon green">📈</div>
                        <div class="profile-card-label">Income</div>
                    </div>
                    <div class="profile-card-value" style="color: var(--income);"><?php echo number_format((float)$stats['income_khr'], 2); ?> ៛</div>
                    <div class="profile-card-meta">KHR</div>
                    <div class="profile-card-value" style="color: var(--income); font-size:1.1rem;"><?php echo number_format((float)$stats['income_usd'], 2); ?> $</div>
                    <div class="profile-card-meta">USD</div>
                </div>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon red">📉</div>
                        <div class="profile-card-label">Expense</div>
                    </div>
                    <div class="profile-card-value" style="color: var(--expense);"><?php echo number_format((float)$stats['expense_khr'], 2); ?> ៛</div>
                    <div class="profile-card-meta">KHR</div>
                    <div class="profile-card-value" style="color: var(--expense); font-size:1.1rem;"><?php echo number_format((float)$stats['expense_usd'], 2); ?> $</div>
                    <div class="profile-card-meta">USD</div>
                </div>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon blue">🕒</div>
                        <div class="profile-card-label">Last Activity</div>
                    </div>
                    <div class="profile-card-value" style="font-size:1.05rem;"><?php echo htmlspecialchars($lastActivity, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-card-meta">Latest transaction</div>
                </div>
            </div>
        </div>

        <div class="profile-section">
            <div class="profile-section-title">Actions</div>
            <div class="profile-actions">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <button class="btn btn-secondary" onclick="window.print()">🖨 Print Profile</button>
                <button class="btn btn-secondary" id="btn-monthly-view">📊 View by Month</button>
                <span class="spacer"></span>
                <button class="btn btn-danger" onclick="doLogout()">Logout</button>
            </div>
        </div>

        <div class="profile-section monthly-view" id="monthly-view-section">
            <div class="monthly-view-header">
                <h3>Monthly Breakdown</h3>
                <button class="btn-monthly-toggle" id="btn-close-monthly">✕ Close</button>
            </div>
            <div id="monthly-content">
                <div class="monthly-empty">Loading monthly data...</div>
            </div>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';

        function escapeHtml(str) {
            if (typeof str !== 'string') str = String(str);
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function formatCurrency(value, currency) {
            if (currency === 'KHR') {
                const num = Math.round(value);
                return new Intl.NumberFormat('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }).format(num) + ' ៛';
            } else {
                return new Intl.NumberFormat('en-US', {
                    style: 'currency', currency: 'USD'
                }).format(value);
            }
        }

        function showAlert(message, type) {
            type = type || 'error';
            const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✕';
            const overlay = document.createElement('div');
            overlay.className = 'custom-alert-overlay';
            const safeMessage = escapeHtml(message).replace(/\n/g, '<br>');
            overlay.innerHTML = '<div class="custom-alert-card"><div class="custom-alert-header ' + type + '"><span class="alert-icon">' + icon + '</span><h4>' + (type === 'success' ? 'Success' : type === 'warning' ? 'Warning' : 'Error') + '</h4></div><div class="custom-alert-body">' + safeMessage + '</div><div class="custom-alert-footer"><button class="btn-ok" onclick="this.closest(\'.custom-alert-overlay\').remove()">OK</button></div></div>';
            document.body.appendChild(overlay);
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) overlay.remove();
            });
        }

        function showConfirm(message) {
            return new Promise(function(resolve) {
                const overlay = document.createElement('div');
                overlay.className = 'custom-confirm-overlay';
                overlay.innerHTML = '<div class="custom-confirm-card"><div class="custom-confirm-body">' + escapeHtml(message) + '</div><div class="custom-confirm-footer"><button class="btn-confirm-no">No</button><button class="btn-confirm-yes">Yes</button></div></div>';
                document.body.appendChild(overlay);
                overlay.querySelector('.btn-confirm-no').addEventListener('click', function() { overlay.remove(); resolve(false); });
                overlay.querySelector('.btn-confirm-yes').addEventListener('click', function() { overlay.remove(); resolve(true); });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) { overlay.remove(); resolve(false); }
                });
            });
        }

        function showPrompt(title, message) {
            return new Promise(function(resolve) {
                const overlay = document.createElement('div');
                overlay.className = 'custom-confirm-overlay';
                overlay.innerHTML = '<div class="custom-confirm-card"><div class="custom-confirm-header" style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)"><h4 style="font-size:0.9rem;font-weight:700">' + escapeHtml(title) + '</h4></div><div class="custom-confirm-body"><p>' + escapeHtml(message) + '</p><input type="text" class="edit-input" id="prompt-input" style="width:100%;margin-top:0.5rem;" placeholder="Enter password"></div><div class="custom-confirm-footer"><button class="btn-refirm-no">Cancel</button><button class="btn-confirm-yes">OK</button></div></div>';
                document.body.appendChild(overlay);
                const input = overlay.querySelector('#prompt-input');
                input.focus();
                overlay.querySelector('.btn-refirm-no').addEventListener('click', function() { overlay.remove(); resolve(null); });
                overlay.querySelector('.btn-confirm-yes').addEventListener('click', function() { resolve({ value: input.value }); });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) { overlay.remove(); resolve(null); }
                });
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') { resolve({ value: input.value }); }
                    if (e.key === 'Escape') { overlay.remove(); resolve(null); }
                });
            });
        }

        function showPasswordChangeModal() {
            return new Promise(function(resolve) {
                const overlay = document.createElement('div');
                overlay.className = 'custom-confirm-overlay';
                overlay.innerHTML = '<div class="custom-confirm-card"><div class="custom-confirm-header" style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)"><h4 style="font-size:0.9rem;font-weight:700">Change Password</h4></div><div class="custom-confirm-body"><p style="margin-bottom:0.5rem;color:var(--text-sec);font-size:0.85rem;">Enter current password and new 6-digit password.</p><label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:0.25rem;">Current Password</label><input type="password" class="edit-input" id="current-password-input" style="width:100%;margin-bottom:0.75rem;" placeholder="Current password"><label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:0.25rem;">New Password</label><input type="password" class="edit-input" id="new-password-input" style="width:100%;" placeholder="New 6-digit password" maxlength="6"></div><div class="custom-confirm-footer"><button class="btn-refirm-no">Cancel</button><button class="btn-confirm-yes">Change Password</button></div></div>';
                document.body.appendChild(overlay);
                const currentInput = overlay.querySelector('#current-password-input');
                const newInput = overlay.querySelector('#new-password-input');
                currentInput.focus();
                overlay.querySelector('.btn-refirm-no').addEventListener('click', function() { overlay.remove(); resolve(null); });
                overlay.querySelector('.btn-confirm-yes').addEventListener('click', function() {
                    const currentPassword = currentInput.value.trim();
                    const newPassword = newInput.value.trim();
                    if (!currentPassword || !newPassword) {
                        showAlert('Both fields are required.', 'warning');
                        return;
                    }
                    if (!/^\d{6}$/.test(newPassword)) {
                        showAlert('New password must be exactly 6 digits.', 'warning');
                        return;
                    }
                    overlay.remove();
                    resolve({ currentPassword: currentPassword, newPassword: newPassword });
                });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) { overlay.remove(); resolve(null); }
                });
                currentInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') newInput.focus();
                });
                newInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') { overlay.querySelector('.btn-confirm-yes').click(); }
                    if (e.key === 'Escape') { overlay.remove(); resolve(null); }
                });
            });
        }

        function showUsernameChangeModal() {
            return new Promise(function(resolve) {
                const overlay = document.createElement('div');
                overlay.className = 'custom-confirm-overlay';
                overlay.innerHTML = '<div class="custom-confirm-card"><div class="custom-confirm-header" style="padding:1rem 1.25rem;border-bottom:1px solid var(--border)"><h4 style="font-size:0.9rem;font-weight:700">Change Username</h4></div><div class="custom-confirm-body"><p style="margin-bottom:0.5rem;color:var(--text-sec);font-size:0.85rem;">Enter current password and new username.</p><label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:0.25rem;">Current Password</label><input type="password" class="edit-input" id="current-password-username-input" style="width:100%;margin-bottom:0.75rem;" placeholder="Current password"><label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);display:block;margin-bottom:0.25rem;">New Username</label><input type="text" class="edit-input" id="new-username-input" style="width:100%;" placeholder="New username"></div><div class="custom-confirm-footer"><button class="btn-refirm-no">Cancel</button><button class="btn-confirm-yes">Change Username</button></div></div>';
                document.body.appendChild(overlay);
                const currentInput = overlay.querySelector('#current-password-username-input');
                const newInput = overlay.querySelector('#new-username-input');
                currentInput.focus();
                overlay.querySelector('.btn-refirm-no').addEventListener('click', function() { overlay.remove(); resolve(null); });
                overlay.querySelector('.btn-confirm-yes').addEventListener('click', function() {
                    const currentPassword = currentInput.value.trim();
                    const newUsername = newInput.value.trim();
                    if (!currentPassword || !newUsername) {
                        showAlert('Both fields are required.', 'warning');
                        return;
                    }
                    if (newUsername.length < 3 || newUsername.length > 100) {
                        showAlert('Username must be between 3 and 100 characters.', 'warning');
                        return;
                    }
                    overlay.remove();
                    resolve({ currentPassword: currentPassword, newUsername: newUsername });
                });
                overlay.addEventListener('click', function(e) {
                    if (e.target === overlay) { overlay.remove(); resolve(null); }
                });
                currentInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') newInput.focus();
                });
                newInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') { overlay.querySelector('.btn-confirm-yes').click(); }
                    if (e.key === 'Escape') { overlay.remove(); resolve(null); }
                });
            });
        }

        function doLogout() {
            fetch(`${API_URL}?action=/logout`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                body: JSON.stringify({})
            }).then(() => {
                window.location.href = 'index.php';
            }).catch(() => {
                window.location.href = 'index.php';
            });
        }

        async function fetchProfileStatus() {
            try {
                const response = await fetch(`${API_URL}/user/profile-status`);
                const data = await response.json();
                const usernameBtn = document.getElementById('btn-change-username');
                const passwordBtn = document.getElementById('btn-change-password');

                if (data.isAdmin) {
                    usernameBtn.style.display = 'inline-flex';
                    passwordBtn.style.display = 'inline-flex';
                    return;
                }

                usernameBtn.style.display = 'inline-flex';
                passwordBtn.style.display = 'inline-flex';

                usernameBtn.dataset.locked = data.canChangeUsername ? 'false' : 'true';
                passwordBtn.dataset.locked = data.canChangePassword ? 'false' : 'true';
                if (!data.canChangeUsername) usernameBtn.dataset.lockMessage = 'Username locked: ' + data.usernameLockedFor + ' remaining';
                if (!data.canChangePassword) passwordBtn.dataset.lockMessage = 'Password locked: ' + data.passwordLockedFor + ' remaining';
            } catch (error) {
                console.error('Failed to fetch profile status:', error);
            }
        }

        document.getElementById('btn-change-username').addEventListener('click', async () => {
            const btn = document.getElementById('btn-change-username');
            if (btn.dataset.locked === 'true') {
                showAlert(btn.dataset.lockMessage || 'Username is currently locked.', 'warning');
                return;
            }
            const result = await showUsernameChangeModal();
            if (result === null) return;
            const currentPassword = result.currentPassword.trim();
            const newUsername = result.newUsername.trim();
            if (!currentPassword || !newUsername) return;

            try {
                const response = await fetch(`${API_URL}/user/profile`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify({ username: newUsername, current_password: currentPassword })
                });
                const data = await response.json();
                if (data.success) {
                    showAlert('Username changed successfully. Please login again.', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1500);
                } else {
                    showAlert(data.error || 'Failed to change username.');
                }
            } catch (error) {
                showAlert('Connection failed. Please try again.');
            }
        });

        document.getElementById('btn-change-password').addEventListener('click', async () => {
            const btn = document.getElementById('btn-change-password');
            if (btn.dataset.locked === 'true') {
                showAlert(btn.dataset.lockMessage || 'Password is currently locked.', 'warning');
                return;
            }
            const result = await showPasswordChangeModal();
            if (result === null) return;
            const currentPassword = result.currentPassword.trim();
            const newPassword = result.newPassword.trim();
            if (!currentPassword || !newPassword) return;

            try {
                const response = await fetch(`${API_URL}/user/password`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json; charset=UTF-8' },
                    body: JSON.stringify({ password: newPassword, current_password: currentPassword })
                });
                const data = await response.json();
                if (data.success) {
                    showAlert('Password changed successfully. Please login again.', 'success');
                    setTimeout(() => window.location.href = 'index.php', 1500);
                } else {
                    showAlert(data.error || 'Failed to change password.');
                }
            } catch (error) {
                showAlert('Connection failed. Please try again.');
            }
        });

        fetchProfileStatus();

        const btnMonthlyView = document.getElementById('btn-monthly-view');
        const monthlySection = document.getElementById('monthly-view-section');
        const monthlyContent = document.getElementById('monthly-content');
        const btnCloseMonthly = document.getElementById('btn-close-monthly');
        let monthlyViewActive = false;

        btnMonthlyView.addEventListener('click', async function() {
            if (monthlyViewActive) {
                monthlySection.classList.remove('active');
                monthlyViewActive = false;
                btnMonthlyView.classList.remove('active');
                return;
            }

            monthlySection.classList.add('active');
            monthlyViewActive = true;
            btnMonthlyView.classList.add('active');
            monthlyContent.innerHTML = '<div class="monthly-empty">Loading monthly data...</div>';

            try {
                const response = await fetch(`${API_URL}/user/monthly-summary`);
                const data = await response.json();

                if (data.error) {
                    monthlyContent.innerHTML = '<div class="monthly-empty">' + escapeHtml(data.error) + '</div>';
                    return;
                }

                if (!Array.isArray(data) || data.length === 0) {
                    monthlyContent.innerHTML = '<div class="monthly-empty">No transaction data available.</div>';
                    return;
                }

                let rows = [];
                rows.push('<div class="monthly-table-wrapper"><table class="monthly-table"><thead><tr>');
                rows.push('<th>Month</th>');
                rows.push('<th>Txns</th>');
                rows.push('<th>Income</th>');
                rows.push('<th>Expense</th>');
                rows.push('<th>Balance</th>');
                rows.push('</tr></thead><tbody>');

                data.forEach(function(m) {
                    const monthLabel = m.month.substring(5, 7) + '/' + m.month.substring(0, 4);
                    const incomeKhr = formatCurrency(m.income_khr, 'KHR');
                    const incomeUsd = formatCurrency(m.income_usd, 'USD');
                    const expenseKhr = formatCurrency(m.expense_khr, 'KHR');
                    const expenseUsd = formatCurrency(m.expense_usd, 'USD');
                    const balanceKhrClass = m.balance_khr >= 0 ? 'positive' : 'negative';
                    const balanceUsdClass = m.balance_usd >= 0 ? 'positive' : 'negative';
                    const balanceKhr = formatCurrency(m.balance_khr, 'KHR');
                    const balanceUsd = formatCurrency(m.balance_usd, 'USD');

                    rows.push('<tr>');
                    rows.push('<td class="month-month">' + escapeHtml(monthLabel) + '</td>');
                    rows.push('<td class="month-txns">' + m.transaction_count + '</td>');
                    rows.push('<td class="text-income"><div class="month-row"><span class="currency-primary">' + incomeKhr + '</span><span class="currency-secondary">' + incomeUsd + '</span></div></td>');
                    rows.push('<td class="text-expense"><div class="month-row"><span class="currency-primary">' + expenseKhr + '</span><span class="currency-secondary">' + expenseUsd + '</span></div></td>');
                    rows.push('<td class="text-balance ' + balanceKhrClass + '"><div class="month-row"><span class="currency-primary">' + balanceKhr + '</span><span class="currency-secondary ' + balanceUsdClass + '">' + balanceUsd + '</span></div></td>');
                    rows.push('</tr>');
                });

                rows.push('</tbody></table></div>');
                monthlyContent.innerHTML = rows.join('');
            } catch (error) {
                monthlyContent.innerHTML = '<div class="monthly-empty">Failed to load monthly data.</div>';
            }
        });

        btnCloseMonthly.addEventListener('click', function() {
            monthlySection.classList.remove('active');
            monthlyViewActive = false;
            btnMonthlyView.classList.remove('active');
        });
    </script>
</body>
</html>
