CREATE TABLE banners (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  image_path VARCHAR(255) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description VARCHAR(255) NULL,
  link VARCHAR(255) NULL,
  placement ENUM('home_top','feed','profile','subscription') NOT NULL,
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort INT NOT NULL DEFAULT 0,
  click_count INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
