<?php
session_start();

date_default_timezone_set('Asia/Ho_Chi_Minh');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

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
    echo 'Database connection failed.';
    exit;
}

$currentUser = null;
$isAdmin = false;
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$currentUser = $stmt->fetch();

if ($currentUser && $currentUser['role'] === 'admin') {
    $isAdmin = true;
}

if ($isAdmin) {
    $stmt = $pdo->query("
        SELECT t.id, t.user_id, u.username, t.title, t.amount, t.currency, t.type, t.category, t.date, t.created_at, t.updated_at
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.date ASC, t.id ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT t.id, t.user_id, u.username, t.title, t.amount, t.currency, t.type, t.category, t.date, t.created_at, t.updated_at
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.user_id = :user_id
        ORDER BY t.date ASC, t.id ASC
    ");
    $stmt->execute([':user_id' => $userId]);
}

$transactions = $stmt->fetchAll();

$dailyTotals = [];
foreach ($transactions as $tx) {
    $date = $tx['date'];
    if (!isset($dailyTotals[$date])) {
        $dailyTotals[$date] = ['income' => 0, 'expense' => 0, 'income_usd' => 0, 'expense_usd' => 0];
    }
    $amt = (float)$tx['amount'];
    $curr = strtoupper(trim((string)($tx['currency'] ?? 'KHR')));
    if ($tx['type'] === 'income') {
        if ($curr === 'USD') {
            $dailyTotals[$date]['income_usd'] += $amt;
        } else {
            $dailyTotals[$date]['income'] += $amt;
        }
    } else {
        if ($curr === 'USD') {
            $dailyTotals[$date]['expense_usd'] += $amt;
        } else {
            $dailyTotals[$date]['expense'] += $amt;
        }
    }
}

$totalIncomeKHR = 0;
$totalIncomeUSD = 0;
$totalExpenseKHR = 0;
$totalExpenseUSD = 0;
foreach ($dailyTotals as $d) {
    $totalIncomeKHR += $d['income'];
    $totalIncomeUSD += $d['income_usd'];
    $totalExpenseKHR += $d['expense'];
    $totalExpenseUSD += $d['expense_usd'];
}

function formatDateTime($isoString)
{
    if (empty($isoString)) return '';
    $parts = preg_split('/[- :]/', $isoString);
    return $parts[2] . '-' . $parts[1] . '-' . substr($parts[0], -2) . ' ' . $parts[3] . ':' . $parts[4];
}

function formatCurrencyKHR($value)
{
    return number_format($value, 2) . ' ៛';
}

function formatCurrencyUSD($value)
{
    return '$' . number_format($value, 2);
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>របាយការណ៍ប្រតិបត្តិការ - Transaction Report</title>
    <link rel="icon" href="uzita.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Kantumruy Pro', 'Noto Sans Khmer', 'Khmer OS Battambang', 'Siemreap', system-ui, sans-serif;
            color: #1E293B;
            background: #F8FAFC;
            font-size: 12px;
            line-height: 1.6;
            padding: 20px;
        }

        .report-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            overflow: hidden;
        }

        /* Header */
        .report-header {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            color: #fff;
            padding: 32px;
            text-align: center;
        }

        .report-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.01em;
        }

        .report-header .subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 16px;
        }

        .report-header .user-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Summary Cards */
        .summary-section {
            padding: 24px 32px;
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        .summary-card {
            background: #F8FAFC;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #E2E8F0;
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .summary-card-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }

        .summary-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .summary-icon.income { background: #ECFDF5; color: #059669; }
        .summary-icon.expense { background: #FEF2F2; color: #DC2626; }
        .summary-icon.balance { background: #EEF2FF; color: #4F46E5; }

        .summary-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .summary-value {
            font-size: 18px;
            font-weight: 700;
            color: #1E293B;
            line-height: 1.2;
        }

        .summary-value.income { color: #059669; }
        .summary-value.expense { color: #DC2626; }
        .summary-value.balance { color: #1E293B; }

        .summary-sub {
            font-size: 11px;
            color: #64748B;
            margin-top: 4px;
        }

        /* Content */
        .report-content {
            padding: 24px 32px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #E2E8F0;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        thead th {
            background: #F1F5F9;
            padding: 10px 12px;
            border-bottom: 2px solid #E2E8F0;
            text-align: left;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #475569;
        }

        tbody td {
            padding: 10px 12px;
            border-bottom: 1px solid #F1F5F9;
            color: #334155;
        }

        tbody tr:hover td {
            background: #F8FAFC;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            display: inline-block;
            letter-spacing: 0.02em;
        }

        .badge.income {
            background: #ECFDF5;
            color: #065F46;
        }

        .badge.expense {
            background: #FEF2F2;
            color: #991B1B;
        }

        .text-income {
            color: #059669;
            font-weight: 600;
        }

        .text-expense {
            color: #DC2626;
            font-weight: 600;
        }

        /* Daily Sections */
        .daily-section {
            margin-bottom: 24px;
            background: #F8FAFC;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #E2E8F0;
        }

        .daily-section h3 {
            font-size: 14px;
            font-weight: 700;
            color: #1E293B;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #E2E8F0;
        }

        .daily-section table {
            margin-bottom: 0;
        }

        .daily-section table thead th {
            background: #fff;
            font-size: 10px;
        }

        .daily-total-row {
            font-weight: 700;
            background: #fff !important;
        }

        .daily-total-row td {
            border-top: 2px solid #E2E8F0;
            color: #1E293B;
        }

        /* No Data */
        .no-data {
            text-align: center;
            padding: 60px 20px;
            color: #94A3B8;
        }

        .no-data-icon {
            font-size: 48px;
            display: block;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .no-data-text {
            font-size: 14px;
            font-weight: 500;
        }

        /* Footer */
        .footer {
            padding: 20px 32px;
            border-top: 1px solid #E2E8F0;
            text-align: center;
            font-size: 11px;
            color: #94A3B8;
            background: #F8FAFC;
        }

        /* Buttons */
        .no-print {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 32px;
            background: #fff;
            border-bottom: 1px solid #E2E8F0;
        }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.15s;
            font-family: inherit;
        }

        .btn-primary {
            background: #4F46E5;
            color: #fff;
        }

        .btn-primary:hover {
            background: #4338CA;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }

        .btn-secondary {
            background: #fff;
            color: #475569;
            border: 1px solid #E2E8F0;
        }

        .btn-secondary:hover {
            background: #F8FAFC;
            border-color: #CBD5E1;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
                background: #fff;
            }

            .report-container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }

            .no-print {
                display: none !important;
            }

            .summary-card {
                break-inside: avoid;
            }

            .daily-section {
                break-inside: avoid;
            }
        }

        /* Page layout for print */
        @page {
            margin: 10mm;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="no-print">
            <div class="btn-group">
                <button class="btn btn-secondary" onclick="window.close()">← Back</button>
            </div>
            <div class="btn-group">
                <button class="btn btn-primary" onclick="window.print()">🖨️ Print / PDF</button>
            </div>
        </div>

        <div class="report-header">
            <h1>របាយការណ៍ប្រតិបត្តិការ</h1>
            <p class="subtitle">Transaction Report</p>
            <div class="user-info">
                <span>👤 <?= htmlspecialchars($currentUser['username']) ?></span>
                <span style="opacity:0.6;">|</span>
                <span style="text-transform:capitalize;"><?= htmlspecialchars($currentUser['role']) ?></span>
                <span style="opacity:0.6;">|</span>
                <span><?= date('d M Y') ?></span>
            </div>
        </div>

        <?php if (empty($transactions)): ?>
            <div class="no-data">
                <span class="no-data-icon">📊</span>
                <div class="no-data-text">មិនមានប្រតិបត្តិការទេ។</div>
                <div style="font-size:12px;color:#94A3B8;margin-top:8px;">No transactions found</div>
            </div>
        <?php else: ?>
            <div class="summary-section">
                <div class="summary-grid">
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-icon income">📈</div>
                            <div class="summary-label">Total Income</div>
                        </div>
                        <div class="summary-value income">
                            <?= formatCurrencyKHR($totalIncomeKHR) ?>
                            <?php if ($totalIncomeUSD > 0): ?>
                                <span style="font-size:0.7em;font-weight:500;color:#64748B;">/ <?= formatCurrencyUSD($totalIncomeUSD) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="summary-sub">ចំណូលសរុប</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-icon expense">📉</div>
                            <div class="summary-label">Total Expense</div>
                        </div>
                        <div class="summary-value expense">
                            <?= formatCurrencyKHR($totalExpenseKHR) ?>
                            <?php if ($totalExpenseUSD > 0): ?>
                                <span style="font-size:0.7em;font-weight:500;color:#64748B;">/ <?= formatCurrencyUSD($totalExpenseUSD) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="summary-sub">ចំណាយសរុប</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-card-header">
                            <div class="summary-icon balance">💰</div>
                            <div class="summary-label">Total Balance</div>
                        </div>
                        <div class="summary-value balance">
                            <?= formatCurrencyKHR($totalIncomeKHR - $totalExpenseKHR) ?>
                            <?php if ($totalIncomeUSD - $totalExpenseUSD != 0): ?>
                                <span style="font-size:0.7em;font-weight:500;color:#64748B;">/ <?= formatCurrencyUSD($totalIncomeUSD - $totalExpenseUSD) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="summary-sub">សមតុល្យសរុប</div>
                    </div>
                </div>
            </div>

            <div class="report-content">
                <h2 class="section-title">Transaction Details / សេចក្តីលម្អិតប្រតិបត្តិការ</h2>
                <table>
                    <thead>
                        <tr>
                            <th style="width:15%">Date</th>
                            <th style="width:25%">Description</th>
                            <th style="width:10%">Name</th>
                            <th style="width:10%">Type</th>
                            <th style="width:15%">Amount</th>
                            <th style="width:25%">Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $tx):
                            $amt = (float)$tx['amount'];
                            $isIncome = $tx['type'] === 'income';
                            $curr = strtoupper(trim((string)($tx['currency'] ?? 'KHR')));
                        ?>
                            <tr>
                                <td><?= htmlspecialchars(formatDateTime($tx['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($tx['title']) ?></strong></td>
                                <td><?= htmlspecialchars($tx['username'] ?: '-') ?></td>
                                <td><span class="badge <?= $tx['type'] ?>"><?= $isIncome ? 'Income' : 'Expense' ?></span></td>
                                <td class="<?= $isIncome ? 'text-income' : 'text-expense' ?>" style="font-weight:600;">
                                    <?php if ($curr === 'USD'): ?>
                                        <?= formatCurrencyUSD($amt) ?>
                                    <?php else: ?>
                                        <?= formatCurrencyKHR($amt) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($tx['category']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <h2 class="section-title">Daily Totals / សរុបតាមថ្ងៃ</h2>
                <?php foreach ($dailyTotals as $date => $totals): ?>
                    <div class="daily-section">
                        <h3>📅 <?= htmlspecialchars($date) ?></h3>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:25%">Currency</th>
                                    <th style="width:25%">Income</th>
                                    <th style="width:25%">Expense</th>
                                    <th style="width:25%">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($totals['income'] != 0 || $totals['expense'] != 0): ?>
                                    <tr>
                                        <td><strong>🇰🇭 KHR (Riel)</strong></td>
                                        <td class="text-income" ><?= formatCurrencyKHR($totals['income']) ?></td>
                                        <td class="text-expense"><?= formatCurrencyKHR($totals['expense']) ?></td>
                                        <td style="font-weight:600;"><?= formatCurrencyKHR($totals['income'] - $totals['expense']) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($totals['income_usd'] != 0 || $totals['expense_usd'] != 0): ?>
                                    <tr>
                                        <td><strong>🇺🇸 USD (Dollar)</strong></td>
                                        <td class="text-income" ><?= formatCurrencyUSD($totals['income_usd']) ?></td>
                                        <td class="text-expense"><?= formatCurrencyUSD($totals['expense_usd']) ?></td>
                                        <td style="font-weight:600;"><?= formatCurrencyUSD($totals['income_usd'] - $totals['expense_usd']) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="footer">
            <p>Payment Tracker Report © <?= date('Y') ?> | Generated: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
