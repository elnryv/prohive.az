-- =====================================================================
-- BİRLİKDƏ YÜK — install.sql
-- MySQL 8.0, utf8mb4. Təmiz bazaya bir dəfə işə salınır (idempotent deyil).
-- Mənbə: BIRLIKDE_YUK_TAM.md bölmə 4. Əlavə cədvəllər (admins, admin_logs,
-- sse_events, reports) Getdik istinadı əvəzinə bu layihə üçün müstəqil
-- dizayn edilib (bax PROGRESS.md "SUAL" qeydi).
-- =====================================================================

SET NAMES utf8mb4;
SET time_zone = '+04:00';

-- ---------------------------------------------------------------------
-- 4.1 settings
-- ---------------------------------------------------------------------
CREATE TABLE settings (
  skey VARCHAR(64) PRIMARY KEY,
  svalue TEXT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO settings (skey, svalue) VALUES
('payments_enabled','1'),
('default_monthly_price','25.00'),
('trial_days','30'),
('grace_days','7'),
('listing_auto_close_hours','72'),
('currency','AZN'),
('support_phone',''),
('maintenance_mode','0'),
('vapid_public',''),
('vapid_private','');

-- ---------------------------------------------------------------------
-- 4.5 locations
-- ---------------------------------------------------------------------
CREATE TABLE locations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_az VARCHAR(100) NOT NULL,
  name_ru VARCHAR(100) NOT NULL,
  name_en VARCHAR(100) NOT NULL,
  is_baku TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO locations (name_az, name_ru, name_en, is_baku, sort_order) VALUES
('Yasamal','Ясамал','Yasamal',1,10),
('Nəsimi','Насими','Nasimi',1,20),
('Binəqədi','Бинагади','Binagadi',1,30),
('Xətai','Хатаи','Khatai',1,40),
('Nizami','Низами','Nizami',1,50),
('Sabunçu','Сабунчи','Sabunchu',1,60),
('Suraxanı','Сураханы','Surakhany',1,70),
('Xəzər','Хазар','Khazar',1,80),
('Qaradağ','Гарадаг','Garadagh',1,90),
('Pirallahı','Пираллахи','Pirallahi',1,100),
('Səbail','Сабаил','Sabail',1,110),
('Nərimanov','Наримановский','Narimanov',1,120),
('Sumqayıt','Сумгаит','Sumgayit',0,130),
('Abşeron','Абшерон','Absheron',0,140),
('Gəncə','Гянджа','Ganja',0,150),
('Sumqayıt ətrafı','Сумгаит (пригород)','Sumgayit area',0,160),
('Mingəçevir','Мингячевир','Mingachevir',0,170),
('Şəki','Шеки','Sheki',0,180),
('Şirvan','Ширван','Shirvan',0,190),
('Naxçıvan','Нахчыван','Nakhchivan',0,200),
('Lənkəran','Ленкорань','Lankaran',0,210),
('Qazax','Газах','Gazakh',0,220),
('Yevlax','Евлах','Yevlakh',0,230),
('Quba','Куба','Guba',0,240),
('Qusar','Гусар','Gusar',0,250),
('Zaqatala','Загатала','Zaqatala',0,260),
('Balakən','Балакен','Balakan',0,270),
('Şamaxı','Шамахы','Shamakhi',0,280),
('Qəbələ','Габала','Gabala',0,290),
('Göyçay','Гейчай','Goychay',0,300),
('Ağdaş','Агдаш','Agdash',0,310),
('İmişli','Имишли','Imishli',0,320),
('Salyan','Сальян','Salyan',0,330),
('Neftçala','Нефтчала','Neftchala',0,340),
('Biləsuvar','Билясувар','Bilasuvar',0,350),
('Saatlı','Саатлы','Saatly',0,360),
('Sabirabad','Сабирабад','Sabirabad',0,370),
('Hacıqabul','Гаджигабул','Hajigabul',0,380),
('Kürdəmir','Кюрдамир','Kurdamir',0,390),
('Ağstafa','Агстафа','Agstafa',0,400),
('Tovuz','Товуз','Tovuz',0,410),
('Gədəbəy','Гедабек','Gadabay',0,420),
('Daşkəsən','Дашкесан','Dashkasan',0,430),
('Goranboy','Геранбой','Goranboy',0,440),
('Tərtər','Тертер','Tartar',0,450),
('Bərdə','Барда','Barda',0,460),
('Ağcabədi','Агджабеди','Agjabadi',0,470),
('Beyləqan','Бейлаган','Beylagan',0,480),
('Füzuli','Физули','Fuzuli',0,490),
('Cəlilabad','Джалилабад','Jalilabad',0,500),
('Masallı','Масаллы','Masally',0,510),
('Yardımlı','Ярдымлы','Yardimli',0,520),
('Lerik','Лерик','Lerik',0,530),
('Astara','Астара','Astara',0,540),
('Xaçmaz','Хачмаз','Khachmaz',0,550),
('Siyəzən','Сиязань','Siyazan',0,560),
('Xızı','Хызы','Khizi',0,570),
('Ağsu','Агсу','Agsu',0,580),
('İsmayıllı','Исмаиллы','Ismayilli',0,590),
('Şabran','Шабран','Shabran',0,600);

-- ---------------------------------------------------------------------
-- 4.4 categories
-- ---------------------------------------------------------------------
CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(64) NOT NULL UNIQUE,
  name_az VARCHAR(100) NOT NULL,
  name_ru VARCHAR(100) NOT NULL,
  name_en VARCHAR(100) NOT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '📦',
  hint_az VARCHAR(255) DEFAULT NULL,
  hint_ru VARCHAR(255) DEFAULT NULL,
  hint_en VARCHAR(255) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categories (slug, name_az, name_ru, name_en, icon, hint_az, hint_ru, hint_en, sort_order) VALUES
('ev-kocurmesi','Ev köçürməsi','Переезд квартиры','Home moving','🏠','Otaq sayı, mərtəbə, lift varmı yaz','Укажите количество комнат, этаж, есть ли лифт','Note room count, floor, whether there is a lift',10),
('mebel-teknika','Mebel-məişət texnikası','Мебель и техника','Furniture & appliances','🛋️','Nə qədər əşya, ölçüləri təxmini yaz','Укажите количество вещей и примерные размеры','Note item count and approximate sizes',20),
('tikinti-materiali','Tikinti materialı','Стройматериалы','Construction material','🧱','Material növü və təxmini çəki/həcm yaz','Укажите тип материала и примерный вес/объём','Note material type and approximate weight/volume',30),
('bolge-yuku','Bölgə yükü','Груз в регион','Intercity cargo','🚛','Yükün növü və çəkisini yaz','Укажите тип и вес груза','Note cargo type and weight',40),
('temir-tullantisi','Təmir tullantısı','Строительный мусор','Renovation waste','🗑️','Təxmini həcm (bir/bir neçə maşın) yaz','Укажите примерный объём (один/несколько рейсов)','Note approximate volume (one/several loads)',50),
('diger','Digər','Другое','Other','📦','Yükün nə olduğunu qısaca yaz','Кратко опишите груз','Briefly describe the cargo',60);

-- ---------------------------------------------------------------------
-- 4.3 vehicle_types
-- ---------------------------------------------------------------------
CREATE TABLE vehicle_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name_az VARCHAR(80) NOT NULL,
  name_ru VARCHAR(80) NOT NULL,
  name_en VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO vehicle_types (name_az, name_ru, name_en, sort_order) VALUES
('Yük taksisi (kiçik)','Грузовое такси (малое)','Small cargo taxi',10),
('Furqon','Фургон','Van',20),
('Sprinter (furqon)','Спринтер (фургон)','Sprinter (van)',21),
('Ford Transit (furqon)','Форд Транзит (фургон)','Ford Transit (van)',22),
('Bortlu maşın','Бортовая машина','Flatbed truck',30),
('Kamaz/böyük yük','КамАЗ/большегруз','Heavy truck',40),
('Fura','Фура','Semi-trailer',50),
('Evakuator','Эвакуатор','Tow truck',60);

-- ---------------------------------------------------------------------
-- 4.2 users
-- ---------------------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('customer','driver') NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  profile_photo VARCHAR(255) DEFAULT NULL,
  lang ENUM('az','ru','en') NOT NULL DEFAULT 'az',
  is_blocked TINYINT(1) NOT NULL DEFAULT 0,
  vehicle_type_id INT UNSIGNED DEFAULT NULL,
  vehicle_note VARCHAR(160) DEFAULT NULL,
  vehicle_photo VARCHAR(255) DEFAULT NULL,
  driver_status ENUM('pending','approved','rejected') DEFAULT NULL,
  reject_reason VARCHAR(500) DEFAULT NULL,
  billing_status ENUM('trial','paid','free','expired') DEFAULT NULL,
  custom_price DECIMAL(8,2) DEFAULT NULL,
  trial_until DATE DEFAULT NULL,
  paid_until DATE DEFAULT NULL,
  cancel_count INT UNSIGNED NOT NULL DEFAULT 0,
  jobs_done INT UNSIGNED NOT NULL DEFAULT 0,
  failed_login_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until TIMESTAMP NULL DEFAULT NULL,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vehicle_type_id) REFERENCES vehicle_types(id),
  INDEX idx_role (role), INDEX idx_phone (phone), INDEX idx_billing (billing_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.6 listings
-- ---------------------------------------------------------------------
CREATE TABLE listings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_code CHAR(8) NOT NULL UNIQUE,
  customer_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  from_location_id INT UNSIGNED NOT NULL,
  from_detail VARCHAR(160) DEFAULT NULL,
  to_location_id INT UNSIGNED NOT NULL,
  to_detail VARCHAR(160) DEFAULT NULL,
  scope ENUM('baku','intercity') NOT NULL,
  move_date DATE DEFAULT NULL,
  is_urgent TINYINT(1) NOT NULL DEFAULT 0,
  description TEXT NOT NULL,
  status ENUM('active','accepted','completed','expired','removed') NOT NULL DEFAULT 'active',
  accepted_offer_id INT UNSIGNED DEFAULT NULL,
  accepted_at TIMESTAMP NULL DEFAULT NULL,
  completed_at TIMESTAMP NULL DEFAULT NULL,
  expires_at TIMESTAMP NOT NULL,
  reopen_count INT UNSIGNED NOT NULL DEFAULT 0,
  extended TINYINT(1) NOT NULL DEFAULT 0,
  offers_count INT UNSIGNED NOT NULL DEFAULT 0,
  views_count INT UNSIGNED NOT NULL DEFAULT 0,
  removed_by ENUM('customer','admin') DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id),
  FOREIGN KEY (from_location_id) REFERENCES locations(id),
  FOREIGN KEY (to_location_id) REFERENCES locations(id),
  INDEX idx_feed (status, scope, is_urgent, created_at),
  INDEX idx_customer (customer_id), INDEX idx_code (public_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.7 listing_photos
-- ---------------------------------------------------------------------
CREATE TABLE listing_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id INT UNSIGNED NOT NULL,
  filename VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.8 offers
-- ---------------------------------------------------------------------
CREATE TABLE offers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id INT UNSIGNED NOT NULL,
  driver_id INT UNSIGNED NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  note VARCHAR(300) DEFAULT NULL,
  status ENUM('pending','accepted','lost','withdrawn','canceled_by_customer') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_listing_driver (listing_id, driver_id),
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_driver (driver_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE listings ADD FOREIGN KEY (accepted_offer_id) REFERENCES offers(id) ON DELETE SET NULL;

-- ---------------------------------------------------------------------
-- 4.9 listing_events
-- ---------------------------------------------------------------------
CREATE TABLE listing_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id INT UNSIGNED NOT NULL,
  event ENUM('created','offer_new','offer_updated','offer_withdrawn','accepted','reopened','completed','expired','removed') NOT NULL,
  actor_user_id INT UNSIGNED DEFAULT NULL,
  details JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  INDEX idx_listing (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.10 payments (Getdik 4.4 struktur ekvivalenti — bax PROGRESS.md SUAL)
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  driver_id INT UNSIGNED NOT NULL,
  amount DECIMAL(8,2) NOT NULL,
  currency VARCHAR(8) NOT NULL DEFAULT 'AZN',
  payriff_order_id VARCHAR(100) DEFAULT NULL,
  status ENUM('pending','paid','failed','canceled') NOT NULL DEFAULT 'pending',
  payment_url VARCHAR(500) DEFAULT NULL,
  raw_response TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_driver (driver_id), INDEX idx_status (status), INDEX idx_order (payriff_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.11 push_subscriptions
-- ---------------------------------------------------------------------
CREATE TABLE push_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  endpoint VARCHAR(500) NOT NULL,
  p256dh VARCHAR(255) NOT NULL,
  auth_key VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_endpoint (endpoint(191)),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.12 route_subscriptions
-- ---------------------------------------------------------------------
CREATE TABLE route_subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  driver_id INT UNSIGNED NOT NULL,
  from_location_id INT UNSIGNED DEFAULT NULL,
  to_location_id INT UNSIGNED DEFAULT NULL,
  scope ENUM('baku','intercity','all') NOT NULL DEFAULT 'all',
  FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (from_location_id) REFERENCES locations(id) ON DELETE CASCADE,
  FOREIGN KEY (to_location_id) REFERENCES locations(id) ON DELETE CASCADE,
  UNIQUE KEY uq_route (driver_id, from_location_id, to_location_id, scope)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4.13 admins / admin_logs / sse_events
-- ---------------------------------------------------------------------
CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(120) NOT NULL,
  is_super TINYINT(1) NOT NULL DEFAULT 0,
  failed_attempts INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until TIMESTAMP NULL DEFAULT NULL,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE admin_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NOT NULL,
  action VARCHAR(100) NOT NULL,
  target_type VARCHAR(50) DEFAULT NULL,
  target_id INT UNSIGNED DEFAULT NULL,
  details JSON DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admins(id),
  INDEX idx_admin (admin_id), INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sse_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  channel VARCHAR(60) NOT NULL,
  event VARCHAR(60) NOT NULL,
  payload JSON DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_channel_id (channel, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- banners — admin tərəfindən yüklənən, müştəri/sürücü ekranlarında
-- başlıqdan dərhal aşağıda avtomatik sürüşən reklam/elan bannerləri
-- ---------------------------------------------------------------------
CREATE TABLE banners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image VARCHAR(255) NOT NULL,
  title_az VARCHAR(160) DEFAULT NULL,
  title_ru VARCHAR(160) DEFAULT NULL,
  title_en VARCHAR(160) DEFAULT NULL,
  link_url VARCHAR(500) DEFAULT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_active_sort (is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- reports — şikayət növbəsi (bölmə 9.4)
-- ---------------------------------------------------------------------
CREATE TABLE reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  listing_id INT UNSIGNED NOT NULL,
  offer_id INT UNSIGNED DEFAULT NULL,
  reporter_user_id INT UNSIGNED NOT NULL,
  reason VARCHAR(500) NOT NULL,
  status ENUM('pending','resolved','dismissed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  resolved_at TIMESTAMP NULL DEFAULT NULL,
  FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
  FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL,
  FOREIGN KEY (reporter_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- Seed: 1 admin (username: admin / password: ChangeMe!2026 — DƏRHAL DƏYİŞİN)
-- Hash password_hash() ilə PASSWORD_BCRYPT alqoritmi ilə 'ChangeMe!2026' üçün generasiya olunub.
-- ---------------------------------------------------------------------
INSERT INTO admins (username, password_hash, full_name, is_super) VALUES
('admin', '$2y$12$tPFDEraUix1YkjvaewOGYOQ3OXr53p1lyDkor623CP.yhxUOP.kbq', 'Baş Admin', 1);
