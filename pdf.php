<?php
session_start();

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
    $curr = $tx['currency'] || 'KHR';
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

function formatCurrencyKHR($value) {
    return number_format($value, 2) . ' ៛';
}

function formatCurrencyUSD($value) {
    return '$' . number_format($value, 2);
}
?>
<!DOCTYPE html>
<html lang="km">
    
<link rel="icon" href="uzita.png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>របាយការណ៍ប្រតិបត្តិការ - Transaction Report</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Khmer OS Battambang', 'Siemreap', 'Time New Roman', system-ui, sans-serif; color: #0f172a; padding: 12mm; background: #fff; font-size: 12px; line-height: 1.5; }
        .report-header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .report-header h1 { font-size: 18px; margin-bottom: 4px; }
        .report-header p { font-size: 11px; color: #64748b; }
        .report-header .user-info { font-size: 11px; margin-top: 4px; }
        h2 { font-size: 14px; margin: 16px 0 8px; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
        thead th { background: #f1f5f9; padding: 6px 8px; border: 1px solid #cbd5e1; text-align: left; font-weight: 700; font-size: 10px; text-transform: uppercase; }
        tbody td { padding: 5px 8px; border: 1px solid #e2e8f0; }
        .badge { padding: 1px 4px; border-radius: 3px; font-size: 10px; font-weight: 600; display: inline-block; }
        .badge.income { background: #d1fae5; color: #065f46; }
        .badge.expense { background: #fee2e2; color: #991b1b; }
        .text-income { color: #059669; font-weight: 600; }
        .text-expense { color: #dc2626; font-weight: 600; }
        .daily-section { margin-bottom: 16px; page-break-inside: avoid; }
        .daily-section h3 { font-size: 12px; margin-bottom: 4px; color: #334155; }
        .daily-section table { margin-bottom: 4px; }
        .daily-total { font-weight: 700; background: #f8fafc; }
        .daily-total td { border-top: 2px solid #cbd5e1; font-weight: 700; }
        .summary { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
        .summary-item { background: #f8fafc; padding: 8px 12px; border-radius: 6px; border: 1px solid #e2e8f0; min-width: 100px; }
        .summary-item .label { font-size: 10px; color: #64748b; text-transform: uppercase; }
        .summary-item .value { font-size: 14px; font-weight: 700; }
        .summary-item.income .value { color: #059669; }
        .summary-item.expense .value { color: #dc2626; }
        .summary-item.balance .value { color: #1e293b; }
        .no-data { text-align: center; padding: 20px; color: #94a3b8; font-style: italic; }
        .page-break { page-break-before: always; }
        .footer { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e2e8f0; font-size: 10px; color: #94a3b8; text-align: center; }
        .btn-print { background: #2563eb; color: #fff; padding: 0.5rem 1rem; border: none; border-radius: 8px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: background 0.15s; margin-left: auto; font-family: 'Khmer OS Battambang', 'Siemreap', 'Time New Roman', system-ui, sans-serif; }
        .btn-print:hover { background: #1d4ed8; }
        .btn-back { background: transparent; color: #475569; padding: 0.5rem 1rem; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; font-size: 0.8rem; cursor: pointer; transition: all 0.15s; font-family: 'Khmer OS Battambang', 'Siemreap', 'Time New Roman', system-ui, sans-serif; }
        .btn-back:hover { background: #f8fafc; color: #0f172a; }
        @media print {
            body { padding: 8mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print" style="display:flex;align-items:center;gap:0.5rem;margin-bottom:16px;">
    <button class="btn-back" onclick="window.close()">Back</button>
    <button class="btn-print" onclick="window.print()">🖨️ បោះពុម្ភ PDF</button>
</div>

<div class="report-header">
    <h1>របាយការណ៍ប្រតិបត្តិការ</h1>
    <p>Transaction Report</p>
    <div class="user-info">អ្នកប្រើប្រាស់: <?= htmlspecialchars($currentUser['username']) ?> (<?= htmlspecialchars($currentUser['role']) ?>) | កាលបរិច្ឆេទ: <?= date('Y-m-d') ?></div>
</div>

<?php if (empty($transactions)): ?>
    <div class="no-data">មិនមានប្រតិបត្តិការទេ។</div>
<?php else: ?>
    <div class="summary">
        <?php
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
        ?>
        <div class="summary-item income">
            <div class="label">ចំណូលសរុប / Total Income</div>
            <div class="value"><?= formatCurrencyKHR($totalIncomeKHR) ?> / <?= formatCurrencyUSD($totalIncomeUSD) ?></div>
        </div>
        <div class="summary-item expense">
            <div class="label">ចំណាយសរុប / Total Expense</div>
            <div class="value"><?= formatCurrencyKHR($totalExpenseKHR) ?> / <?= formatCurrencyUSD($totalExpenseUSD) ?></div>
        </div>
        <div class="summary-item balance">
            <div class="label">សមតុល្យសរុប / Total Balance</div>
            <div class="value"><?= formatCurrencyKHR($totalIncomeKHR - $totalExpenseKHR) ?> / <?= formatCurrencyUSD($totalIncomeUSD - $totalExpenseUSD) ?></div>
        </div>
    </div>

    <h2>តារាងប្រតិបត្តិការទាំងអស់ / All Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>កាលបរិច្ឆេទ</th>
                <th>ឈ្មោះ</th>
                <th>អ្នកបន្ថែម</th>
                <th>ប្រភេទ</th>
                <th>ចំនួនទឹកប្រាក់</th>
                <th>ក្រុម</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $tx):
                $amt = (float)$tx['amount'];
                $isIncome = $tx['type'] === 'income';
                $curr = $tx['currency'] || 'KHR';
            ?>
            <tr>
                <td><?= htmlspecialchars($tx['date']) ?></td>
                <td><strong><?= htmlspecialchars($tx['title']) ?></strong></td>
                <td><?= htmlspecialchars($tx['username'] ?: '-') ?></td>
                <td><span class="badge <?= $tx['type'] ?>"><?= $isIncome ? 'ចំណូល' : 'ចំណាយ' ?></span></td>
                <td class="<?= $isIncome ? 'text-income' : 'text-expense' ?>">
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

    <h2>បញ្ចូលតាមថ្ងៃ / Daily Totals</h2>
    <?php foreach ($dailyTotals as $date => $totals): ?>
    <div class="daily-section">
        <h3><?= htmlspecialchars($date) ?></h3>
        <table>
            <thead>
                <tr><th>ព័ត៌មាន</th><th>ចំណូល (Income)</th><th>ចំណាយ (Expense)</th><th>សមតុល្យ (Balance)</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>តាមរយៈ KHR</td>
                    <td class="text-income"><?= formatCurrencyKHR($totals['income']) ?></td>
                    <td class="text-expense"><?= formatCurrencyKHR($totals['expense']) ?></td>
                    <td><?= formatCurrencyKHR($totals['income'] - $totals['expense']) ?></td>
                </tr>
                <tr>
                    <td>តាមរយៈ USD</td>
                    <td class="text-income"><?= formatCurrencyUSD($totals['income_usd']) ?></td>
                    <td class="text-expense"><?= formatCurrencyUSD($totals['expense_usd']) ?></td>
                    <td><?= formatCurrencyUSD($totals['income_usd'] - $totals['expense_usd']) ?></td>
                </tr>
                <tr class="daily-total">
                    <td>សរុប</td>
                    <td class="text-income"><?= formatCurrencyKHR($totals['income'] + $totals['income_usd'] * 4100) ?></td>
                    <td class="text-expense"><?= formatCurrencyKHR($totals['expense'] + $totals['expense_usd'] * 4100) ?></td>
                    <td><?= formatCurrencyKHR(($totals['income'] - $totals['expense']) + ($totals['income_usd'] - $totals['expense_usd']) * 4100) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<div class="footer">
    <p>Payment Tracker Report &copy; <?= date('Y') ?> | Generated: <?= date('Y-m-d H:i:s') ?></p>
</div>

</body>
</html>