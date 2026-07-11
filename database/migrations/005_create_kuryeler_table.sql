-- Bax CLAUDE.md bölmə 4.3 / orijinal sənəd bölmə 4.3
CREATE TABLE IF NOT EXISTS kuryeler (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  neqliyyat     ENUM('avtomobil','moto','skuter','velosiped','piyada') NULL,
  onlayn        TINYINT(1) NOT NULL DEFAULT 0,
  tamamlanan    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kurye_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
