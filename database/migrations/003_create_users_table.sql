-- Bax CLAUDE.md bölmə 4.2 / orijinal sənəd bölmə 4.2
CREATE TABLE IF NOT EXISTS users (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad             VARCHAR(80)  NOT NULL,
  soyad          VARCHAR(80)  NOT NULL,
  telefon        VARCHAR(20)  NOT NULL UNIQUE,
  parol_hash     VARCHAR(255) NOT NULL,
  whatsapp       VARCHAR(20)  NOT NULL,
  whatsapp_tip   ENUM('sexsi','business') DEFAULT 'sexsi',
  rol            ENUM('musteri','kurye','yukdasima') NOT NULL DEFAULT 'musteri',
  dil            ENUM('az','ru','en') NOT NULL DEFAULT 'az',
  status         ENUM('aktiv','bloklu') NOT NULL DEFAULT 'aktiv',
  sozlesme_qebul TINYINT(1) NOT NULL DEFAULT 0,
  sozlesme_tarix TIMESTAMP NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rol (rol),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
