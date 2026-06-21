CREATE DATABASE IF NOT EXISTS bank_sampah_palembang;
USE bank_sampah_palembang;

CREATE TABLE IF NOT EXISTS users (
    user_id CHAR(8) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    google_id VARCHAR(120) UNIQUE DEFAULT NULL,
    phone_number VARCHAR(20) NOT NULL,
    address TEXT,
    password_hash VARCHAR(255) NOT NULL,
    auth_provider ENUM('LOCAL', 'GOOGLE') NOT NULL DEFAULT 'LOCAL',
    role ENUM('USER', 'OFFICER', 'ADMIN') NOT NULL DEFAULT 'USER',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS officers (
    officer_id CHAR(8) PRIMARY KEY,
    service_area VARCHAR(100) NOT NULL, 
    vehicle_type VARCHAR(50) NOT NULL,
    FOREIGN KEY (officer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS waste_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL,
    price_per_kg DECIMAL(10, 2) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS routes (
    route_id CHAR(12) PRIMARY KEY,
    officer_id CHAR(8) NOT NULL,
    route_date DATE NOT NULL,
    total_distance_km DECIMAL(5,2) DEFAULT 0.00,
    FOREIGN KEY (officer_id) REFERENCES officers(officer_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS orders (
    order_id CHAR(10) PRIMARY KEY,
    user_id CHAR(8) NOT NULL,
    officer_id CHAR(8) DEFAULT NULL,
    route_id CHAR(12) DEFAULT NULL,
    pickup_date DATE NOT NULL,
    pickup_time_slot ENUM('PAGI_08_10', 'SIANG_10_12', 'SORE_13_15') NOT NULL,
    pickup_address TEXT NOT NULL,
    estimated_price DECIMAL(10, 2) DEFAULT 0.00,
    final_price DECIMAL(10, 2) DEFAULT 0.00,
    order_status ENUM('PENDING', 'CONFIRMED', 'COMPLETED', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(officer_id) ON DELETE SET NULL,
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id CHAR(10) NOT NULL,
    category_id INT NOT NULL,
    estimated_weight DECIMAL(6, 2) NOT NULL DEFAULT 0.00,
    actual_weight DECIMAL(6, 2) DEFAULT NULL, 
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES waste_categories(category_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS transactions (
    transaction_id CHAR(12) PRIMARY KEY,
    order_id CHAR(10) UNIQUE NOT NULL,
    user_id CHAR(8) NOT NULL,
    officer_id CHAR(8) NOT NULL,
    pickup_date_time DATETIME NOT NULL,
    total_weight_kg DECIMAL(6, 2) NOT NULL,
    total_amount_rp DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(30) DEFAULT 'CASH_ON_PICKUP',
    co2_saved_kg DECIMAL(6, 2) DEFAULT 0.00,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES officers(officer_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS notifications (
    notification_id CHAR(15) PRIMARY KEY,
    recipient_id CHAR(8) NOT NULL,
    channel ENUM('EMAIL', 'SMS') NOT NULL,
    status ENUM('SENT', 'FAILED', 'PENDING') NOT NULL DEFAULT 'PENDING',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS pickup_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(8) DEFAULT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    pickup_address TEXT NOT NULL,
    waste_type ENUM('Organik', 'Anorganik', 'B3') NOT NULL,
    price_per_kg INT NOT NULL,
    weight_kg DECIMAL(8, 2) NOT NULL,
    distance_km DECIMAL(8, 2) NOT NULL,
    distance_fee INT NOT NULL,
    total_price INT NOT NULL,
    pickup_date DATE NOT NULL,
    pickup_time TIME NOT NULL,
    payment_method ENUM('QRIS', 'DANA', 'OVO', 'GoPay', 'Transfer Bank', 'COD') NOT NULL,
    request_status ENUM('PENDING', 'DIJEMPUT', 'SELESAI', 'DIBATALKAN') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS pickup_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    total_weight_kg DECIMAL(8, 2) NOT NULL,
    total_amount INT NOT NULL,
    payment_method ENUM('QRIS', 'DANA', 'OVO', 'GoPay', 'Transfer Bank', 'COD') NOT NULL,
    payment_status ENUM('BELUM BAYAR', 'LUNAS') NOT NULL DEFAULT 'LUNAS',
    transaction_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE
);

INSERT INTO waste_categories (category_name, price_per_kg, description)
SELECT 'Organik', 3000, 'Sampah mudah terurai'
WHERE NOT EXISTS (SELECT 1 FROM waste_categories WHERE category_name = 'Organik');

INSERT INTO waste_categories (category_name, price_per_kg, description)
SELECT 'Anorganik', 5000, 'Sampah plastik, kertas, kaca, dan logam'
WHERE NOT EXISTS (SELECT 1 FROM waste_categories WHERE category_name = 'Anorganik');

INSERT INTO waste_categories (category_name, price_per_kg, description)
SELECT 'B3', 10000, 'Sampah bahan berbahaya dan beracun'
WHERE NOT EXISTS (SELECT 1 FROM waste_categories WHERE category_name = 'B3');
