-- =====================================================================
-- Birlikdə Getdik — install.sql
-- Tam DB sxemi + ilkin seed (bölmə 4, texniki şərtnamə)
-- Tələb: MySQL 8.0, boş/təmiz bazaya import olunmalıdır.
--   mysql -u root -p getdik < install.sql
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================================
-- 4.1 settings — sistem parametrləri
-- =====================================================================
CREATE TABLE settings (
  skey       VARCHAR(64) PRIMARY KEY,
  svalue     TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO settings (skey, svalue) VALUES
('default_monthly_price', '25.00'),
('trial_days', '30'),
('currency', 'AZN'),
('site_phone', ''),
('maintenance_mode', '0');

-- =====================================================================
-- 4.2 regions — bölgələr
-- =====================================================================
CREATE TABLE regions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug        VARCHAR(64) NOT NULL UNIQUE,
  name_az     VARCHAR(100) NOT NULL,
  name_ru     VARCHAR(100) NOT NULL,
  name_en     VARCHAR(100) NOT NULL,
  tagline_az  VARCHAR(160) DEFAULT NULL,
  tagline_ru  VARCHAR(160) DEFAULT NULL,
  tagline_en  VARCHAR(160) DEFAULT NULL,
  cover_img   VARCHAR(255) DEFAULT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO regions (slug, name_az, name_ru, name_en, sort_order) VALUES
('qebele',    'Qəbələ',     'Габала',     'Gabala',     10),
('quba',      'Quba',       'Куба',       'Guba',       20),
('qusar',     'Qusar',      'Гусар',      'Gusar',      30),
('seki',      'Şəki',       'Шеки',       'Sheki',      40),
('ismayilli', 'İsmayıllı',  'Исмаиллы',   'Ismayilli',  50),
('lankaran',  'Lənkəran',   'Ленкорань',  'Lankaran',   60),
('astara',    'Astara',     'Астара',     'Astara',     70),
('zaqatala',  'Zaqatala',   'Загатала',   'Zaqatala',   80),
('shamakhi',  'Şamaxı',     'Шамаха',     'Shamakhi',   90),
('naftalan',  'Naftalan',   'Нафталан',   'Naftalan',   100);

-- =====================================================================
-- 4.3 owners — ev sahibləri
-- Görünürlük qaydası (kritik, tətbiq qatında icra olunur):
--   billing_status IN ('trial','paid','free') VƏ
--   (trial: trial_until >= CURDATE() VƏ YA paid: paid_until >= CURDATE();
--    free: tarix yoxlanmır). expired/blocked -> evlər gizlənir (silinmir).
-- =====================================================================
CREATE TABLE owners (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone           VARCHAR(20) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  full_name       VARCHAR(120) NOT NULL,
  billing_status  ENUM('trial','paid','free','expired','blocked') NOT NULL DEFAULT 'trial',
  custom_price    DECIMAL(8,2) DEFAULT NULL,
  trial_until     DATE DEFAULT NULL,
  paid_until      DATE DEFAULT NULL,
  lang            ENUM('az','ru','en') NOT NULL DEFAULT 'az',
  last_login_at   TIMESTAMP NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_phone (phone),
  INDEX idx_billing (billing_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4.4 payments — abunə ödənişləri
-- =====================================================================
CREATE TABLE payments (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id          INT UNSIGNED NOT NULL,
  amount            DECIMAL(8,2) NOT NULL,
  currency          CHAR(3) NOT NULL DEFAULT 'AZN',
  months            TINYINT UNSIGNED NOT NULL DEFAULT 1,
  payriff_order_id  VARCHAR(64) DEFAULT NULL,
  payment_url       VARCHAR(512) DEFAULT NULL,
  status            ENUM('created','approved','declined','canceled','expired') NOT NULL DEFAULT 'created',
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  paid_at           TIMESTAMP NULL,
  raw_response      JSON DEFAULT NULL,
  FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
  INDEX idx_owner (owner_id),
  INDEX idx_status (status),
  INDEX idx_payriff (payriff_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4.5 houses — qonaq evləri
-- Redaktə qaydası: approved ev redaktə olunanda status DƏYİŞMİR;
-- yalnız yeni foto admin baxışına düşmədən dərc olunmur (house_photos.is_approved).
-- =====================================================================
CREATE TABLE houses (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id          INT UNSIGNED NOT NULL,
  region_id         INT UNSIGNED NOT NULL,
  slug              VARCHAR(140) NOT NULL UNIQUE,
  title             VARCHAR(140) NOT NULL,
  title_ru          VARCHAR(140) DEFAULT NULL,
  title_en          VARCHAR(140) DEFAULT NULL,
  village           VARCHAR(120) DEFAULT NULL,
  description       TEXT NOT NULL,
  description_ru    TEXT DEFAULT NULL,
  description_en    TEXT DEFAULT NULL,
  price_night       DECIMAL(8,2) NOT NULL,
  price_weekend     DECIMAL(8,2) DEFAULT NULL,
  rooms             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  capacity          TINYINT UNSIGNED NOT NULL DEFAULT 2,
  whatsapp_phone    VARCHAR(20) NOT NULL,
  map_lat           DECIMAL(10,7) DEFAULT NULL,
  map_lng           DECIMAL(10,7) DEFAULT NULL,
  status            ENUM('draft','pending','approved','rejected') NOT NULL DEFAULT 'draft',
  reject_reason     VARCHAR(500) DEFAULT NULL,
  views_total       INT UNSIGNED NOT NULL DEFAULT 0,
  wa_clicks_total   INT UNSIGNED NOT NULL DEFAULT 0,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES owners(id) ON DELETE CASCADE,
  FOREIGN KEY (region_id) REFERENCES regions(id),
  INDEX idx_region_status (region_id, status),
  INDEX idx_owner (owner_id),
  FULLTEXT idx_search (title, village, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4.6 house_photos — foto/video
-- Qaydalar: min 4 foto (dərc şərti), maks 20 foto + 1 video.
-- =====================================================================
CREATE TABLE house_photos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  house_id      INT UNSIGNED NOT NULL,
  filename      VARCHAR(255) NOT NULL,
  is_cover      TINYINT(1) NOT NULL DEFAULT 0,
  is_video      TINYINT(1) NOT NULL DEFAULT 0,
  is_approved   TINYINT(1) NOT NULL DEFAULT 0,
  sort_order    INT NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  INDEX idx_house (house_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4.7 amenities — şərait lüğəti
-- =====================================================================
CREATE TABLE amenities (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  icon        VARCHAR(16) NOT NULL,
  name_az     VARCHAR(80) NOT NULL,
  name_ru     VARCHAR(80) NOT NULL,
  name_en     VARCHAR(80) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  is_active   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE house_amenities (
  house_id    INT UNSIGNED NOT NULL,
  amenity_id  INT UNSIGNED NOT NULL,
  PRIMARY KEY (house_id, amenity_id),
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE,
  FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO amenities (icon, name_az, name_ru, name_en, sort_order) VALUES
('📶', 'Wi-Fi',              'Wi-Fi',            'Wi-Fi',              10),
('🏊', 'Hovuz',              'Бассейн',          'Pool',               20),
('🔥', 'Mangal yeri',        'Место для мангала','BBQ area',           30),
('❄️', 'Kondisioner',        'Кондиционер',      'Air conditioning',   40),
('🅿️', 'Parkinq',            'Парковка',         'Parking',            50),
('🏔️', 'Dağ mənzərəsi',      'Вид на горы',      'Mountain view',      60),
('🌊', 'Dəniz mənzərəsi',    'Вид на море',      'Sea view',           70),
('🧒', 'Uşaq üçün uyğun',    'Подходит для детей','Kid-friendly',      80),
('🐾', 'Heyvan olar',        'Можно с животными','Pets allowed',       90),
('🍳', 'Səhər yeməyi',       'Завтрак',          'Breakfast',          100),
('🧺', 'Paltaryuyan',        'Стиральная машина','Washing machine',    110),
('♨️', 'İsti döşəmə/soba',   'Тёплый пол/печь',  'Underfloor heating/stove', 120),
('🌳', 'Həyət',              'Двор',             'Yard',               130),
('🛝', 'Yelləncək',          'Качели',           'Swing',              140);

-- =====================================================================
-- 4.8 house_calendar — dolu/boş vitrin təqvimi (Q4)
-- Yalnız DOLU günlər saxlanılır.
-- =====================================================================
CREATE TABLE house_calendar (
  house_id  INT UNSIGNED NOT NULL,
  busy_date DATE NOT NULL,
  PRIMARY KEY (house_id, busy_date),
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4.9 stats_daily — günlük statistika
-- =====================================================================
CREATE TABLE stats_daily (
  house_id  INT UNSIGNED NOT NULL,
  stat_date DATE NOT NULL,
  views     INT UNSIGNED NOT NULL DEFAULT 0,
  wa_clicks INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (house_id, stat_date),
  FOREIGN KEY (house_id) REFERENCES houses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- 4.10 admins və audit
-- =====================================================================
CREATE TABLE admins (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(60) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  last_login_at   TIMESTAMP NULL,
  created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE admin_logs (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id    INT UNSIGNED NOT NULL,
  action      VARCHAR(80) NOT NULL,
  target_id   INT UNSIGNED DEFAULT NULL,
  details     VARCHAR(500) DEFAULT NULL,
  ip          VARCHAR(45) DEFAULT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_admin (admin_id),
  INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DEV SEED: username=admin, şifrə=ChangeMe!2026 — istehsalatda DƏRHAL dəyişin.
INSERT INTO admins (username, password_hash) VALUES
('admin', '$2y$12$wqupLuQCpNrZf4dn4EHJPO1DA3ofqHLp.73bFJPmiONSP0W9MK9Pa');

-- =====================================================================
-- 4.11 sse_events — real-time hadisə növbəsi
-- 7 gündən köhnə sətirlər cron ilə silinir.
-- =====================================================================
CREATE TABLE sse_events (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel     VARCHAR(40) NOT NULL,
  event_type  VARCHAR(40) NOT NULL,
  payload     JSON NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_channel_id (channel, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
