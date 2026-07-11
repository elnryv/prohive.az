-- Bax CLAUDE.md bölmə 4.7 / orijinal sənəd bölmə 4.7
CREATE TABLE IF NOT EXISTS odenisler (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  abunelik_id   BIGINT UNSIGNED NULL,
  kurye_id      BIGINT UNSIGNED NOT NULL,
  order_id      VARCHAR(64) NOT NULL UNIQUE,
  mebleg        DECIMAL(10,2) NOT NULL,
  status        ENUM('gozlemede','ugurlu','ugursuz') DEFAULT 'gozlemede',
  provayder     VARCHAR(30),
  callback_json JSON NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_od_abunelik FOREIGN KEY (abunelik_id) REFERENCES abunelikler(id),
  CONSTRAINT fk_od_kurye    FOREIGN KEY (kurye_id)    REFERENCES kuryeler(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
