-- Bax CLAUDE.md bölmə 4.4 / orijinal sənəd bölmə 4.4
CREATE TABLE IF NOT EXISTS rayonlar (
  id        SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sehir_id  SMALLINT UNSIGNED NOT NULL,
  ad_az     VARCHAR(80) NOT NULL,
  ad_ru     VARCHAR(80),
  ad_en     VARCHAR(80),
  aktiv     TINYINT(1) DEFAULT 1,
  CONSTRAINT fk_rayon_sehir FOREIGN KEY (sehir_id) REFERENCES sehirler(id),
  INDEX idx_sehir (sehir_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
