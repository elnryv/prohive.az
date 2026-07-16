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

## FAZA 4 — Payriff + abunə ✅ (tamamlandı)

### Nə edildi

- **`app/Payments/PaymentGateway.php`** (interfeys) + **`PayriffProvider.php`**
  — bölmə 8.3-ə tam uyğun: `createOrder`/`getOrderStatus`, cURL + JSON,
  `storage/logs/payriff.log`-a audit yazısı, `PAYRIFF_SECRET_KEY` boşdursa
  konstruktor `RuntimeException` atır (Q7).
- **`app/Models/PaymentRepository.php`** — `create()` məbləği YARADILAN ANDA
  fiksə edir (Q10), `applyApproval()` atomik+idempotent (`UPDATE ... WHERE
  status='created'` sətri kilidləyir — `rowCount()=0` olduqda təkrar
  callback heç nə dəyişmir), `owners.paid_until = GREATEST(COALESCE(paid_until,
  trial_until, CURDATE()), CURDATE()) + INTERVAL months MONTH` (8.4 addım 3),
  `staleCreated()`/`expireOldCreated()` (8.5).
- **`Owner/Billing::pay()`** — "Ödə" düyməsi: CSRF+free-status qorunması →
  `PAYRIFF_SECRET_KEY` boşdursa `?netice=tezlikle`-yə yönləndirir (payment
  sətri BELƏ YARADILMIR, boş "created" qeydləri yığılmır) → doludursa
  `PaymentRepository::create()` + `PayriffProvider::createOrder()` →
  `payment_url`-ə yönləndirmə.
- **`Site/PaymentCallback.php`** (`POST /odenis/callback`, bölmə 8.4
  təhlükəsizlik qaydası) — callback gövdəsinə güvənilmir, status HƏMİŞƏ
  `getOrderStatus()` ilə yenidən yoxlanılır, nəticə fərqli olsa belə HƏMİŞƏ
  HTTP 200 qaytarılır (Payriff təkrar cəhdlərini dayandırmaq üçün). Test
  üçün `handle()` (HTTP giriş) və `process(string $raw)` (test edilə bilən
  nüvə məntiq) ayrılıb, həmçinin konstruktor `?PaymentGateway` qəbul edir
  (mock inyeksiyası üçün DI).
- **`cron/daily.php`** (12.4): trial/paid bitmə → `expired`, `house_calendar`
  keçmiş tarixlər + `sse_events` 7 gündən köhnə sətirlər silinir, əsas
  `sitemap.xml` generasiyası (tam SEO cilası Faza 6-nın işidir), `storage/tmp`
  köhnə marker fayllarının təmizlənməsi. **`cron/payriff_recheck.php`** (8.5):
  `status='created'` və 48 saatdan gənc sətirləri yenidən yoxlayır, 48
  saatdan köhnələri `expired` edir. **`deploy/crontab.example`** əlavə olundu.
- Admin dashboard-a "Bitməyə 5 gün qalanlar" bölməsi əlavə olundu
  (`AdminRepository::expiringOwners()`, 12.4 p.3 — hər sahib üçün hazır
  WhatsApp xatırlatma linki).
- `owner/billing.php` view yeniləndi: `paymentsEnabled=true` olduqda real
  POST formu, `?netice=ok/xeta/tezlikle` bildirişləri (3 dildə).

### Nə test olundu

1. **Mock gateway ilə tam ödəniş axını** (`test_payment_flow.php`, `MockApprovedGateway
   implements PaymentGateway`): sahib 1 (billing_status='free', paid_until=NULL) üçün
   27.50 AZN-lik ödəniş yaradıldı → `PaymentCallback::process()` mock
   `APPROVED` statusu ilə çağırıldı → `payments.status='approved'`,
   `owners.billing_status='paid'`, `paid_until` düzgün `CURDATE()+1 ay`
   oldu (`COALESCE` boş tarixləri düzgün idarə etdi).
2. **İdempotentlik**: EYNİ callback gövdəsi İKİNCİ dəfə göndərildi —
   `paid_until` DƏYİŞMƏDİ (2026-08-16 → 2026-08-16), sübut etdi ki, atomik
   `UPDATE ... WHERE status='created'` kilidi təkrar tətbiqin qarşısını alır.
3. **Q10 (qiymət dəyişimi köhnə linkə təsir etmir)**: ödəniş yaradıldıqdan
   SONRA `default_monthly_price` 25→99 dəyişdirildi — `payments.amount`
   27.50-də DƏYİŞMƏDİ (sütun yaradılan anda fiksələnib, sonradan heç vaxt
   yenidən oxunmur).
