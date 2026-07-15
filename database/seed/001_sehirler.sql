-- Bax CLAUDE.md bölmə 5.1 / orijinal sənəd bölmə 5.1
-- Qeyd: idempotentlik seed runner-in tracking cədvəli ilə təmin olunur (bax database/seed.php)
-- — ad_az üzərində UNIQUE olmadığından ON DUPLICATE KEY istifadə edilmir.
INSERT INTO sehirler (ad_az, ad_ru, ad_en, aktiv) VALUES
  ('Bakı', 'Баку', 'Baku', 1),
  ('Sumqayıt', 'Сумгайыт', 'Sumgayit', 1);
