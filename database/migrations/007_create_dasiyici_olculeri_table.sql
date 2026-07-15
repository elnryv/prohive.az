-- Bax CLAUDE.md bölmə 4.3.1 / orijinal sənəd bölmə 4.3.1
CREATE TABLE IF NOT EXISTS dasiyici_olculeri (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurye_id     BIGINT UNSIGNED NOT NULL,
  olcu_id      TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_do_kurye FOREIGN KEY (kurye_id) REFERENCES kuryeler(id),
  CONSTRAINT fk_do_olcu  FOREIGN KEY (olcu_id)  REFERENCES yukdasima_olculeri(id),
  UNIQUE KEY uq_kurye_olcu (kurye_id, olcu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
