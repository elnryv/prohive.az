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

## FAZA 1 — Qonaq vitrini ✅ (tamamlandı)

### Nə edildi

- **`app/Models/`** (yeni qovluq — spec-də ayrı adlandırılmayıb, sorğu təkrarını
  aradan qaldırmaq üçün): `RegionRepository`, `HouseRepository`, `AmenityRepository`.
  Bütün SQL PDO bound params ilə (12.1). Görünürlük qaydası (4.3) mərkəzi
  `VISIBILITY_SQL` sabitində.
- **`app/Core/ListingFilters.php`** — bölgə/axtarış filtrlərinin sanitizasiyası
  (price/guests/date/sort/amenity_ids tipə görə cast, whitelist sort).
- **`app/Core/Dedupe.php`** + **`RateLimit.php`** — fayl-kilidli view/klik dedupe
  (storage/tmp, 30 dəq TTL) və 30/dəq/IP rate limit (track endpointi üçün, login/
  qeydiyyat Faza 2-də təkrar istifadə edəcək).
- **Kontrollerlər**: `Home` (tam: hero+axtarış+çiplər+bölgələr+populyar),
  `Region` (filtr+sıralama+AJAX səhifələmə), `Search` (`/axtar`, bölgə "hamısı"
  daxil), `House` (qalereya+təqvim+şərait+xəritə+sticky WA paneli+baxış sayğacı),
  `Track` (`POST /api/track/wa` — Origin yoxlanışı + rate limit + CSRF-istisna),
  `StaticPage` (`/haqqinda`, `/sertler`, `/mexfilik`, `/ev-sahibi-ol`).
- **View-lar**: `site/{home,region,search,house,static,become_host}.php` +
  `site/partials/{house_card,house_cards,filters_form}.php`. `layout.php`
  yeniləndi: dil keçidi cari query-ni saxlayır, nav + footer CTA əlavə olundu.
  `app.css` tam genişləndirildi (dizayn tokenlərinə uyğun: kartlar, filtrlər,
  qalereya, təqvim grid, sticky panel). `app.js`-ə AJAX "Daha çox göstər",
  WhatsApp mesaj tərtibi + track fetch, checkout-min tarix sinxronu əlavə olundu.
- **WhatsApp axını (Q3, 6.4.1)** tam client-side: düymə → `fetch(POST /api/track/wa,
  keepalive)` → `window.location = wa.me linki`. Mesaj mətni node ilə simulyasiya
  edilib spec-dəki nümunə ilə **hərfi eyni** olduğu təsdiqləndi (aşağıda).
- **`seed_demo.php`** (repo kökündə, install.sql-dən sonra bir dəfə işə salınır) —
  3 sahibkar (free/paid/trial statuslarının hərəsindən), 6 bölgədə 6 ev, hər evə
  GD ilə generasiya olunan 4 yaşıl-gradient placeholder WebP foto (10.2), bəzi
  evlərə `house_calendar` dolu günləri (maybe_full test üçün). Idempotentdir.

### Nə test olundu

1. Ana→bölgə→ev axını: bütün səhifələr 200, başlıqlar 3 dildə düzgün göstərildi.
2. **WhatsApp mesaj mətni** — `?checkin=2026-08-17&checkout=2026-08-24&guests=4`
   ilə ev səhifəsinə keçid edib JS məntiqini Node-da simulyasiya etdim:
   ```
   Salam! "Meşəkənarı ev" (Qəbələ) haqqında məlumat almaq istəyirəm.
   Tarix: 17.08 – 24.08 | Qonaq: 4
   — Birlikdə Getdik vasitəsilə: https://getdik.birlikde.biz/ev/meshekenari-ev-qebele-1
   ```
   Tarix/qonaq olmadıqda orta sətir tam buraxıldığı da ayrıca yoxlanıldı.
3. **SQL injection**: `price_min`, `guests`, `sort`, `amenities[]`, `checkin`,
   `region`, ev slug-una 7 fərqli inyeksiya cəhdi (`' OR '1'='1`, `; DROP TABLE`,
   `UNION SELECT`) — hamısı ya 200 (zərərsiz nəticə ilə) ya 404 qaytardı,
   `SHOW TABLES` + `SELECT COUNT(*) FROM houses` inyeksiyadan sonra da bazanın
   toxunulmaz qaldığını təsdiqlədi.
