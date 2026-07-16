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

## FAZA 2 — Ev sahibi ✅ (tamamlandı)

### Nə edildi

- **`app/Core/Auth.php`** — owner sessiyası: `login/logout/check/id/user/requireLogin`,
  imzalı (HMAC) remember-cookie (12.1, cədvəlsiz), 30 gün.
- **`app/Core/RateLimit.php`** genişləndirildi: `tooMany()`/`hit()`/`clear()` —
  login yalnız UĞURSUZ cəhdləri sayır (uğurlu login limitə təsir etmir),
  qeydiyyat hər cəhdi sayır (3/saat/IP).
- **`app/Core/Upload.php`** (12.2 tam): finfo MIME sniffing (uzantıya güvənilmir),
  GD ilə yenidən render (EXIF təmizlənir) → WebP maks 1600px, keyfiyyət 80;
  video: MP4 MIME yoxlaması + 30MB limit, ffmpeg varsa 720p re-encode, yoxdursa
  olduğu kimi saxlanılır; fayl adları random hash.
- **`app/Models/OwnerRepository.php`**, **`SettingsRepository.php`** — qeydiyyat,
  görünürlük qaydası (`isVisible`), effektiv qiymət hesablama (Q8: custom_price
  NULL-dursa ümumi qiymət).
- **`HouseRepository`** genişləndirildi: sahib-scope sorğular (`forOwner`,
  `findOwnedById`), `createDraft`/`updateFields` (whitelist sütunlar),
  `setAmenities`, `submitForApproval` (yalnız draft/rejected → pending —
  redaktə qaydası, 4.5), foto CRUD, təqvim `toggleBusyDay`/`markRange`,
  `stats30d`.
- **Owner kontrollerləri**: `Register`, `Login` (+ logout), `Dashboard`,
  `Billing` (Faza 4-ə qədər "tezliklə" stub-u ilə), `HouseEdit` (4 addımlı
  sehrbaz: addım 1 draft yaradır, 2/3 AJAX-sız POST ilə yenilənir, 4-də
  foto+təsdiqə göndər), `Photos` (AJAX upload/sil/üz qabığı), `Calendar`
  (gün/aralıq AJAX).
- **Owner view qatı** + `app.css`/`app.js` genişləndirilməsi (wizard, foto
  grid, redaktə oluna bilən təqvim, sparkline-lı panel).
- Bütün owner.* açarları 3 dildə (`az/ru/en`) əlavə olundu; **validasiya xəta
  mesajları hələlik yalnız AZ-dır** (bax aşağıda, bilinən sadələşdirmə).

### Nə test olundu (canlı HTTP + DB üzərindən, cookie-jar ilə sessiya saxlanaraq)

1. **Qeydiyyat, 5 fərqli telefon formatı**: `0501234601`, `+994501234602`,
   `994501234603`, `501234604`, `050-123-46-05` — hamısı `9945012346XX`-ə
   normallaşdı və verilənlər bazasında düz formatda saxlanıldı.
2. **Qeydiyyat rate limit** (3/saat/IP): 4-cü ardıcıl cəhd düzgün bloklandı;
   limiti sıfırlayıb qalan 2 format da testdən keçdi.
3. **Login lockout** (5/15dəq, yalnız uğursuz cəhdlər): 5 səhv şifrə cəhdi
   keçdi, 6-cı bloklandı; blok aktivkən DÜZGÜN şifrə ilə belə giriş rədd
   edildi (gözlənilən davranış).
4. **CSRF qorunması**: saxta/köhnə tokenlə qeydiyyat cəhdi rədd edildi, heç
   bir sətir yaradılmadı.
5. **Remember-me**: giriş zamanı imzalı cookie qoyuldu; sessiya cookie-si
   silinəndən sonra da (yeni brauzer sessiyası simulyasiyası) remember-cookie
   ilə `/sahib/panel`-ə avtomatik giriş baş tutdu.
6. **Tam 4-addım sehrbaz**: addım 1 (əsas məlumat) → draft ev yaradıldı →
   addım 2 (qiymət/əlaqə, whatsapp nömrəsi normallaşdı) → addım 3 (2 şərait
   bağlandı) → addım 4-də 4 real şəkil (jpg/png/webp qarışıq) yükləndi —
   hamısı WebP-ə re-encode olundu (`getimagesize` ilə təsdiqləndi, MIME
   `image/webp`), ilk foto avtomatik üz qabığı oldu, `is_approved=0`.