4. **`PAYRIFF_SECRET_KEY` boş rejimi** (canlı HTTP): billing səhifəsində
   düymə deaktiv + "tezliklə" qeydi; CSRF-keçərli birbaşa `POST
   /sahib/odenis/basla` cəhdi belə `payments` cədvəlinə HEÇ BİR sətir
   yazmadan `?netice=tezlikle`-yə yönləndirildi (xəta yox, boş "created"
   qeydi yaranmadı).
5. **Callback endpoint-i real HTTP üzərindən**: mövcud olmayan `orderId`,
   JSON olmayan gövdə, boş gövdə — hamısı `200 {"status":"ok"}` qaytardı,
   heç bir 500/fatal yoxdur.
6. **`cron/daily.php`**: təmiz işlədi (`sitemap_urls=21`: 6 statik + 9 aktiv
   bölgə + 6 approved ev). Sahib 3-ün `trial_until`-i keçmişə (2020-01-01)
   dəyişdirilib təkrar işə salındı → `trial_to_expired=1`, status `expired`
   oldu, sitemap 21→19 URL-ə düşdü (2 evi artıq gizli) — **VƏ eyni anda
   sayt tərəfində o evlərdən biri 200-dən 404-ə keçdi**, expiry-nin
   görünürlüyə kaskad təsiri canlı təsdiqləndi. Sahib bərpa ediləndə ev
   dərhal yenidən 200 oldu.
7. **`cron/payriff_recheck.php`**: `PAYRIFF_SECRET_KEY` boş olduqda səliqəli
   mesajla dayanır, xəta atmır.
8. Bütün fayllar `php -l` təmiz; test zamanı əlavə bug tapılmadı (əvvəlki
   fazalarda tapılan "təkrar adlı placeholder" pattern-i bu fazada YAZILARKƏN
   bir daha qarşıya çıxdı — `AdminRepository::expiringOwners()`-də `:days`
   iki dəfə istifadə olunmuşdu — amma bu dəfə testə çatmadan kod baxışı
   zamanı tapılıb düzəldildi, canlı xətaya səbəb olmadı).

### Məlum məhdudiyyətlər

- Real Payriff hesabı/açarı olmadığı üçün `createOrder`/`getOrderStatus`-un
  HƏQİQİ Payriff API-yə qarşı işləməsi test edilmədi — yalnız mock
  interfeys vasitəsilə (spec-in özü də FAZA 4 üçün "mock ilə" test tələb
  edir). Kod bölmə 8.3-dəki nümunə ilə hərfi eynidir.
- `sitemap.xml` `.gitignore`-a əlavə olundu (cron-generated fayl, repo-da
  saxlanmır).

### Növbəti addım — FAZA 5 (PWA + SSE + splash)

`manifest.webmanifest`/`sw.js`/`offline.html` Faza 0-da skelet kimi var —
tam keş strategiyası (11.2: app shell cache-first, HTML network-first,
fotolar stale-while-revalidate) yazılmalıdır. Splash animasiyası (6.1,
yalnız PWA rejimində/ilk açılışda, ≤2s, `prefers-reduced-motion` hörməti).
`Core/Sse.php` (11.4: `sse_events`-dən poll, Last-Event-ID, 25s ping) +
owner panelində canlı baxış/klik sayğacı + admin dashboard-da canlı lent
— hazırda hər ikisi səhifə-yükləmə-vaxtı statik hesablanır, bu fazada
`sse_events` cədvəlinə yazma nöqtələri (view/wa_click/yeni-qeydiyyat/
ödəniş/ev-təsdiq) əlavə olunmalı və SSE endpoint-i qurulmalıdır.

Yoxlama kriteriyaları (FAZA 5, bölmə 13.2): Lighthouse PWA installable;
splash ≤2s və reduced-motion-da sönür; iki brauzer pəncərəsi: birində ev
səhifəsi baxışı → digərində owner panel sayğacı 3 saniyə içində artır.

## FAZA 5 — PWA + SSE + splash ✅ (tamamlandı)

### Nə edildi

- **`app/Core/Sse.php`** (11.4): `emit(channel, type, payload)` →
  `sse_events`-ə yazır; `stream(channel)` — 2 saniyəlik DB poll, Last-Event-ID
  ilə bərpa (header və ya `?lastEventId`), 25 saniyədən bir `: ping`,
  maksimum 5 dəq bağlantı ömrü (`X-Accel-Buffering: no`, bufer təmizlənməsi).
