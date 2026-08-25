<?php
header("Content-Type: application/json; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Cache-Control: no-store, no-cache, must-revalidate");

ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('default_charset', 'UTF-8');
date_default_timezone_set('Asia/Phnom_Penh');

session_start();

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
    http_response_code(401);
    echo json_encode(["error" => "Authentication required."]);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_tasks') {
    $stmt = $pdo->prepare("SELECT id, task_name, status, priority, due_date, created_at, completed_at FROM tasks WHERE user_id = ? ORDER BY due_date ASC, id DESC");
    $stmt->execute([$userId]);
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'add') {
        $taskName = trim($data['task'] ?? '');
        $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
        $dueDate = !empty($data['due_date']) ? $data['due_date'] : null;

        if (empty($taskName)) {
            echo json_encode(['success' => false, 'message' => 'Task description required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, task_name, priority, due_date, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$userId, $taskName, $priority, $dueDate]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'toggle') {
        $id = $data['id'] ?? 0;
        $status = $data['status'] === 'completed' ? 'completed' : 'pending';
        $completedAt = ($status === 'completed') ? date('Y-m-d H:i:s') : null;

        $stmt = $pdo->prepare("UPDATE tasks SET status = ?, completed_at = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$status, $completedAt, $id, $userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'delete') {
        $id = $data['id'] ?? 0;
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'update') {
        $id = $data['id'] ?? 0;
        $taskName = trim($data['task'] ?? '');
        $priority = in_array($data['priority'] ?? '', ['low', 'medium', 'high']) ? $data['priority'] : 'medium';
        $dueDate = !empty($data['due_date']) ? $data['due_date'] : null;

        if (empty($taskName)) {
            echo json_encode(['success' => false, 'message' => 'Task description required.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tasks SET task_name = ?, priority = ?, due_date = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$taskName, $priority, $dueDate, $id, $userId]);
        echo json_encode(['success' => true]);
        exit;
    }
}

echo json_encode(['error' => 'Invalid action']);
