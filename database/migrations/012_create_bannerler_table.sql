-- Bax CLAUDE.md bölmə 4.8 / orijinal sənəd bölmə 4.8
CREATE TABLE IF NOT EXISTS bannerler (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  baslik      VARCHAR(120),
  sekil_yol   VARCHAR(255) NOT NULL,
  link        VARCHAR(500),
  hedef       ENUM('hamisi','musteri','kurye') DEFAULT 'hamisi',
  baslama     DATE NOT NULL,
  bitme       DATE NOT NULL,
  sira        SMALLINT DEFAULT 0,
  aktiv       TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_aktiv_tarix (aktiv, baslama, bitme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
