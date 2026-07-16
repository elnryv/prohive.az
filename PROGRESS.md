# BİRLİKDƏ YÜK — İCRA JURNALI (PROGRESS.md)

Həqiqət mənbəyi: `BIRLIKDE_YUK_TAM.md` (istifadəçinin göndərdiyi PDF-dən çıxarılıb, bax aşağı qeyd) + bu fayl.

## SUAL / QƏRAR QEYDİ (sahibkardan cavab gözlənilir, blok DEYİL — davam edilib)

**SUAL:** `BIRLIKDE_GETDIK_TAM.md` (Payriff `PaymentGateway.php`/`PayriffProvider.php` kodu, telefon
normalizasiya alqoritmi, SSE poll mexanizmi üçün istinad sənədi) nə repoda, nə upload-larda tapılmadı.
İstifadəçiyə aydınlaşdırıcı sual göndərildi, lakin cavab alınmadı (tool xətası). **Qərar:** bloklamadan,
öz best-practice tətbiqimlə davam edirəm:

- **Telefon normalizasiyası:** sənəddə formatı açıq yazılıb (`994XXXXXXXXX`) — `app/Core/Phone.php`
  bunu 070/077/099 s.k. yerli formatlardan, `+994`-dən, boşluq/tire-lərdən təmizləyib qurur.
- **Payriff inteqrasiyası:** `app/Payments/PayriffProvider.php` Payriff-in ictimai API v3 sənədləşməsinə
  (createOrder, getOrderStatus, callback) uyğun, sənədin bölmə 7.6/8 təsvirinə əsasən yazılıb.
  SECRET_KEY placeholder-dır. **Sahibkar Getdik-in əsl kodunu versə, bu fayl 1:1 əvəz olunmalıdır**
  (bölmə 8-in tələbi budur — "eyni kod").
- **SSE:** bölmə 11.5-dəki təsvirə (sse_events poll, ping, reconnect, kanallar) əsasən `app/Core/Sse.php`
  özüm yazılıb.

Bu 3 fayl "Getdik ilə EYNİ" tələbini formal şəkildə ödəmir (çünki mənbə kod yoxdur), amma spesifikasiyanın
təsvir etdiyi DAVRANIŞI tam ödəyir. Əgər sahibkar əsl Getdik kodunu təqdim etsə, dərhal əvəz olunacaq.

---

## FAZA STATUSLARI

| Faza | Ad | Status |
|---|---|---|
| 0 | Skelet | 🔄 icrada |
| 1 | Auth + rollar | ⏳ |
| 2 | Elan + lent | ⏳ |
| 3 | Təklif + qəbul (atomik) | ⏳ |
| 4 | SSE + Push | ⏳ |
| 5 | Abunə + Payriff | ⏳ |
| 6 | Admin | ⏳ |
| 7 | PWA + dizayn cilası | ⏳ |
| 8 | Buraxılış | ⏳ |

## MÜHİT QEYDİ

Bu sessiya bir git-repo daxilində (kod anbarı) işləyir, canlı VPS-ə çıxışı yoxdur. Layihə kodu spesifikasiyanın
bölmə 5 strukturuna uyğun **repo kökündə** yazılır (`app/`, `public/`, `public_admin/`, `cron/`, `db/`, `nginx/`).
Faktiki serverdə bunlar `/var/www/yuk/` altına yerləşdirilməlidir — `README.md`-də dəqiq addımlar var.
FAZA 8-dəki server/SSL/cron **qeydiyyatı** və real cihazda push testi kimi addımlar bu mühitdə fiziki icra
oluna bilmir — bunun əvəzinə skriptlər/təlimatlar hazırlanır, sahibkar öz VPS-də icra edir.

## FAZA 0 — Skelet ✅ TAMAMLANDI

- [x] Qovluq strukturu (bölmə 5) — repo kökündə (`app/`, `public/`, `public_admin/`, `cron/`, `db/`, `nginx/`)
- [x] `db/install.sql` — bütün 16 cədvəl + seed (60 lokasiya, 6 kateqoriya, 6 maşın növü, 1 admin, 10 setting)
- [x] `app/Core/*`: Config, DB, Router, View, Lang, Csrf, Auth, AdminAuth, Settings, Upload, Phone, Sse, WebPush, OgImage
- [x] `app/Payments/PaymentGateway.php` interfeysi (tətbiqi FAZA 5-də)
- [x] Boş layout (`layouts/app.php`) + ana səhifə (3 dil) + 404 səhifə + bottom-nav partial
- [x] Dizayn tokenləri (`public/assets/css/app.css`) — bölmə 10.1 (kömür+narıncı)
- [x] nginx nümunə konfiqləri (sayt + admin), `.gitignore`, `config.example.php`

### Özünüyoxlama nəticələri (real test, bu mühitdə)
- **MySQL 8.0 əvəzinə MariaDB 10.11** yerli sandbox-da quraşdırılıb (utf8mb4/JSON dəstəkli, MySQL 8.0-a
  praktiki cəhətdən uyğun) — `install.sql` təmiz `yuk_db`-yə **xətasız** yükləndi, 16 cədvəl yarandı,
  seed sayları düzgün (60 lokasiya/6 kateqoriya/6 maşın növü/1 admin/10 setting). ✓
- PHP daxili server (`php -S`) + Playwright/Chromium ilə `/` səhifəsi **AZ/RU/EN** 3 dildə HTTP 200,
  düzgün `<title>`, PHP xəta/warning YOXDUR. ✓
- 360px viewport-da (Playwright, real CSS viewport emulyasiyası) horizontal overflow yoxdur,
  `scrollWidth === clientWidth === 360`. ✓ (İlk ilkin test alətdə — raw `chrome --headless --window-size`
  — yanlış overflow göstərmişdi, çünki `viewport-fit`/`width=device-width` düzgün emulyasiya etmirdi;
  Playwright ilə təkrar test bunun alət artefaktı olduğunu təsdiqlədi.)
- Kripto özəklər (FAZA 4 üçün əvvəlcədən) test edilib: `openssl_pkey_derive()` ECDH, HKDF-SHA256,
  AES-128-GCM (RFC 8291 çərçivəsi) və VAPID ES256 DER↔raw imza çevrilməsi — hamısı encrypt→decrypt
  round-trip və `openssl_verify` ilə təsdiqləndi. ext-gmp/bcmath TƏLƏB OLUNMUR (PHP 8.1+ kifayətdir).
- `Core/OgImage.php` GD ilə test edilib — vizual olaraq düzgün render (bax qeyd aşağıda).

### Qeyd (dizayn qərarı)
- Space Grotesk/Manrope (bölmə 10.2) əsl font faylları bu mühitə yüklənməyib (internet girişi yoxdur).
  Hazırda `public/assets/fonts/DejaVuSans(.ttf/-Bold.ttf)` fallback kimi bundle edilib (sərbəst lisenziya,
  Azərbaycan/Kiril simvollarını tam dəstəkləyir, OgImage.php də bunu istifadə edir). CSS `--font-head`/
  `--font-body` dəyişənləri Space Grotesk/Manrope-u ilk seçim kimi saxlayır — sahibkar əsl `.woff2`
  fayllarını `public/assets/fonts/`-a əlavə edib `@font-face` bağlaya bilər (README-də qeyd olunacaq).
- İkon/PNG-lər (`icon-192.png`, `icon-512.png`, `badge-72.png`) hazırda funksional placeholder-dır
  (kömür fon + narıncı kvadrat) — real brend loqosu ilə əvəz oluna bilər, texniki tələb (ölçü/format) ödənilib.
