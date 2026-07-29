<?php
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost");
}
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Cache-Control: no-store, no-cache, must-revalidate");

ini_set('display_errors', '0');
error_reporting(E_ALL);

session_start();

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
    echo json_encode(["error" => "Database connection failed."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function isValidAmount($value) {
    $num = filter_var($value, FILTER_VALIDATE_FLOAT);
    return $num !== false && $num > 0;
}

function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function sanitizeString($str) {
    return trim(strip_tags($str));
}

function requireAuth($pdo) {
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(["error" => "Authentication required."]);
        exit;
    }
    return $_SESSION['user_id'];
}

function requireAdmin($pdo) {
    $userId = requireAuth($pdo);
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(["error" => "Admin access required."]);
        exit;
    }
    return $userId;
}

function getCurrentUser($pdo) {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    return $stmt->fetch();
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = $_SERVER['SCRIPT_NAME'];
$action = substr($path, strpos($path, $scriptName) + strlen($scriptName));

if ($method === 'POST' && $action === '/signup') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['username']) || empty($input['password'])) {
        echo json_encode(["error" => "Username and password are required."]);
        http_response_code(400);
        exit;
    }

    $username = sanitizeString($input['username']);
    $password = $input['password'];

    if (mb_strlen($username) < 3 || mb_strlen($username) > 100) {
        echo json_encode(["error" => "Username must be between 3 and 100 characters."]);
        http_response_code(400);
        exit;
    }

    if (!preg_match('/^\d{6}$/', $password)) {
        echo json_encode(["error" => "Password must be exactly 6 digits."]);
        http_response_code(400);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        if ($stmt->fetch()) {
            echo json_encode(["error" => "Username already exists."]);
            http_response_code(409);
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (:username, :password, 'user')");
        $stmt->execute([':username' => $username, ':password' => $hash]);

        echo json_encode(["success" => true, "message" => "Account created successfully."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to create account."]);
    }
    exit;
}

if ($method === 'POST' && $action === '/signin') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['username']) || empty($input['password'])) {
        echo json_encode(["error" => "Username and password are required."]);
        http_response_code(400);
        exit;
    }

    $username = sanitizeString($input['username']);
    $password = $input['password'];

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(["error" => "Invalid username or password."]);
            http_response_code(401);
            exit;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        echo json_encode(["success" => true, "user" => ["id" => $user['id'], "username" => $user['username'], "role" => $user['role']]]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Sign-in failed."]);
    }
    exit;
}

if ($method === 'POST' && $action === '/logout') {
    session_destroy();
    echo json_encode(["success" => true, "message" => "Logged out successfully."]);
    exit;
}

if ($method === 'GET' && $action === '/auth-check') {
    $user = getCurrentUser($pdo);
    if ($user) {
        echo json_encode(["authenticated" => true, "user" => $user]);
    } else {
        echo json_encode(["authenticated" => false]);
    }
    exit;
}

if ($method === 'GET') {
    $isAdmin = false;
    $userId = null;
    $currentUser = getCurrentUser($pdo);

    if ($currentUser) {
        $userId = $currentUser['id'];
        if ($currentUser['role'] === 'admin') {
            $isAdmin = true;
        }
    }

    if ($action === '/admin/users') {
        requireAdmin($pdo);
        try {
            $stmt = $pdo->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
            $users = $stmt->fetchAll();
            echo json_encode($users);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Database error."]);
        }
        exit;
    }

    try {
        if ($isAdmin) {
            $stmt = $pdo->query("SELECT t.id, t.user_id, u.username, t.title, t.amount, t.currency, t.type, t.category, t.date, t.created_at, t.updated_at FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.date DESC, t.id DESC");
        } else {
            $stmt = $pdo->prepare("SELECT id, title, amount, currency, type, category, date, created_at, updated_at FROM transactions WHERE user_id = :user_id ORDER BY date DESC, id DESC");
            $stmt->execute([':user_id' => $userId]);
        }
        $transactions = $stmt->fetchAll();

        $lastMod = !empty($transactions) ? max(array_column($transactions, 'created_at')) : gmdate('D, d M Y H:i:s', time()) . ' GMT';
        $etag = md5(json_encode($transactions));

        header("Last-Modified: $lastMod");
        header("ETag: \"$etag\"");

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === "\"$etag\"") {
            http_response_code(304);
            exit;
        }

        echo json_encode($transactions);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error."]);
    }
    exit;
}

