-- Bax CLAUDE.md bölmə 4.7 / orijinal sənəd bölmə 4.7
CREATE TABLE IF NOT EXISTS abunelikler (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurye_id    BIGINT UNSIGNED NOT NULL,
  tip         ENUM('pulsuz','pullu') NOT NULL DEFAULT 'pullu',
  baslama     DATE NOT NULL,
  bitme       DATE NOT NULL,
  aktiv       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ab_kurye FOREIGN KEY (kurye_id) REFERENCES kuryeler(id),
  INDEX idx_kurye_bitme (kurye_id, bitme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
