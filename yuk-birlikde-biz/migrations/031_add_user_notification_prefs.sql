-- Hissə 4.12 "Bildiriş ayarları" (Təklif bildirişləri · Status bildirişləri ·
-- Sistem xəbərləri) üçün istifadəçi səviyyəli toggle-lar.
ALTER TABLE users
  ADD COLUMN notify_offers TINYINT(1) NOT NULL DEFAULT 1 AFTER status,
  ADD COLUMN notify_status TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_offers,
  ADD COLUMN notify_system TINYINT(1) NOT NULL DEFAULT 1 AFTER notify_status;