if ($method === 'POST') {
    $userId = requireAuth($pdo);
    $currentUser = getCurrentUser($pdo);
    $isAdmin = $currentUser && $currentUser['role'] === 'admin';

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['title']) || empty($input['amount']) || empty($input['currency']) || empty($input['type']) || empty($input['category']) || empty($input['date'])) {
        echo json_encode(["error" => "All fields are required."]);
        http_response_code(400);
        exit;
    }

    $title = sanitizeString($input['title']);
    $amount = $input['amount'];
    $currency = strtoupper(sanitizeString($input['currency']));
    $type = sanitizeString($input['type']);
    $category = sanitizeString($input['category']);
    $date = sanitizeString($input['date']);

    if (!isValidAmount($amount)) {
        echo json_encode(["error" => "Invalid amount. Must be greater than 0."]);
        http_response_code(400);
        exit;
    }

    if (!in_array($currency, ['KHR', 'USD'], true)) {
        echo json_encode(["error" => "Invalid currency."]);
        http_response_code(400);
        exit;
    }

    if (!in_array($type, ['income', 'expense'], true)) {
        echo json_encode(["error" => "Invalid transaction type."]);
        http_response_code(400);
        exit;
    }

    if (!isValidDate($date)) {
        echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD."]);
        http_response_code(400);
        exit;
    }

    if (mb_strlen($title) < 2 || mb_strlen($title) > 255) {
        echo json_encode(["error" => "Title must be between 2 and 255 characters."]);
        http_response_code(400);
        exit;
    }

    if (mb_strlen($category) < 2 || mb_strlen($category) > 100) {
        echo json_encode(["error" => "Category must be between 2 and 100 characters."]);
        http_response_code(400);
        exit;
    }

    try {
        $sql = "INSERT INTO transactions (user_id, title, amount, currency, type, category, date) VALUES (:user_id, :title, :amount, :currency, :type, :category, :date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':amount' => $amount,
            ':currency' => $currency,
            ':type' => $type,
            ':category' => $category,
            ':date' => $date
        ]);

        echo json_encode(["success" => true, "message" => "Transaction added successfully.", "id" => (int)$pdo->lastInsertId()]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to save transaction."]);
    }
    exit;
}

