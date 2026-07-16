# Birlikdə Getdik — İcra jurnalı

Bu fayl + `BIRLIKDƏ_GETDİK_TAM.pdf` (texniki şərtnamə) Claude Code üçün yeganə həqiqət
mənbəyidir (bölmə 14). Kontekst sıfırlansa, işə buradan davam edilir.

## FAZA 0 — Skelet ✅ (tamamlandı)

### Nə edildi

- **Qovluq strukturu** bölmə 5-ə tam uyğun yaradıldı: `public/`, `public_admin/`, `app/`
  (Core, Payments, Controllers/{Site,Owner,Admin}, views/{site,owner,admin}, lang),
  `storage/{uploads/houses,tmp,logs}`, `cron/`, `deploy/`.
- **`app/config.php`** — DB, Payriff (placeholder açarlarla, Q7), sessiya/remember,
  sabitlər (default qiymət, trial günü, dil siyahısı).
- **`app/bootstrap.php`** — autoload (`spl_autoload_register`, Core/Payments/Controllers
  qovluqlarını tarayır), sessiya (httponly, samesite=Lax, HTTPS aşkarlanarsa secure),
  `Lang::boot()`.
- **Core siniflər:**
  - `DB.php` — PDO tək-instans wrapper, `query/one/all`, tranzaksiya köməkçiləri
    (bütün SQL bundan keçməlidir — 12.1).
  - `Router.php` — `{param}` dəstəkli sadə regex router, `setNotFound()` ilə
    admin/sayt üçün ayrı 404 handler-ları.
  - `View.php` — layout+content render, `View::e()` htmlspecialchars köməkçisi (12.1).
  - `Lang.php` — cookie-əsaslı dil seçimi (`?lang=` → cookie), `az/ru/en` lüğəti,
    dəstəklənməyən dil daxil edilsə `DEFAULT_LANG`-a düşür.
  - `Csrf.php` — sessiya-əsaslı token generasiya/doğrulama (12.1).
  - `Phone.php` — normalizasiya (bölmə 5.1) tam icra olundu: 0-prefiks, 994-prefiks,
    9-rəqəmli, digitsOnly() admin qismən-axtarış üçün, `toWaLink()` köməkçisi.
- **Dil faylları** `app/lang/{az,ru,en}.php` — nav/footer/salam açarları.
- **Ana səhifə** (`Site/Home.php` + `views/site/{layout,home,404}.php`) — "Salam!" 3 dildə.
- **Front kontrollerlər** `public/index.php`, `public_admin/index.php` — Router
  quraşdırılır, admin üçün ayrı 404 view.
- **PWA skelet fayllari**: `manifest.webmanifest` (bölmə 11.1-dən tam), boş/minimal
  `sw.js` (yalnız install/activate, tam keş strategiyası Faza 5-də), `offline.html`.
- **`public/uploads`** → `../storage/uploads` simlink (yalnız oxu).
- **`install.sql`** — bölmə 4-dəki bütün 13 cədvəl (settings, regions, owners, payments,
  houses, house_photos, amenities, house_amenities, house_calendar, stats_daily, admins,
  admin_logs, sse_events) + seed: 5 settings sətri, 10 bölgə, 14 şərait, 1 admin
  (`admin` / `ChangeMe!2026` — **istehsalatda dərhal dəyişilməlidir**).
  - Qeyd: mənbə PDF-də səhifə kəsilməsi ucbatından `houses` və `house_amenities`
    cədvəllərinin son sətirləri (indekslər/FK-lar) qarışıq sıralanmışdı; sxem sütun
    adlarına görə məntiqi olaraq düzgün cədvələ bərpa edildi (`idx_owner` +
    `FULLTEXT idx_search(title,village,description)` → `houses`,
    FK cütləri → `house_amenities`).
- **`deploy/nginx.conf.example`** — 2 subdomain (bölmə 3), upload limitləri
  (`client_max_body_size 25M`), `/sse` üçün `fastcgi_buffering off`, admin üçün
  `X-Frame-Options DENY`, `/storage/` bloklanması.
- **`deploy/php-fpm-pool.conf.example`** — `upload_max_filesize 20M`,
  `post_max_size 25M` (nginx `fastcgi_param` ilə DEYİL, PHP-FPM pool səviyyəsində).

### Nə test olundu

1. `install.sql` təmiz MariaDB 10.11 (MySQL 8.0 uyğun) bazasına **xətasız** import
   olundu; 13 cədvəl, FULLTEXT indeks və bütün FK-lar təsdiqləndi.
2. `/` səhifəsi AZ/RU/EN-də düzgün açıldı (`?lang=`), dil cookie-yə yazılıb sonrakı
   sorğuda saxlanıldı.
3. 404 həm sayt, həm admin front kontrollerində işlədi (ayrı-ayrı view-larla).
4. Statik fayllar (`assets/css/app.css`, `robots.txt`, `manifest.webmanifest`)
   nginx `try_files`-ı imitasiya edən dev router ilə düzgün served olundu.
5. `Phone::normalize()` 6 fərqli formatda (`0501234567`, `994501234567`,
   `+994501234567`, `501234567`, `050-123-45-67`, `(050) 123 45 67`) eyni nəticəni
   (`994501234567`) verdi.
6. Bütün PHP fayllar `php -l` ilə sintaksis xətasız yoxlanıldı.

### Fayllar

```
app/config.php, bootstrap.php
app/Core/{DB,Router,View,Lang,Csrf,Phone}.php
app/Controllers/Site/Home.php
app/views/site/{layout,home,404}.php
app/views/admin/{layout,placeholder}.php
app/lang/{az,ru,en}.php
public/index.php, robots.txt, manifest.webmanifest, sw.js, offline.html
public/assets/css/app.css, assets/js/app.js
public/uploads -> ../storage/uploads (symlink)
public_admin/index.php, robots.txt, assets/admin.css
install.sql
deploy/nginx.conf.example, deploy/php-fpm-pool.conf.example
.gitignore
```

### Növbəti addım — FAZA 1 (Qonaq vitrini)

Ana səhifə tam versiyası (hero, axtarış bloku, kateqoriya çipləri, bölgələr zolağı,
populyar evlər), bölgə səhifəsi + filtrlər, ev səhifəsi + WhatsApp track/redirect axını,
axtarış nəticələri, statik səhifələr (`/haqqinda`, `/sertler`, `/mexfilik`,
`/ev-sahibi-ol`), seed-ə 6 demo ev. Qərarlar reyestrinə (bölmə 2) zidd improvizasiya
qadağandır — xüsusilə Q3 (WhatsApp mesaj mətni), Q4 (təqvim vitrin məlumatıdır,
zəmanət deyil), Q13 (AZ məcburi, RU/EN könüllü).

Yoxlama kriteriyaları (FAZA 1 üçün, bölmə 13.2): qeydiyyatsız tam axın
ana→bölgə→ev→WhatsApp linki düzgün mətnlə açılır; filtrlər SQL injection testindən
keçir; 404 səhifəsi işləyir.