7. **Min 4 foto qaydası**: 0 fotoyla "Təsdiqə göndər" server tərəfdə
   bloklandı (müvafiq xəta mesajı ilə); 4 foto olduqda təqdim uğurla keçdi,
   `houses.status` `draft` → `pending` oldu.
8. **Zərərli fayl testi**: `.php` faylı `.jpg` adı ilə yükləndi — finfo MIME
   sniffing həqiqi məzmuna görə rədd etdi (uzantıya baxmadı); mətn məzmunlu
   saxta `.mp4` də həm video, həm şəkil kimi rədd edildi.
9. **Sahiblik yoxlaması**: bir sahibin başqasının evini redaktəyə cəhdi 404
   ilə bloklandı.
10. **Redaktə qaydası (4.5)**: `approved` statuslu evin başlığı dəyişdirildi
    — status `approved` olaraq QALDI (pending-ə qayıtmadı), yalnız yeni foto
    `is_approved=0` ilə gəlir (artıq addım 6-da təsdiqlənib).
11. **Təqvim**: bir günə iki dəfə toxunma düzgün busy→free keçidi verdi;
    aralıq (4 gün) düzgün hamısı busy işarələndi.
12. Server loqunda bütün test sessiyası boyu heç bir PHP warning/notice/
    fatal qeydə alınmadı. Bütün dəyişdirilmiş fayllar `php -l` təmiz.

### Məlum məhdudiyyətlər / sonrakı fazalara saxlanılan

- Validasiya xəta mesajları (Register/Login/HouseEdit) hələlik yalnız AZ-dır
  — UI xromu (düymələr, etiketlər) tam 3-dillidir, server mesajları Faza 6-da
  tərcümə oluna bilər.
- Video yükləmə yolu MIME-rədd testi ilə yoxlanıldı, amma sandbox-da ffmpeg
  olmadığı üçün real MP4 ilə end-to-end test edilmədi (kod nəzərdən keçirilib;
  ffmpeg yoxdursa spec-in özü "olduğu kimi saxla" fallback-ını tələb edir).
- "Qiymət/əlaqə" addımında xəritə koordinatları (lat/lng) sehrbazın 2-ci
  addımına əlavə edildi — sxemin mövcud sütunlarına uyğun, spec-in 4-addım
  cədvəlində açıq yer göstərilməyib, lakin təbii yeri budur.
- Abunə/ödəniş kartı (7.6) yalnız GÖRÜNTÜ səviyyəsində hazırdır — "Ödə"
  düyməsi Faza 4-ə qədər deaktiv, "tezliklə" qeydi ilə.

### Növbəti addım — FAZA 3 (Admin)

Giriş (username+şifrə, 2 uğursuz→30san gecikmə, 5 uğursuz→15dəq IP kilidi),
dashboard (kartlar+SSE canlı lent Faza 5-ə qədər statik), təsdiq növbəsi
(pending evlər + yeni fotolar), owners (axtarış — Q11 telefon hissəvi axtarış,
status/qiymət idarəsi — Q9/Q10), settings (ümumi qiymət — Q8), regions/
amenities CRUD, admin_logs. Qərarlar reyestrinə zidd: Q9 (admin bir kliklə,
səbəbsiz status keçidi, ANINDA qüvvəyə minir), Q10 (mövcud ödəniş linki öz
məbləğində qalır — Faza 4 ilə birlikdə tam mənalanacaq), Q11 (telefon
axtarışı `Phone::digitsOnly()` + LIKE, artıq Core/Phone.php-də hazırdır).

Yoxlama kriteriyaları (FAZA 3, bölmə 13.2): pending ev təsdiqlənir və saytda
görünür; nömrə hissəvi axtarışı ("50 123") tapır; pulsuz/pullu keçid anında
görünürlüyə təsir edir; hər əməliyyat admin_logs-a düşür.

## FAZA 3 — Admin ✅ (tamamlandı)

### Nə edildi

- **`app/Core/AdminAuth.php`** — admin sessiyası, 30 dəq hərəkətsizlik → avtomatik
  çıxış, 2 uğursuz cəhddən sonra captcha əvəzinə 30 saniyəlik süni gecikmə,
  5 uğursuz cəhd → 15 dəq IP kilidi (RateLimit-in `tooMany/count/hit`
  metodları üzərindən).
- **`app/Core/AdminLog.php`** — bütün admin əməliyyatları `admin_logs`-a yazır
  (səbəb tələb olunmur, Q9).
