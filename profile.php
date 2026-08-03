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

$stmt = $pdo->prepare("SELECT COUNT(*) as cnt, COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) as income, COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) as expense FROM transactions WHERE user_id = :user_id");
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
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>គណនី - <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="uzita.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4">
    <style>
        .profile-page { max-width: 720px; margin: 0 auto; padding: 2rem 1rem; }
        .profile-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .profile-avatar { width: 64px; height: 64px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.75rem; font-weight: 700; }
        .profile-title { font-size: 1.25rem; font-weight: 700; color: var(--text); }
        .profile-subtitle { font-size: 0.85rem; color: var(--text-muted); }
        .profile-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .profile-card { background: var(--card); border-radius: var(--radius); padding: 1.25rem; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .profile-card h4 { font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; font-weight: 600; }
        .profile-card .value { font-size: 1.1rem; font-weight: 700; color: var(--text); }
        .profile-card .meta { font-size: 0.82rem; color: var(--text-sec); margin-top: 0.25rem; }
        .profile-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }
        .profile-actions .btn-export { text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
        @media (max-width: 640px) {
            .profile-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
    <div class="profile-page">
        <div class="card" style="margin-bottom: 1rem;">
            <div class="profile-header">
                <div class="profile-avatar"><?php echo strtoupper(mb_substr($user['username'], 0, 1)); ?></div>
                <div>
                    <div class="profile-title"><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="profile-subtitle"><?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?> • Created <?php echo htmlspecialchars($user['created_at'] ? date('d M Y', strtotime($user['created_at'])) : '-', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <div class="profile-card">
                <h4>Transactions</h4>
                <div class="value"><?php echo (int)$txCount; ?></div>
                <div class="meta">Total records</div>
            </div>
            <div class="profile-card">
                <h4>Income</h4>
                <div class="value" style="color: var(--income);"><?php echo number_format((float)$stats['income'], 2); ?> KHR</div>
                <div class="meta">Total income</div>
            </div>
            <div class="profile-card">
                <h4>Expense</h4>
                <div class="value" style="color: var(--expense);"><?php echo number_format((float)$stats['expense'], 2); ?> KHR</div>
                <div class="meta">Total expense</div>
            </div>
            <div class="profile-card">
                <h4>Last Activity</h4>
                <div class="value"><?php echo $lastTx ? htmlspecialchars(date('d M Y H:i', strtotime($lastTx['created_at'])), ENT_QUOTES, 'UTF-8') : '-'; ?></div>
                <div class="meta">Latest transaction</div>
            </div>
        </div>

        <div class="profile-actions">
            <a href="index.php" class="btn-export">Back to Dashboard</a>
            <button class="btn-export" onclick="window.print()">Print Profile</button>
            <button class="btn-link" id="btn-change-username" style="display:none;">Change Username</button>
            <button class="btn-link" id="btn-change-password" style="display:none;">Change Password</button>
        </div>
    </div>

    <script>
        const API_URL = 'api.php';

        function escapeHtml(str) {
            if (typeof str !== 'string') str = String(str);
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function showAlert(message, type) {
            type = type || 'error';
            const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✕';
            const overlay = document.createElement('div');
            overlay.className = 'custom-alert-overlay';
            overlay.innerHTML = '<div class="custom-alert-card"><div class="custom-alert-header ' + type + '"><span class="alert-icon">' + icon + '</span><h4>' + (type === 'success' ? 'Success' : type === 'warning' ? 'Warning' : 'Error') + '</h4></div><div class="custom-alert-body">' + escapeHtml(message) + '</div><div class="custom-alert-footer"><button class="btn-ok" onclick="this.closest(\'.custom-alert-overlay\').remove()">OK</button></div></div>';
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

        async function fetchProfileStatus() {
            try {
                const response = await fetch(`${API_URL}/user/profile-status`);
                const data = await response.json();
                const usernameBtn = document.getElementById('btn-change-username');
                const passwordBtn = document.getElementById('btn-change-password');

                if (data.isAdmin) {
                    usernameBtn.style.display = 'inline-block';
                    passwordBtn.style.display = 'inline-block';
                    return;
                }

                usernameBtn.style.display = data.canChangeUsername ? 'inline-block' : 'none';
                passwordBtn.style.display = data.canChangePassword ? 'inline-block' : 'none';

                if (!data.canChangeUsername || !data.canChangePassword) {
                    const msg = [];
                    if (!data.canChangeUsername) msg.push('Username locked: ' + data.usernameLockedFor + ' remaining');
                    if (!data.canChangePassword) msg.push('Password locked: ' + data.passwordLockedFor + ' remaining');
                    showAlert(msg.join('<br>'), 'warning');
                }
            } catch (error) {
                console.error('Failed to fetch profile status:', error);
            }
        }

        document.getElementById('btn-change-username').addEventListener('click', async () => {
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
    </script>
