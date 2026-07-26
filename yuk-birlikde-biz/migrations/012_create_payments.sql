CREATE TABLE payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  driver_id BIGINT UNSIGNED NOT NULL,
  subscription_id BIGINT UNSIGNED NULL,
  amount DECIMAL(8,2) NOT NULL,
  currency CHAR(3) NOT NULL,
  payriff_tx_id VARCHAR(64) NULL,
  status ENUM('pending','success','failed') NOT NULL DEFAULT 'pending',
  raw JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_payments_driver FOREIGN KEY (driver_id) REFERENCES drivers(user_id),
  CONSTRAINT fk_payments_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
