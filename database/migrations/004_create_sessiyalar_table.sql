-- Bax CLAUDE.md bölmə 4.2.1 / orijinal sənəd bölmə 4.2.1 (remember-me, 7.3.1)
CREATE TABLE IF NOT EXISTS sessiyalar (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  token_hash  VARCHAR(255) NOT NULL,
  cihaz       VARCHAR(160),
  son_giris   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
