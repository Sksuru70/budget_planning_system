-- ============================================================
--  BUDGET PLANNING SYSTEM  –  Database Schema
--  Import this file into phpMyAdmin or MySQL CLI:
--    mysql -u root -p < budget_system.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS budget_planning_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE budget_planning_system;

-- ── USERS ──────────────────────────────────────────────────
CREATE TABLE users (
  user_id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name        VARCHAR(100)  NOT NULL,
  email            VARCHAR(100)  NOT NULL UNIQUE,
  phone_number     VARCHAR(15),
  password         VARCHAR(255)  NOT NULL,
  role             ENUM('user','admin') DEFAULT 'user',
  registration_date DATE         DEFAULT (CURDATE()),
  is_active        TINYINT(1)    DEFAULT 1
) ENGINE=InnoDB;

-- ── BUDGET CATEGORIES ──────────────────────────────────────
CREATE TABLE budget_categories (
  category_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT          NOT NULL,
  category_name VARCHAR(100) NOT NULL,
  budget_limit  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  period_type   ENUM('monthly','yearly') DEFAULT 'monthly',
  icon          VARCHAR(50)  DEFAULT 'bi-tag',
  color         VARCHAR(10)  DEFAULT '#4361ee',
  status        ENUM('active','inactive') DEFAULT 'active',
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── TRANSACTIONS ───────────────────────────────────────────
CREATE TABLE transactions (
  transaction_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT          NOT NULL,
  category_id    INT,
  amount         DECIMAL(12,2) NOT NULL,
  type           ENUM('income','expense') NOT NULL,
  description    VARCHAR(255),
  transaction_date DATE       NOT NULL,
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(user_id)             ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES budget_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── SAVINGS GOALS ──────────────────────────────────────────
CREATE TABLE savings_goals (
  goal_id        INT AUTO_INCREMENT PRIMARY KEY,
  user_id        INT          NOT NULL,
  goal_name      VARCHAR(100) NOT NULL,
  target_amount  DECIMAL(12,2) NOT NULL,
  saved_amount   DECIMAL(12,2) DEFAULT 0.00,
  target_date    DATE,
  description    VARCHAR(255),
  status         ENUM('in_progress','achieved','cancelled') DEFAULT 'in_progress',
  created_at     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── ALERTS ─────────────────────────────────────────────────
CREATE TABLE alerts (
  alert_id      INT AUTO_INCREMENT PRIMARY KEY,
  user_id       INT          NOT NULL,
  category_id   INT,
  alert_type    ENUM('warning','exceeded','goal_achieved','reminder') DEFAULT 'warning',
  message       TEXT,
  is_read       TINYINT(1)   DEFAULT 0,
  created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)     REFERENCES users(user_id)             ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES budget_categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── SEED: default admin account ────────────────────────────
-- Password: admin@123  (bcrypt hash)
INSERT INTO users (full_name, email, phone_number, password, role)
VALUES ('Administrator', 'admin@budget.com', '9999999999',
        '$2y$12$Hjk6U5fOQ4OxVmkdBz8zDOCxkKGSEBwG0T8pCvLPTZ5yYOCz5cU3i', 'admin');

-- ── SEED: demo user account ────────────────────────────────
-- Password: demo@123
INSERT INTO users (full_name, email, phone_number, password, role)
VALUES ('Demo User', 'demo@budget.com', '9876543210',
        '$2y$12$7TjOZk0mQFkUiX3lT8gYdOBsI1Z9vKx5RL2N0pW4yMhCeHqJdVS6u', 'user');

-- demo categories for user_id=2
INSERT INTO budget_categories (user_id, category_name, budget_limit, icon, color) VALUES
(2,'Food & Dining',     5000.00, 'bi-cup-hot',       '#f72585'),
(2,'Transportation',    2000.00, 'bi-car-front',      '#7209b7'),
(2,'Rent & Utilities',  8000.00, 'bi-house',          '#3a0ca3'),
(2,'Healthcare',        2000.00, 'bi-heart-pulse',    '#4361ee'),
(2,'Entertainment',     1500.00, 'bi-film',           '#4cc9f0'),
(2,'Education',         3000.00, 'bi-book',           '#06d6a0');

-- demo transactions
INSERT INTO transactions (user_id, category_id, amount, type, description, transaction_date) VALUES
(2,1, 450.00,'expense','Lunch at restaurant',    DATE_SUB(CURDATE(),INTERVAL 1 DAY)),
(2,2, 200.00,'expense','Auto fare',              DATE_SUB(CURDATE(),INTERVAL 2 DAY)),
(2,3,8000.00,'expense','Monthly rent',           DATE_SUB(CURDATE(),INTERVAL 3 DAY)),
(2,5, 500.00,'expense','Movie tickets',          DATE_SUB(CURDATE(),INTERVAL 4 DAY)),
(2,NULL,35000.00,'income','Monthly salary',      DATE_SUB(CURDATE(),INTERVAL 5 DAY)),
(2,1, 600.00,'expense','Grocery shopping',       DATE_SUB(CURDATE(),INTERVAL 6 DAY)),
(2,4, 800.00,'expense','Doctor visit',           DATE_SUB(CURDATE(),INTERVAL 7 DAY)),
(2,6,1500.00,'expense','Online course fee',      DATE_SUB(CURDATE(),INTERVAL 8 DAY)),
(2,2, 150.00,'expense','Bus pass',               DATE_SUB(CURDATE(),INTERVAL 9 DAY)),
(2,1, 300.00,'expense','Evening snacks',         DATE_SUB(CURDATE(),INTERVAL 10 DAY));

-- demo savings goal
INSERT INTO savings_goals (user_id, goal_name, target_amount, saved_amount, target_date, description)
VALUES (2,'Emergency Fund',50000.00,18000.00, DATE_ADD(CURDATE(),INTERVAL 6 MONTH),'6 months emergency reserve'),
       (2,'New Laptop',    65000.00,20000.00, DATE_ADD(CURDATE(),INTERVAL 4 MONTH),'For college project work');
