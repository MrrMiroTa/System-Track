CREATE DATABASE IF NOT EXISTS tracker_db;
USE tracker_db;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE transactions ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'KHR' AFTER amount;
ALTER TABLE transactions ADD COLUMN updated_at TIMESTAMP NULL DEFAULT NULL AFTER created_at;

CREATE INDEX idx_transactions_date ON transactions(date);
CREATE INDEX idx_transactions_type ON transactions(type);
CREATE INDEX idx_transactions_updated_at ON transactions(updated_at);

-- Optional sample data to get started
INSERT INTO transactions (title, amount, type, category, date) VALUES
('Web Design Freelance', 1200.00, 'income', 'Freelance', '2026-07-15'),
('Monthly Rent', 500.00, 'expense', 'Housing', '2026-07-01'),
('Groceries', 75.50, 'expense', 'Food', '2026-07-18');