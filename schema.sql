CREATE DATABASE IF NOT EXISTS tracker_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tracker_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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