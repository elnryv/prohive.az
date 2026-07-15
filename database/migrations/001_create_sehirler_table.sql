-- Bax CLAUDE.md bölmə 4.4 / orijinal sənəd bölmə 4.4
CREATE TABLE IF NOT EXISTS sehirler (
  id     SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad_az  VARCHAR(60) NOT NULL,
  ad_ru  VARCHAR(60),
  ad_en  VARCHAR(60),
  aktiv  TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
