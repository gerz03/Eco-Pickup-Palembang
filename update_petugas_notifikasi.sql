USE bank_sampah_palembang;

ALTER TABLE pickup_requests
    ADD COLUMN officer_id CHAR(8) DEFAULT NULL AFTER user_id,
    ADD COLUMN notes TEXT DEFAULT NULL AFTER pickup_address,
    ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL AFTER notes,
    ADD CONSTRAINT fk_pickup_requests_officer
        FOREIGN KEY (officer_id) REFERENCES officers(officer_id)
        ON DELETE SET NULL;

ALTER TABLE pickup_requests
    MODIFY request_status ENUM('PENDING', 'DIJEMPUT', 'SELESAI', 'DIBATALKAN', 'Menunggu', 'Diproses', 'Terangkut', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu';

UPDATE pickup_requests
SET request_status = CASE request_status
    WHEN 'PENDING' THEN 'Menunggu'
    WHEN 'DIJEMPUT' THEN 'Terangkut'
    WHEN 'SELESAI' THEN 'Selesai'
    WHEN 'DIBATALKAN' THEN 'Dibatalkan'
    ELSE request_status
END;

ALTER TABLE pickup_requests
    MODIFY request_status ENUM('Menunggu', 'Diproses', 'Terangkut', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Menunggu';

CREATE TABLE IF NOT EXISTS user_notifications (
    notification_id CHAR(18) PRIMARY KEY,
    recipient_id CHAR(8) NOT NULL,
    request_id INT DEFAULT NULL,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) DEFAULT 'notifikasi.php',
    read_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id CHAR(8) DEFAULT NULL,
    role ENUM('USER', 'OFFICER', 'ADMIN', 'GUEST') NOT NULL DEFAULT 'GUEST',
    action VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    request_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (request_id) REFERENCES pickup_requests(request_id) ON DELETE SET NULL
);
