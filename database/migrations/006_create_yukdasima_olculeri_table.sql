-- Bax CLAUDE.md bölmə 4.3.1 / orijinal sənəd bölmə 4.3.1
CREATE TABLE IF NOT EXISTS yukdasima_olculeri (
  id        TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kod       VARCHAR(6) NOT NULL UNIQUE,
  uzunluq   VARCHAR(40),
  tutum     VARCHAR(40),
  tesvir    VARCHAR(80),
  sira      TINYINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
