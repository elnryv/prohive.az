-- Bax CLAUDE.md bölmə 4.6 / orijinal sənəd bölmə 4.6 (nüvə cədvəl)
CREATE TABLE IF NOT EXISTS sifarisler (
  id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  musteri_id          BIGINT UNSIGNED NOT NULL,
  kurye_id            BIGINT UNSIGNED NULL,
  goturulme_sehir_id  SMALLINT UNSIGNED NOT NULL,
  goturulme_rayon_id  SMALLINT UNSIGNED NOT NULL,
  goturulme_unvan     VARCHAR(255) NOT NULL,
  catdirilma_sehir_id SMALLINT UNSIGNED NOT NULL,
  catdirilma_rayon_id SMALLINT UNSIGNED NOT NULL,
  catdirilma_unvan    VARCHAR(255) NOT NULL,
  yuk_tesviri         VARCHAR(500),
  teklif_qiymet       DECIMAL(10,2),
  tecili              TINYINT(1) NOT NULL DEFAULT 0,
  tip                 ENUM('kurye','yukdasima') NOT NULL DEFAULT 'kurye',
  yukdasima_olcu_id   TINYINT UNSIGNED NULL,
  status              ENUM('axtarisda','goturulub','tamamlandi','legv','passiv')
                        NOT NULL DEFAULT 'axtarisda',
  version             INT UNSIGNED NOT NULL DEFAULT 0,
  goturulme_vaxti     TIMESTAMP NULL,
  created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sif_musteri FOREIGN KEY (musteri_id) REFERENCES users(id),
  CONSTRAINT fk_sif_kurye   FOREIGN KEY (kurye_id)   REFERENCES kuryeler(id),
  INDEX idx_status_rayon (status, goturulme_rayon_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