if ($method === 'PUT') {
    $userId = requireAuth($pdo);
    $currentUser = getCurrentUser($pdo);
    $isAdmin = $currentUser && $currentUser['role'] === 'admin';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$id || empty($input)) {
        echo json_encode(["error" => "Invalid request. ID and update data are required."]);
        http_response_code(400);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id, updated_at FROM transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $tx = $stmt->fetch();

        if (!$tx) {
            echo json_encode(["error" => "Transaction not found."]);
            http_response_code(404);
            exit;
        }

        if (!$isAdmin && $tx['user_id'] != $userId) {
            echo json_encode(["error" => "You can only update your own transactions."]);
            http_response_code(403);
            exit;
        }

        if ($tx['updated_at'] !== null) {
            echo json_encode(["error" => "This transaction has already been updated and cannot be modified again."]);
            http_response_code(403);
            exit;
        }

        $fields = [];
        $params = [':id' => $id];

        if (isset($input['title'])) {
            $title = sanitizeString($input['title']);
            if (mb_strlen($title) < 2 || mb_strlen($title) > 255) {
                echo json_encode(["error" => "Title must be between 2 and 255 characters."]);
                http_response_code(400);
                exit;
            }
            $fields[] = "title = :title";
            $params[':title'] = $title;
        }

        if (isset($input['amount'])) {
            if (!isValidAmount($input['amount'])) {
                echo json_encode(["error" => "Invalid amount. Must be greater than 0."]);
                http_response_code(400);
                exit;
            }
            $fields[] = "amount = :amount";
            $params[':amount'] = $input['amount'];
        }

        if (isset($input['currency'])) {
            $currency = strtoupper(sanitizeString($input['currency']));
            if (!in_array($currency, ['KHR', 'USD'], true)) {
                echo json_encode(["error" => "Invalid currency."]);
                http_response_code(400);
                exit;
            }
            $fields[] = "currency = :currency";
            $params[':currency'] = $currency;
        }

        if (isset($input['type'])) {
            $type = sanitizeString($input['type']);
            if (!in_array($type, ['income', 'expense'], true)) {
                echo json_encode(["error" => "Invalid transaction type."]);
                http_response_code(400);
                exit;
            }
            $fields[] = "type = :type";
            $params[':type'] = $type;
        }

        if (isset($input['category'])) {
            $category = sanitizeString($input['category']);
            if (mb_strlen($category) < 2 || mb_strlen($category) > 100) {
                echo json_encode(["error" => "Category must be between 2 and 100 characters."]);
                http_response_code(400);
                exit;
            }
            $fields[] = "category = :category";
            $params[':category'] = $category;
        }

        if (isset($input['date'])) {
            $date = sanitizeString($input['date']);
            if (!isValidDate($date)) {
                echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD."]);
                http_response_code(400);
                exit;
            }
            $fields[] = "date = :date";
            $params[':date'] = $date;
        }

        if (empty($fields)) {
            echo json_encode(["error" => "No fields provided for update."]);
            http_response_code(400);
            exit;
        }

        $fields[] = "updated_at = CURRENT_TIMESTAMP";
        $sql = "UPDATE transactions SET " . implode(', ', $fields) . " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode(["success" => true, "message" => "Transaction updated successfully."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update transaction."]);
    }
    exit;
}

if ($method === 'DELETE') {
    requireAuth($pdo);
    $currentUser = getCurrentUser($pdo);
    $isAdmin = $currentUser && $currentUser['role'] === 'admin';

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (!$id) {
        echo json_encode(["error" => "Transaction ID is required."]);
        http_response_code(400);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT user_id FROM transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $tx = $stmt->fetch();

        if (!$tx) {
            echo json_encode(["error" => "Transaction not found."]);
            http_response_code(404);
            exit;
        }

        if (!$isAdmin && $tx['user_id'] != $userId) {
            echo json_encode(["error" => "You can only delete your own transactions."]);
            http_response_code(403);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);

        echo json_encode(["success" => true, "message" => "Transaction deleted."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete transaction."]);
    }
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Not found."]);
exit;

if ($method === 'PUT' && $action === '/admin/reset-password') {
    requireAdmin($pdo);
    $targetUserId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$targetUserId) {
        echo json_encode(["error" => "User ID is required."]);
        http_response_code(400);
        exit;
    }

    $newPassword = $input['password'] ?? '';
    if (!preg_match('/^\d{6}$/', $newPassword)) {
        echo json_encode(["error" => "Password must be exactly 6 digits."]);
        http_response_code(400);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->execute([':id' => $targetUserId]);
        if (!$stmt->fetch()) {
            echo json_encode(["error" => "User not found."]);
            http_response_code(404);
            exit;
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
        $stmt->execute([':password' => $hash, ':id' => $targetUserId]);

        echo json_encode(["success" => true, "message" => "Password reset successfully."]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to reset password."]);
    }
    exit;
}

http_response_code(404);
echo json_encode(["error" => "Not found."]);
?>