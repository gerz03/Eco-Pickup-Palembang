USE bank_sampah_palembang;

ALTER TABLE pickup_requests
ADD COLUMN IF NOT EXISTS user_id CHAR(8) DEFAULT NULL AFTER request_id;

CREATE TABLE IF NOT EXISTS pickup_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    total_weight_kg DECIMAL(8, 2) NOT NULL,
    total_amount INT NOT NULL,
    payment_method ENUM('QRIS', 'DANA', 'OVO', 'GoPay', 'Transfer Bank', 'COD') NOT NULL,
    payment_status ENUM('PENDING', 'LUNAS', 'BATAL') NOT NULL DEFAULT 'PENDING',
    transaction_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE
);