- **Hadisə yayım nöqtələri**: `HouseRepository::incrementView/incrementWaClick`
  → `owner_{id}` kanalına `view`/`wa_click`; `Register::submit()` →
  `admin` kanalına `new_owner`; `HouseEdit::submitForApproval()` → `admin`
  kanalına `house_pending`; `Approvals::approve/reject()` → `owner_{id}`
  kanalına `house_approved`/`house_rejected`; `PaymentRepository::applyApproval()`
  → HƏM `owner_{id}`, HƏM `admin` kanalına `payment_ok`.
- **SSE marşrutları**: sayt tərəfində `GET /sse` (`Site/SseStream.php`,
  owner auth-dan `owner_{Auth::id()}` kanalı), admin tərəfində `GET /sse`
  (`Admin/AdminSseStream.php`, `admin` kanalı). *Sinif adı toqquşmasından
  qaçmaq üçün əvvəlcədən `AdminSseStream` adlandırıldı (Faza 3-dəki
  Login/Dashboard dərsi nəzərə alınaraq).*
- **Owner panel canlı UI**: `stat-value` `data-stat` atributları ilə
  baxış/klik sayğacları SSE hadisəsi gəldikcə JS-də artırılır (səhifə
  yenilənmədən); `house_approved`/`house_rejected` status nişanını canlı
  yeniləyir; `payment_ok` toast göstərib 3 saniyə sonra səhifəni yeniləyir.
  Toast konteyner + 3-dilli mesaj şablonları (`owner.toast_*`).
- **Admin dashboard canlı lent**: yeni `public_admin/assets/admin.js` —
  `new_owner`/`house_pending`/`payment_ok` hadisələrini vaxt möhürü ilə
  siyahının başına əlavə edir, maks. 20 sətir saxlanılır.
- **`sw.js`** tam yenidən yazıldı (11.2): versiyalı cache-first app shell
  (css/js/offline.html/manifest/ikonlar), `activate`-də köhnə versiya
  keşlərinin silinməsi, ev fotoları (`/uploads/houses/...`) üçün
  stale-while-revalidate + 60 şəkillik LRU (`trimCache`), `/api/*`,
  `/sse`, `/odenis/callback` HEÇ VAXT keşlənmir, digər HTML naviqasiyaları
  network-first → uğursuz olduqda `offline.html`. `app.js`-ə SW qeydiyyatı
  əlavə olundu.
- **PWA ikonları** (`generate_icons.php`, GD ilə): real loqo olmadığı üçün
  dizayn palitrasına uyğun sadə "ev" siluetli `icon-192.png`, `icon-512.png`,
  `maskable-512.png` generasiya edildi (maskable variant təhlükəsiz zona
  daxilində kiçildilib).
- **Splash** (`app/views/shared/splash.php`, 6.1): "Birlikdə" fade-in →
  "Getdik" sürüşərək gəlir → dağ siluet SVG stroke animasiyası, cəmi
  ~1.9s. Inline HTML/CSS/JS (əlavə sorğu yoxdur), `sessionStorage` bayrağı
  ilə "sessiyada bir dəfə" məntiqi — bu, spec-in "PWA rejimində VƏ YA
  ilk açılışda" şərtini TƏK bir yoxlama ilə düzgün ödəyir, çünki
  `sessionStorage` PWA-nın hər soyuq başlanğıcında da təbii sıfırlanır
  (qeyd: bu, iki ayrı şərt kimi yox, vahid məntiq kimi icra edildi —
  aşağıda "qərar" bölməsində izah olunur). `prefers-reduced-motion` →
  animasiyasız, dərhal keçid.
- **Quraşdırma təklifi** (11.3): `app/views/shared/install_prompt.php` +
  `app.js`. Qonaq: 2-ci səhifə baxışından sonra (`sessionStorage` sayğacı)
  alt sheet; `beforeinstallprompt` tutulur, iOS-da (hadisə heç vaxt
  atəşlənmədiyi üçün) "Quraşdır" düyməsi təlimat mətninə keçir. Rədd →
  `localStorage`-da 7 gün bayrağı. Ev sahibi: qeydiyyatdan sonra
  (`/sahib/ev/yeni?xosgeldin=1` query parametri ilə) dərhal göstərilir.

### Qərar: splash-ın "PWA VƏ YA ilk açılış" şərti necə vahidləşdirildi

