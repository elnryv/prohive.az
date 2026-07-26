CREATE TABLE broadcast_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id BIGINT UNSIGNED NOT NULL,
  audience ENUM('all','customers','drivers','active_subs','expired_subs') NOT NULL,
  title VARCHAR(160) NOT NULL,
  body VARCHAR(255) NOT NULL,
  scheduled_at DATETIME NULL,
  sent_at DATETIME NULL,
  success_count INT NOT NULL DEFAULT 0,
  fail_count INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
