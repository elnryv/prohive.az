-- Bax CLAUDE.md bölmə 4.5 / orijinal sənəd bölmə 4.5
CREATE TABLE IF NOT EXISTS kurye_bolgeler (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurye_id  BIGINT UNSIGNED NOT NULL,
  rayon_id  SMALLINT UNSIGNED NOT NULL,
  CONSTRAINT fk_kb_kurye FOREIGN KEY (kurye_id) REFERENCES kuryeler(id),
  CONSTRAINT fk_kb_rayon FOREIGN KEY (rayon_id) REFERENCES rayonlar(id),
  UNIQUE KEY uq_kurye_rayon (kurye_id, rayon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