Spec mətni: "YALNIZ PWA rejimində... VƏ YA sessiyada ilk açılışda... hər
səhifə keçidində YOX". `sessionStorage` təbiətcə hər tam brauzer/PWA
sessiyasının başlanğıcında sıfırlanır (PWA soyuq başlanğıcı da daxil
olmaqla) və naviqasiyalar arasında saxlanılır. Buna görə TƏK bir
`sessionStorage` yoxlaması eyni anda: (a) adi brauzerdə "ilk açılış"-ı,
(b) PWA-da "hər launch"-ı düzgün tutur, (c) "hər səhifə keçidində yox"
qaydasını təmin edir — ayrıca `display-mode: standalone` şərtinə ehtiyac
qalmır. Bu, improvizasiya deyil, iki şərtin məntiqi ekvivalentliyinin
sadələşdirilməsidir; `PROGRESS.md`-də sənədləşdirilir ki, Faza 6-da
(vizual cila) kimsə fərq axtarmasın.

### Nə test olundu (canlı HTTP, iki dev server, PHP_CLI_SERVER_WORKERS=4)

1. **PWA aktivləri fetch olunur**: `manifest.webmanifest` (200, düzgün
   `application/manifest+json`, keçərli JSON), `sw.js` (200,
   `application/javascript`), 3 ikon (200), `offline.html` (200).
2. **Splash HTML/JS mövcudluğu**: ana səhifədə `#splash` markası və
   `sessionStorage.getItem('getdik_splash_shown')` məntiqi tapıldı.
3. **SSE — əsas qəbul kriteriyası, HƏQİQİ eyni-zamanlı bağlantı ilə**:
   owner 1 kimi giriş edilib, arxa fonda `curl -N` ilə `/sse`-yə uzunömürlü
   bağlantı açıldı; AYRI bir sorğu ilə ev səhifəsi baxışı tetiklendi →
   axında `event: view` **2 saniyə ərzində** göründü (tələb olunan 3
   saniyədən sürətli, poll intervalına uyğun). Eyni üsulla `wa_click`,
   admin tərəfindən `house_approved` (owner kanalına) və `new_owner`
   (admin kanalına) hadisələri də real vaxtda çatdırıldı və doğrulandı.
4. Owner panel HTML-də `data-house-id`, `stat-value[data-stat]`,
   `toast-container[data-sse-url]`, `install-sheet` elementlərinin hamısı
   mövcud olduğu təsdiqləndi.
5. Server loqlarında (bütün SSE test sessiyası boyu, ~15 sorğu) heç bir
   PHP warning/fatal qeydə alınmadı.
6. Bütün `.php` faylları `php -l`, bütün `.js` faylları `node --check`
   ilə təmiz.

### Məlum məhdudiyyətlər

- Lighthouse CLI bu sandbox mühitində (headless Chrome yoxdur) işə
  salınmadı — PWA installability meyarları (manifest sahələri, ikon
  ölçüləri, SW qeydiyyatı, HTTPS) kod baxışı ilə spec-ə uyğunlaşdırıldı,
  amma real Lighthouse balı ölçülmədi. Real domendə/brauzerdə
  `chrome://lighthouse` ilə yoxlanılması tövsiyə olunur.
- 25 saniyəlik SSE ping-i və 5 dəqiqəlik maksimum bağlantı ömrü kod
  baxışı ilə təsdiqləndi (məntiq düzgündür), tam 5+ dəqiqə gözləyib canlı
  test edilmədi (vaxt səmərəliliyi üçün).
- iOS Safari-də `beforeinstallprompt` davranışı real cihazda test
  edilmədi (kod UA-sniffing ilə fallback yolunu düzgün işlədir, amma
  Safari-nin xüsusi PWA davranışları simulyasiya ilə tam əhatə oluna
  bilməz).

### Növbəti addım — FAZA 6 (Cila)

Skeleton yükləmə vəziyyətləri, boş hallar üçün mesajlar (artıq "listing.empty"
kimi qismən var — Faza 6-da vizual cilası), xəta səhifələri (403/500 üçün
xüsusi dizayn, hazırda yalnız 404 var), SEO metalar (hər səhifə üçün unikal
title/description artıq var — schema.org `LodgingBusiness` JSON-LD ev
səhifəsində Faza 1-dən mövcuddur, amma digər struktur məlumatlar əlavə
oluna bilər) + tam `sitemap.xml` (əsas versiya Faza 4-də cron ilə hazırdır),
sürət optimallaşdırması (şəkil lazy-load, critical CSS), son dizayn keçidi
(qiymət filtri həqiqi slider-ə çevrilməsi kimi Faza 1-dən qalan
sadələşdirmələr), RU/EN validasiya mesajlarının tamamlanması (Faza 2-dən
qalan, hazırda yalnız AZ).

Yoxlama kriteriyaları (FAZA 6, bölmə 13.2): Lighthouse Performance ≥85
(mobil), SEO ≥95; bütün mətnlər 3 dildə; console-da xəta yoxdur.