- **Ayrı sessiya konteksti**: `APP_CONTEXT` sabiti (`public_admin/index.php`
  `'admin'` təyin edir) + `ADMIN_SESSION_NAME` — admin və ev sahibi
  sessiyaları fərqli cookie adları ilə tamamilə izolyasiya olunub (eyni
  hostda/portda test edərkən belə toqquşmur, real deploy-da ayrı subdomain
  olduğu üçün bu, əlavə təhlükəsizlik qatıdır).
- **`AdminRepository`**: dashboard statistikası (bugünkü baxış/klik,
  aktiv abunə sayı, MRR, pending sayı, 30-günlük bölgə tələbi cədvəli),
  admin_logs filtrli sorğusu.
- **`OwnerRepository`, `RegionRepository`, `AmenityRepository`** admin-scope
  metodlarla genişləndirildi: telefon-hissəvi + ad/ev-adı axtarışı (Q11),
  `setFree/makeBillable/clearCustomPrice/block/activate` (Q9 — status
  ANINDA effektiv olur, ayrı keş/onay addımı yoxdur), region/amenity CRUD
  (silinmə yalnız bağlı qeyd yoxdursa).
- **`HouseRepository`** genişləndirildi: `pendingHouses`, `findByIdAdmin`,
  `approve` (ev + bütün fotolarını təsdiqləyir), `reject` (səbəblə),
  `pendingPhotos`/`approvePhoto`/`declinePhoto` (artıq yayımda olan evə
  sonradan əlavə olunan fotolar üçün).
- **Admin kontrollerləri**: `AdminLogin`, `AdminDashboard`, `Approvals`,
  `Owners`, `OwnerCard`, `Settings`, `Regions`, `Amenities`, `Payments`
  (Faza 4-ə qədər oxu-yalnız stub), `Logs`.
- Admin view qatı (ayrı, yüngül `admin.css` — 3-dilli deyil, tək dil AZ,
  spec-in "ayrıca, yüngül" tələbinə uyğun) + `public_admin/uploads` simlink
  (foto icmalı üçün).

### ⚠️ Test zamanı tapılan və düzəldilən İKİ real bug

1. **Sinif adı toqquşması**: `Controllers/Owner/Login.php` və
   `Controllers/Owner/Dashboard.php` ilə `Controllers/Admin/Login.php` və
   `Controllers/Admin/Dashboard.php` eyni sinif adlarını (`Login`,
   `Dashboard`) bölüşürdü. Layihə PHP namespace istifadə etmədiyi üçün
   (spec: "framework YOX"), avtoloader qovluqları sırayla axtardığından
   (`Core → Models → Payments → Site → Owner → Admin`) admin-in `Login`/
   `Dashboard` sinifləri HEÇ VAXT yüklənmirdi — admin girişi səssizcə
   ev sahibi `Login` sinfini işə salırdı (səhv mesajları admin
   kontekstində "Telefon nömrəsi..." kimi göstərirdi). Fix: admin
   kontrollerləri `AdminLogin`/`AdminDashboard` adına köçürüldü, bütün
   layihə üzrə digər kontroller adları toqquşma üçün proqramla
   yoxlanıldı (təkrar yoxdur).
2. **PDO təkrar adlı parametr**: `OwnerRepository::search()`-də eyni
   `:text_like` placeholder-i bir SQL sorğusunda İKİ dəfə istifadə
   olunurdu (`o.full_name LIKE :text_like OR h.title LIKE :text_like`).
   `DB.php` `PDO::ATTR_EMULATE_PREPARES=false` ilə işlədiyi üçün bu,
   canlı testdə `SQLSTATE[HY093]: Invalid parameter number` fatal
   xətası ilə nəticələndi (500). Fix: hər occurrence üçün ayrı ad
   (`:text_like1`, `:text_like2`). Bütün Models/Controllers faylları
   proqramla (regex skript) təkrar-placeholder pattern-i üçün
   yenidən yoxlanıldı — başqa hal tapılmadı.

### Nə test olundu (canlı HTTP, iki ayrı dev server — sayt 8150, admin 8151)

1. **Tam təsdiq axını**: ev `pending` statusda ikən sayt tərəfində `/ev/{slug}`
   404 qaytardı → admin `/tesdiq`-də görünüb təsdiqləndi → DB-də status
   `approved` oldu → EYNİ ANDA sayt tərəfində 200 + bölgə siyahısında göründü.
   `admin_logs`-a `house.approve` yazıldığı təsdiqləndi.
