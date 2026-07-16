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

## FAZA 1 — Auth + rollar ✅ TAMAMLANDI

- [x] `/qeydiyyat` — rol seçimi (2 böyük kart), müştəri forması, sürücü forması (maşın növü/qeyd/foto)
- [x] Müştəri qeydiyyatı → dərhal aktiv + avtomatik giriş (Q-Y16)
- [x] Sürücü qeydiyyatı → `driver_status='pending'`, `billing_status='trial'`, foto WebP-ə kodlanır,
      avtomatik giriş + "Profilin yoxlanılır" banner-i lentdə (stub)
- [x] `/giris`, `/cixis` — 5 cəhd/15 dəq kilid, "yadda saxla" (HMAC remember-cookie, 30 gün)
- [x] `Phone::normalize()` — bax FAZA 0 test nəticələri, 994XXXXXXXXX formatı
- [x] CSRF token bütün POST formalarında
- [x] Müştəri/sürücü stub dashboard-ları (tam funksionallıq FAZA 2/3-də)

### Özünüyoxlama nəticələri (HTTP səviyyəsində, real server + MySQL)
- Müştəri qeydiyyatı → DB-də düzgün sətir, dərhal `/musteri/elanlarim`-a yönləndirilir. ✓
- Sürücü qeydiyyatı (multipart foto ilə) → foto real WebP-ə kodlanıb saxlanılıb, `driver_status=pending`,
  `trial_until` = bugün+30, lentdə "Profilin yoxlanılır" banner-i göstərilir. ✓
- **Pending sürücü təklif verə bilmir**: `Auth::isActiveDriver()` pending üçün `false`, `approved`-a
  keçiriləndə `true` qaytarır (DB-səviyyəsində unit-test edilib). Tam UI/endpoint enforcement (təklif
  düyməsinin kilidli overlay-i) FAZA 3-də təklif endpoint-i yaradılanda YENİDƏN yoxlanılacaq.
- **5 format nömrə testi**: FAZA 0-da 8 fərqli format (0501234567, +994501234567, 994501234567,
  501234567, boşluqlu, tire-li, 00994 prefiksli, mötərizəli) → hamısı `994501234567`-ə normallaşdı;
  2 yanlış format düzgün rədd edildi.
- Login kilidi: 5 səhv cəhddən sonra 6-cı cəhd (düzgün şifrə ilə belə) kilidlənmə mesajı verir;
  kilid silinəndən sonra düzgün şifrə ilə giriş uğurlu. ✓
- Remember-cookie: sessiya cookie-si silinəndə belə (brauzer bağlanma simulyasiyası) HMAC remember-cookie
  ilə avtomatik giriş işləyir; manipulyasiya olunmuş (imza uyğunsuz) cookie rədd edilir. ✓
- Eyni nömrə fərqli formatda (050-123-45-67) təkrar qeydiyyatda "artıq qeydiyyat var" xətası verir. ✓
- CSRF token olmadan POST → HTTP 419. ✓

## FAZA 2 — Elan + lent ✅ TAMAMLANDI

- [x] `/musteri/elan/yeni` — kateqoriya kartları (ikon+hint-hazır sxem), haradan/hara + detal xəbərdarlığı,
      tarix rejimi (razılaşma/konkret) + təcili çipi, təsvir, foto (maks 6). Qiymət sahəsi YOXDUR (Q-Y2).
- [x] Yaradılışda: `scope` avtomatik (`ListingRules::scopeFor`), `expires_at` hesablanır, unikal `public_code`,
      `listing_events` "created" qeydi, `sse_events`-ə `listing_new` yazılır (FAZA 4-də real yayım),
      OG şəkli bir dəfə render olunur (`OgImage::saveForListing`)
- [x] Müştəri elan idarəsi (`/musteri/elan/{id}`): status paneli, foto, sil/uzat (1 dəfə), paylaşım linki,
      təkliflər bölməsi (boş — FAZA 3-də dolacaq)
- [x] Sürücü lenti (`/surucu/lent`): Bakı/Bölgələrarası tab, kateqoriya filtri, təcili üstdə, kart dizaynı
- [x] Sürücü elan səhifəsi (`/surucu/elan/{id}`): pending/inaktiv üçün kilid banner-i, aktiv sürücüyə
      "təklif forması tezliklə" (FAZA 3 üçün yer saxlanılıb)
- [x] Public paylaşım kartı `/e/{code}` (Q-Y10): loginsiz, marşrut/kateqoriya/tarix/təsvir/foto/təklif SAYI —
      nömrə və qiymət YOXDUR, OG meta teqləri (`layouts/public.php`)
- [x] Müştəri tarixçəsi (`/musteri/tarixce`, Q-Y11): tamamlanmış/bitmiş/silinmiş elanlar + "Yenidən sifariş"
      (köhnə elanın kopyası ilə doldurulmuş forma)
- [x] Gündə maks 5 aktiv elan limiti (bölmə 12.1)

### Özünüyoxlama nəticələri (real server + MySQL + Playwright)
- Elan yaratma HTTP round-trip **0.046 saniyə** (tələb: ≤60 saniyə). ✓
- Scope hesablanması: Yasamal(Bakı)→Gəncə(bölgə) → `intercity` DB-də düzgün yazılıb. ✓
- **Tablar düzgün ayırır**: Bakı-daxili tab boş qayıtdı, Bölgələrarası tab elanı göstərdi (server-side
  filtr, DB sorğusu ilə təsdiqləndi). ✓
- **Public səhifədə nömrə YOXDUR**: `/e/{code}` HTML-i `994`/`+994`/`phone` pattern-lərinə qarşı grep
  edildi — heç bir uyğunluq tapılmadı; eyni şəkildə qiymət/AZN də yoxdur (sorğu səviyyəsində belə
  seçilmir, yalnız `COUNT(*)` təklif sayı). ✓
- OG şəkli avtomatik generasiya olunub (`storage/og/{code}.png`, 1200×630 PNG, GD ilə). ✓
- Pending sürücü elan səhifəsində kilid banner-i görür, approved+trial sürücü təklif yerini görür
  (offer forması FAZA 3-də aktivləşəcək). ✓
- Playwright vizual test zamanı **2 real bug tapılıb və düzəldilib**:
  1. Bottom-nav-ın mərkəzi "+" düyməsində PHP kodu səhvən düymənin GÖRÜNƏN mətninə yazılmışdı
     (`class="fab"><?= $active(...) ?>+` → ekранда hərfi ilə "active+" mətni çıxırdı) — CSS class
     atributuna köçürüldü.
  2. `.fab` düyməsi `.bottom-nav a`-nın ümumi `flex:1` qaydasını miras alıb oval şəklə düşmüşdü —
     `flex:0 0 44px` əlavə edilərək dairəvi формасы bərpa olundu.
  Hər iki düzəlişdən sonra təkrar screenshot ilə vizual təsdiqləndi.

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
