
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'KHR',
    updated_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_transactions_user_id ON transactions(user_id);
CREATE INDEX idx_transactions_date ON transactions(date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_updated_at ON transactions(updated_at);
CREATE INDEX idx_users_username ON users(username);

INSERT IGNORE INTO users (id, username, password, role) VALUES
(1, 'admin', '$2y$12$/Gf4SteqIC.4bTJnRblsouA6He8ofA2dD/Jr1XlNNAeizg081vVsa', 'admin');

INSERT IGNORE INTO transactions (user_id, title, amount, type, category, date, currency) VALUES
(1, 'Web Design Freelance', 1200.00, 'income', 'Freelance', '2026-07-15', 'KHR'),
(1, 'Monthly Rent', 500.00, 'expense', 'Housing', '2026-07-01', 'KHR'),
(1, 'Groceries', 75.50, 'expense', 'Food', '2026-07-18', 'KHR');

ALTER TABLE users ADD COLUMN IF NOT EXISTS last_username_change_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_password_change_at TIMESTAMP NULL DEFAULT NULL AFTER last_username_change_at;

CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    due_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_tasks_user_id ON tasks(user_id);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_tasks_due_date ON tasks(due_date);
