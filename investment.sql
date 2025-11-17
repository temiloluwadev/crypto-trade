-- Create database
CREATE DATABASE IF NOT EXISTS investment_platform;
USE investment_platform;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0.00,
    btc_balance DECIMAL(15,8) DEFAULT 0.00000000,
    eth_balance DECIMAL(15,8) DEFAULT 0.00000000,
    status ENUM('active', 'frozen') DEFAULT 'active',
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Deposits table
CREATE TABLE IF NOT EXISTS deposits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(15,2) NOT NULL,
    currency ENUM('BTC', 'ETH') NOT NULL,
    wallet_address VARCHAR(255),
    status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Withdrawals table
CREATE TABLE IF NOT EXISTS withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    amount DECIMAL(15,2) NOT NULL,
    currency ENUM('BTC', 'ETH') NOT NULL,
    wallet_address VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('deposit', 'withdrawal', 'trade') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10),
    details TEXT,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Trades table
CREATE TABLE IF NOT EXISTS trades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('buy', 'sell') NOT NULL,
    asset VARCHAR(10) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    price DECIMAL(15,2) NOT NULL,
    crypto_amount DECIMAL(15,8) DEFAULT 0.00000000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert admin user (password: 'password')
INSERT IGNORE INTO users (name, email, password, is_admin) VALUES 
('Administrator', 'admin@cryptotrade.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);