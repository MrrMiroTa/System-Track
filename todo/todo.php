<?php
session_start();
header("Content-Type: text/html; charset=UTF-8");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

if (empty($_SESSION['user_id'])) {
    header("Location: index.php");
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
    echo json_encode(["error" => "Database connection failed."]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="km">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo List - <?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="uzita.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kantumruy+Pro:wght@400;500;600;700&family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=4">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .todo-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2.5rem 1rem;
        }

        .todo-hero {
            background: linear-gradient(135deg, var(--primary) 0%, #7C3AED 100%);
            color: #fff;
            border-radius: 20px;
            padding: 2rem 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.08), 0 10px 10px -5px rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .todo-hero-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .todo-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(8px);
            border: 2px solid rgba(255,255,255,0.35);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.6rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .todo-hero-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .todo-hero-subtitle {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.85);
            margin-top: 0.25rem;
        }

        .todo-card {
            background: var(--card);
            border-radius: var(--radius);
            padding: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
        }

        .todo-form {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }

        .todo-form input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: var(--font);
            background: var(--card);
            color: var(--text);
            transition: all 0.15s;
        }

        .todo-form input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .todo-form select {
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: var(--font);
            background: var(--card);
            color: var(--text);
            cursor: pointer;
            transition: all 0.15s;
        }

        .todo-form select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .todo-form input[type="date"] {
            padding: 0.7rem 1rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            font-family: var(--font);
            background: var(--card);
            color: var(--text);
            transition: all 0.15s;
        }

        .todo-form input[type="date"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .todo-form button {
            padding: 0.7rem 1.5rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-family: var(--font);
            transition: all 0.15s;
            white-space: nowrap;
        }

        .todo-form button:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .todo-filters {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .todo-filter-btn {
            padding: 0.45rem 1rem;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text-sec);
            font-family: var(--font);
            transition: all 0.15s;
        }

        .todo-filter-btn.active {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        .todo-filter-btn:hover:not(.active) {
            background: var(--surface);
            color: var(--text);
        }

        .todo-counter {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 0.75rem;
        }

        .todo-list {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .todo-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 0;
            transition: all 0.2s ease;
            position: relative;
        }

        .todo-item:first-child {
            border-radius: 12px 12px 0 0;
        }

        .todo-item:last-child {
            border-radius: 0 0 12px 12px;
            border-bottom: 1px solid var(--border);
        }

        .todo-item:only-child {
            border-radius: 12px;
        }

        .todo-item:hover {
            background: var(--surface);
            border-color: #cbd5e1;
            z-index: 1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .todo-item + .todo-item {
            border-top: none;
        }

        .todo-item.completed {
            background: #f8fafc;
        }

        .todo-item.completed:hover {
            background: #f1f5f9;
        }

        .todo-checkbox {
            width: 1.25rem;
            height: 1.25rem;
            border-radius: 6px;
            cursor: pointer;
            accent-color: var(--primary);
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .todo-content {
            flex: 1;
            min-width: 0;
        }

        .todo-text {
            font-size: 0.95rem;
            font-weight: 500;
            color: var(--text);
            word-break: break-word;
            line-height: 1.5;
            letter-spacing: -0.01em;
        }

        .todo-text.completed {
            text-decoration: line-through;
            color: var(--text-muted);
            opacity: 0.75;
        }

        .todo-meta {
            display: flex;
            gap: 0.75rem;
            margin-top: 0.35rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .todo-badge {
            font-size: 0.7rem;
            font-weight: 600;
            padding: 0.15rem 0.5rem;
            border-radius: 6px;
            text-transform: capitalize;
            letter-spacing: 0.02em;
        }

        .todo-badge-high { background-color: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        .todo-badge-medium { background-color: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .todo-badge-low { background-color: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        .todo-time {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .todo-time.overdue {
            color: #dc2626;
            font-weight: 600;
        }

        .todo-delete {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 1rem;
            line-height: 1;
            flex-shrink: 0;
            opacity: 0;
            transform: translateX(4px);
        }

        .todo-item:hover .todo-delete {
            opacity: 1;
            transform: translateX(0);
        }

        .todo-delete:hover {
            color: #ef4444;
            background: #fef2f2;
        }

        .todo-actions-right {
            display: flex;
            gap: 0.25rem;
            flex-shrink: 0;
            opacity: 0;
            transform: translateX(4px);
            transition: all 0.2s ease;
        }

        .todo-item:hover .todo-actions-right {
            opacity: 1;
            transform: translateX(0);
        }

        .todo-edit {
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-size: 1rem;
            line-height: 1;
            flex-shrink: 0;
        }

        .todo-edit:hover {
            color: var(--primary);
            background: var(--primary-soft);
        }

        .todo-edit-input {
            flex: 1;
            padding: 0.6rem 0.85rem;
            border: 1px solid var(--primary);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: var(--font);
            background: var(--card);
            color: var(--text);
            outline: none;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .todo-edit-actions {
            display: flex;
            gap: 0.5rem;
            flex-shrink: 0;
        }

        .todo-edit-btn {
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-family: var(--font);
            transition: all 0.15s;
        }

        .todo-edit-btn.save {
            background: var(--primary);
            color: #fff;
        }

        .todo-edit-btn.save:hover {
            background: var(--primary-hover);
        }

        .todo-edit-btn.cancel {
            background: var(--surface);
            color: var(--text-sec);
            border: 1px solid var(--border);
        }

        .todo-edit-btn.cancel:hover {
            background: var(--border);
            color: var(--text);
        }

        .todo-empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .todo-empty-icon {
            font-size: 2.5rem;
            opacity: 0.5;
            margin-bottom: 0.75rem;
            display: block;
        }

        .todo-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }

        .todo-back-btn {
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
            background: var(--card);
            color: var(--text-sec);
            border: 1px solid var(--border);
        }

        .todo-back-btn:hover {
            background: var(--surface);
            color: var(--text);
            transform: translateY(-1px);
        }

        @media (max-width: 640px) {
            .todo-hero {
                padding: 1.5rem;
                text-align: center;
                justify-content: center;
            }
            .todo-hero-left {
                flex-direction: column;
                text-align: center;
            }
            .todo-form {
                flex-direction: column;
            }
            .todo-form button {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="todo-page">
        <div class="todo-hero">
            <div class="todo-hero-left">
                <div class="todo-avatar"><?php echo strtoupper(mb_substr($user['username'], 0, 1)); ?></div>
                <div>
                    <div class="todo-hero-title">Todo List</div>
                    <div class="todo-hero-subtitle"><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?> - Manage your tasks</div>
                </div>
            </div>
        </div>

        <div class="todo-card">
            <form id="todoForm" class="todo-form">
                <input type="text" id="taskInput" placeholder="What needs to be done?" required autocomplete="off">
                <select id="priorityInput">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                </select>
                <input type="date" id="dueDateInput">
                <small class="todo-date-hint" style="width:100%;font-size:0.78rem;color:var(--text-muted);margin-top:0.15rem;">Optional</small>
                <button type="submit">Add Task</button>
            </form>

            <div class="todo-filters">
                <button class="todo-filter-btn active" onclick="setFilter('all', this)">All</button>
                <button class="todo-filter-btn" onclick="setFilter('pending', this)">Pending</button>
                <button class="todo-filter-btn" onclick="setFilter('completed', this)">Completed</button>
            </div>

            <div class="todo-counter" id="taskCounter">0 tasks</div>
            <div id="taskList" class="todo-list"></div>
        </div>

        <div class="todo-actions">
            <a href="profile.php" class="todo-back-btn">← Back to Profile</a>
        </div>
    </div>

    <script>
        const API_URL = 'todo_api.php';
        let currentFilter = 'all';
        let allTasks = [];

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

        function formatDateTime(dateTimeStr) {
            if (!dateTimeStr) return '';
            const d = new Date(dateTimeStr);
            return d.toLocaleString([], {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        async function loadTasks() {
            try {
                const response = await fetch(`${API_URL}?action=get_tasks`);
                if (response.status === 401) {
                    window.location.href = 'index.php';
                    return;
                }
                allTasks = await response.json();
                renderTasks();
            } catch (err) {
                console.error("Error fetching tasks:", err);
            }
        }

        window.setFilter = (filter, element) => {
            currentFilter = filter;
            document.querySelectorAll('.todo-filter-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            renderTasks();
        };

        function renderTasks() {
            const taskList = document.getElementById('taskList');
            taskList.innerHTML = '';

            const filteredTasks = allTasks.filter(task => {
                if (currentFilter === 'pending') return task.status === 'pending';
                if (currentFilter === 'completed') return task.status === 'completed';
                return true;
            });

            document.getElementById('taskCounter').textContent = `${filteredTasks.length} ${filteredTasks.length === 1 ? 'task' : 'tasks'}`;

            if (filteredTasks.length === 0) {
                taskList.innerHTML = `
                    <div class="todo-empty">
                        <div class="todo-empty-icon">📝</div>
                        <p class="mb-0">No tasks found.</p>
                    </div>
                `;
                return;
            }

            const todayStr = new Date().toISOString().split('T')[0];

            filteredTasks.forEach(task => {
                const isCompleted = task.status === 'completed';
                const isOverdue = task.due_date && task.due_date < todayStr && !isCompleted;

                const item = document.createElement('div');
                item.className = `todo-item ${isCompleted ? 'completed' : ''}`;
                item.setAttribute('data-id', task.id);
                item.innerHTML = `
                    <input type="checkbox" class="todo-checkbox" ${isCompleted ? 'checked' : ''}
                        onchange="toggleTask(${task.id}, '${isCompleted ? 'pending' : 'completed'}')">
                    <div class="todo-content">
                        <div class="todo-text ${isCompleted ? 'completed' : ''}">${escapeHtml(task.task_name)}</div>
                        <div class="todo-meta">
                            <span class="todo-badge todo-badge-${task.priority}">${task.priority}</span>
                            <span class="todo-time">
                                <i class="bi bi-plus-circle me-1"></i>Created: ${formatDateTime(task.created_at)}
                            </span>
                            ${isCompleted && task.completed_at ? `
                                <span class="todo-time" style="color: #16a34a; font-weight: 500;">
                                    • <i class="bi bi-check-circle me-1"></i>Ended: ${formatDateTime(task.completed_at)}
                                </span>
                            ` : ''}
                            ${task.due_date ? `
                                <span class="todo-time ${isOverdue ? 'overdue' : ''}">
                                    <i class="bi bi-calendar3 me-1"></i>${isOverdue ? 'Overdue: ' : 'Due: '}${task.due_date}
                                </span>
                            ` : ''}
                        </div>
                    </div>
                    <div class="todo-actions-right">
                        <button class="todo-edit" onclick="editTask(${task.id})" title="Edit task">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="todo-delete" onclick="deleteTask(${task.id})" title="Delete task">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                `;
                taskList.appendChild(item);
            });
        }

        document.getElementById('todoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const taskName = document.getElementById('taskInput').value.trim();
            if (!taskName) return;

            const newTask = {
                task: taskName,
                priority: document.getElementById('priorityInput').value,
                due_date: document.getElementById('dueDateInput').value
            };

            document.getElementById('taskInput').value = '';
            document.getElementById('dueDateInput').value = '';

            try {
                const response = await fetch(`${API_URL}?action=add`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newTask)
                });

                const data = await response.json();

                if (!response.ok || data.error) {
                    showAlert(data.error || data.message || 'Failed to add task.', 'error');
                    loadTasks();
                    return;
                }

                loadTasks();
            } catch (error) {
                console.error("Add task failed:", error);
                showAlert('Connection failed. Please try again.', 'error');
                loadTasks();
            }
        });

        window.toggleTask = async (id, status) => {
            try {
                const response = await fetch(`${API_URL}?action=toggle`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status })
                });
                const data = await response.json();
                if (!response.ok || data.error) {
                    showAlert(data.error || 'Failed to update task.', 'error');
                    return;
                }
                loadTasks();
            } catch (error) {
                console.error("Toggle task failed:", error);
                showAlert('Connection failed. Please try again.', 'error');
            }
        };

        window.deleteTask = async (id) => {
            allTasks = allTasks.filter(t => t.id !== id);
            renderTasks();

            try {
                const response = await fetch(`${API_URL}?action=delete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                const data = await response.json();
                if (!response.ok || data.error) {
                    showAlert(data.error || 'Failed to delete task.', 'error');
                    loadTasks();
                }
            } catch (error) {
                console.error("Delete task failed:", error);
                showAlert('Connection failed. Please try again.', 'error');
                loadTasks();
            }
        };

        window.editTask = function(id) {
            const task = allTasks.find(t => t.id === id);
            if (!task) return;

            const item = document.querySelector(`.todo-item[data-id="${id}"]`);
            if (!item) return;

            item.innerHTML = `
                <div class="todo-content">
                    <input type="text" class="todo-edit-input" id="edit-task-${id}" value="${escapeHtml(task.task_name)}">
                    <div class="todo-meta" style="margin-top:0.5rem;">
                        <select class="todo-edit-input" id="edit-priority-${id}" style="flex:1;min-width:auto;">
                            <option value="low" ${task.priority === 'low' ? 'selected' : ''}>Low</option>
                            <option value="medium" ${task.priority === 'medium' ? 'selected' : ''}>Medium</option>
                            <option value="high" ${task.priority === 'high' ? 'selected' : ''}>High</option>
                        </select>
                        <input type="date" class="todo-edit-input" id="edit-due-${id}" value="${task.due_date || ''}" style="flex:1;min-width:auto;">
                    </div>
                </div>
                <div class="todo-edit-actions">
                    <button class="todo-edit-btn save" onclick="saveTask(${id})">Save</button>
                    <button class="todo-edit-btn cancel" onclick="loadTasks()">Cancel</button>
                </div>
            `;

            const input = document.getElementById(`edit-task-${id}`);
            if (input) {
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
            }
        };

        window.saveTask = async function(id) {
            const taskName = document.getElementById(`edit-task-${id}`).value.trim();
            const priority = document.getElementById(`edit-priority-${id}`).value;
            const dueDate = document.getElementById(`edit-due-${id}`).value;

            if (!taskName) {
                showAlert('Task name is required.', 'warning');
                return;
            }

            const task = allTasks.find(t => t.id === id);
            if (task) {
                task.task_name = taskName;
                task.priority = priority;
                task.due_date = dueDate;
            }

            try {
                const response = await fetch(`${API_URL}?action=update`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, task: taskName, priority, due_date: dueDate })
                });
                const data = await response.json();
                if (!response.ok || data.error) {
                    showAlert(data.error || 'Failed to update task.', 'error');
                    return;
                }
                loadTasks();
            } catch (error) {
                console.error("Update task failed:", error);
                showAlert('Connection failed. Please try again.', 'error');
            }
        };

        loadTasks();
    </script>
</body>
</html>