4. 404: naməlum yol, naməlum ev slug-u, naməlum bölgə slug-u → hamısı 404.
5. Sıralama (`price_asc`) və səhifələmə (`limit=3,offset=3`) `HouseRepository`
   üzərindən birbaşa yoxlanıldı — düzgün nəticə.
6. **`maybe_full` bayrağı**: tarix aralığı `house_calendar`-dakı dolu günlərlə
   üst-üstə düşən ev nəticələrin SONUNA düşdü (Q4-ə uyğun, gizlədilmədi).
7. `/api/track/wa`: düzgün Origin + `house_id` ilə 200 və `wa_clicks_total`/
   `stats_daily` artımı; yad Origin → 403; mövcud olmayan `house_id` → 404.
8. **Baxış dedupe**: real test zamanı **bir bug tapıldı və düzəldildi** —
   `Dedupe::shouldCount()` faylı `fopen(..., 'c+')` ilə açırdı, bu isə fayl
   yoxdursa onu DƏRHAL yaradırdı və nəticədə mtime yoxlaması ilk ziyarəti də
   "artıq sayılıb" hesab edirdi (ilk baxış heç vaxt sayılmırdı). Fix: mtime
   fopen-dən ƏVVƏL, fayl mövcudluğu yoxlanaraq oxunur. Fix-dən sonra: 1-ci
   baxış (browser UA) `views_total`-u artırdı, 2-ci baxış (30 dəq içində, eyni
   IP) düzgün deduped oldu, bot/curl UA heç vaxt saymadı.
9. Dil keçidi bölgə/axtarış səhifələrində aktiv filtrləri (`guests`, `sort`)
   URL-də saxladı; RU/EN-də tərcümə olunmamış sahələr üçün AZ mətn + xəbərdarlıq
   qeydi göstərildi (Q13).
10. `seed_demo.php` iki dəfə ardıcıl işə salındı — ikinci dəfə heç nə
    dublikatlaşdırmadı (owner/house artıq var yoxlaması).
11. Bütün dəyişdirilmiş/yeni PHP faylları `php -l` ilə sintaksis təmiz.

### Məlum məhdudiyyətlər / Faza 6-ya saxlanılan

- Qiymət filtri "slider" əvəzinə min/maks rəqəm sahələri ilə icra olundu
  (funksional cəhətdən eynidir, vizual cilalanma Faza 6-nın işidir — spec
  özü də son dizayn keçidini Faza 6-da nəzərdə tutur).
- `/sahib/giris`, `/sahib/qeydiyyat` linkləri (nav, become_host CTA) hələ
  404 qaytarır — Faza 2-nin işidir, qəsdən stub saxlanılıb.
- Ay adları (təqvimdə) `IntlDateFormatter` ilə lokallaşdırılıb; ICU məlumat
  bazasında `az_AZ` üçün tam dəstək server mühitindən asılıdır (fallback:
  ingiliscə `format('F Y')`).

### Növbəti addım — FAZA 2 (Ev sahibi)

Qeydiyyat/giriş (`/sahib/qeydiyyat`, `/sahib/giris`), 4 addımlı ev əlavə/redaktə
sehrbazı, `Core/Upload.php` (WebP re-encode, EXIF təmizləmə), təqvim idarəetməsi,
panel (statik statistika ilə başlanğıc), draft/pending axını. Qərarlar reyestrinə
zidd: Q1 (qonaq qeydiyyatsız qalır — bu fazaya toxunmur), Q2 (OTP/SMS YOX), Q6
(avtomatik kart çıxılması YOX — bu, Faza 4-ün işidir).

Yoxlama kriteriyaları (FAZA 2, bölmə 13.2): yeni sahib ev yaradıb təsdiqə göndərir;
4 fotodan az → göndərmə bloklanır; nömrə normalizasiyası 5 fərqli formatda test
(bu, Core/Phone.php üçün Faza 0-da artıq edilib — Faza 2-də qeydiyyat axınında
təkrar doğrulanacaq).
