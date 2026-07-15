-- Bax CLAUDE.md bölmə 8.8: admin ayrı, izolyasiya olunmuş cədvəldə saxlanır (users deyil).
CREATE TABLE IF NOT EXISTS adminler (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad           VARCHAR(80)  NOT NULL,
  soyad        VARCHAR(80)  NOT NULL,
  telefon      VARCHAR(20)  NOT NULL UNIQUE,
  parol_hash   VARCHAR(255) NOT NULL,
  status       ENUM('aktiv','bloklu') NOT NULL DEFAULT 'aktiv',
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
