<?php
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Cache-Control: no-store, no-cache, must-revalidate");

ini_set('display_errors', '0');
error_reporting(E_ALL);

$host = "localhost";
$db_name = "tracker_db";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
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

if ($method === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, title, amount, currency, type, category, date, created_at, updated_at FROM transactions ORDER BY date DESC, id DESC");
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
}

if ($method === 'POST') {
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
        $sql = "INSERT INTO transactions (title, amount, currency, type, category, date) VALUES (:title, :amount, :currency, :type, :category, :date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
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
}

if ($method === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$id || empty($input)) {
        echo json_encode(["error" => "Invalid request. ID and update data are required."]);
        http_response_code(400);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT updated_at FROM transactions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $tx = $stmt->fetch();

        if (!$tx) {
            echo json_encode(["error" => "Transaction not found."]);
            http_response_code(404);
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
}
?>