2. **Telefon hissəvi axtarışı (Q11)**: `551000002` → Aygün Hüseynovanı tapdı;
   ad üzrə (`Elvin`) və ev adı üzrə (`Yaşıl vadi`) axtarış da ayrı-ayrı
   düzgün nəticə verdi.
3. **Pulsuz/pullu keçid ANINDA (Q9)**: sahib bloklanan kimi evi sayt tərəfində
   dərhal 404 oldu; "Pulsuz et" edilən kimi eyni sorğuda dərhal 200-ə qayıtdı
   (aralıq/keş yoxdur — hər sorğuda canlı hesablanır). "Pullu et" fərdi
   qiymətlə (`custom_price=15`) tətbiq edildi, sonra silindi — hər addım
   `admin_logs`-da izlənildi (`owner.block`, `owner.set_free`,
   `owner.set_paid`, `owner.clear_custom_price`).
4. **Admin login qorunması**: cəhd 1-2 gecikməsiz, cəhd 3 ~30 saniyə gecikmə
   ilə cavab verdi (ölçüldü: 0s, 1s, 30s) — captcha əvəzi süni gecikmə
   təsdiqləndi.
5. **Sessiya izolyasiyası**: admin cookie adı `getdik_admin_sess` (sayt/ev
   sahibinin `getdik_sess`-dən fərqli) təsdiqləndi.
6. **Region/Amenity CRUD**: yeni şərait yaradıldı; bağlı evi olan bölgənin
   silinmə cəhdi bloklandı (`xeta=bagli_ev`), evi olmayan bölgə uğurla silindi;
   istifadədə olan şərait (Wi-Fi) silinmə cəhdi bloklandı.
7. **Yeni foto təsdiq axını**: onsuz da `approved` evə sahib tərəfindən əlavə
   olunan foto `is_approved=0` ilə gəldi, admin `/tesdiq`-in "Yeni fotolar"
   bölməsində göründü, təsdiqləndikdən sonra dərhal sayt tərəfində göründü.
8. **Settings yenilənməsi**: ümumi qiymət 25→30, dəstək nömrəsi normallaşaraq
   saxlanıldı.
9. Server loqlarında (hər iki bug düzəldildikdən sonra) heç bir PHP
   warning/fatal qalmadı. Bütün fayllar `php -l` təmiz.

### Məlum məhdudiyyətlər

- Admin paneli tək dildə (AZ) — spec 9-cu bölmə admin üçün 3-dillilik tələb
  etmir (yalnız qonaq/ev sahibi tərəfləri 3-dilli, Q13).
- SSE canlı lent (9.1) Faza 5-ə saxlanılıb; dashboard statistikası hazırda
  səhifə yükləndikdə server-side hesablanır (statik snapshot).
- `/payments` siyahısı oxu-yalnızdır və hazırda boşdur — real ödəniş yaradan
  axın Faza 4-ün (Payriff inteqrasiyası) işidir.
- 30 dəqiqəlik sessiya timeout-u kod baxışı ilə təsdiqləndi (`AdminAuth::check()`
  məntiqi), real vaxtda 30 dəqiqə gözləyib canlı test edilmədi.

### Növbəti addım — FAZA 4 (Payriff + abunə)

`PayriffProvider` (bölmə 8-də artıq tam yazılıb, PAYRIFF_SECRET_KEY
doldurulmayanda "tezliklə" rejimi), `/sahib/odenis` ödəniş axını (Billing
kontrolleri artıq mövcuddur, yalnız "Ödə" düyməsini real axına qoşmaq
lazımdır), `/odenis/callback` (status re-check ilə, Payriff cavabına
etibar edilmir — bölmə 8.4), `cron/payriff_recheck.php`, `cron/daily.php`
(abunə bitmə yoxlaması, təqvim təmizliyi, sitemap), expired gizlətmə
məntiqi (artıq `OwnerRepository::isVisible()`-da var, yalnız cron ilə
`billing_status` avtomatik `expired`-ə keçməlidir), Q10 qaydası (mövcud
ödəniş linki öz məbləğində qalır — artıq `payments.amount` sütunu
yaradılan anda fiksə olunur, `PayriffProvider::createOrder` bunu təmin edir).

Yoxlama kriteriyaları (FAZA 4, bölmə 13.2): `PAYRIFF_SECRET_KEY` boş →
düymə "tezliklə" rejimi, xəta yox; mock ilə approved axını `paid_until`-i
düzgün artırır; təkrar callback ikinci dəfə artırmır (idempotent); qiymət
dəyişimi köhnə linkə təsir etmir.
