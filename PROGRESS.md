# BİRLİKDƏ — Progress Log

Cari status izləmə jurnalı. Hər faza bitəndə burada yenilənir.

## Status

- **Cari faza:** Faza 7 tamamlandı — bütün fazalar (0-7) bitdi

## Faza Cədvəli

| Faza | Ad | Status | Tarix |
|---|---|---|---|
| — | Yaddaş sistemi (CLAUDE.md/PROGRESS.md) qurulması | ✅ Tamamlandı | 2026-07-11 |
| 0 | Təməl (VPS, qovluq, Core, config, error handler) | ✅ Tamamlandı | 2026-07-11 |
| 1 | Verilənlər Bazası (migrations, seed) | ✅ Tamamlandı | 2026-07-11 |
| 2 | Autentifikasiya (qeydiyyat/giriş/sözləşmə/middleware) | ✅ Tamamlandı | 2026-07-11 |
| 3 | Sifariş və SSE (race qoruması, WhatsApp) | ✅ Tamamlandı | 2026-07-11 |
| 4 | Admin Paneli | ✅ Tamamlandı | 2026-07-11 |
| 5 | Abunə və Ödəniş (adapter, webhook) | ✅ Tamamlandı | 2026-07-11 |
| 6 | PWA və Cilalama (tam frontend daxil) | ✅ Tamamlandı | 2026-07-11 |
| 7 | Hüquqi (10 sənəd, QARALAMA statusu) | ✅ Tamamlandı | 2026-07-11 |

## Jurnalı

### 2026-07-11 — Yaddaş sistemi quruldu
- `Birlikde_Layihe_v2.docx` tam oxundu (11 bölmə + 2 əlavə).
- `CLAUDE.md` yaradıldı: layihə xülasəsi, texnologiya yığını, DB icmalı, əsas biznes
  qaydaları, təhlükəsizlik tələbləri, endpoint xəritəsi, faza planı, iş qaydaları.
- `PROGRESS.md` (bu fayl) yaradıldı.
- Hələ heç bir tətbiq kodu yazılmayıb. Növbəti addım: istifadəçi "Növbəti fazaya keç"
  deyəndə Faza 0-a başlanacaq.

### 2026-07-11 — Faza 0 tamamlandı (Təməl)
- Qovluq strukturu yaradıldı: `public/`, `app/{Core,Controllers,Models,Middleware,
  Services,Providers,Views}`, `config/`, `storage/{logs,cache,sessions,banners}`,
  `database/{migrations,seed}`, `cron/`, `routes/`, `deploy/nginx/`.
- Core sinifləri: `Env` (.env parser), `Database` (PDO singleton, `ERRMODE_EXCEPTION`,
  `EMULATE_PREPARES=false`), `Session` (httponly+secure+samesite, `writeClose()` SSE
  üçün), `Request`, `Response`, `Router` (parametr `{id}` dəstəyi + middleware zənciri),
  `MiddlewareInterface`, `ErrorHandler` (set_error_handler/set_exception_handler/
  shutdown), `Logger` (fayl-əsaslı, `storage/logs/`).
- `bootstrap.php` — PSR-4-bənzər avtoloader (`App\` → `app/`), `.env` yükləmə,
  ErrorHandler qeydiyyatı, timezone. Framework istifadə olunmadı (native PHP).
- `config/config.php` — mərkəzi konfiqurasiya massivi (app/db/session/csrf/
  remember_me/admin/payment/push/whatsapp).
- `.env.example` — bütün dəyişənlər placeholder dəyərlərlə (DB, session, ödəniş,
  VAPID, WhatsApp dəstək nömrəsi).
- `public/index.php` (app.birlikde.biz) və `public/admin.php`
  (appadmin.birlikde.biz) — ayrı front controller-lər, ayrı `routes/web.php` və
  `routes/admin.php`.
- `deploy/nginx/app.birlikde.biz.conf` və `deploy/nginx/appadmin.birlikde.biz.conf`
  — SSE üçün `fastcgi_buffering off`/`proxy_buffering off`/uzun timeout, `.env` və
  daxili qovluqlara (`app/`, `config/`, `database/`, `storage/`, `routes/`) giriş
  bloklanıb. `deploy/VPS_QURULUM.md` — addım-addım VPS qurulum siyahısı.
- **Yoxlama:** bütün `.php` faylları `php -l` ilə sintaksis xətasız təsdiqləndi;
  PHP built-in server ilə hər iki front controller (`index.php`, `admin.php`)
  funksional test edildi — `/` və `/healthz` marşrutları düzgün JSON qaytardı.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 1 (Verilənlər Bazası)
  başlanacaq.

### 2026-07-11 — Faza 1 tamamlandı (Verilənlər Bazası)
- 14 migration faylı yaradıldı (`database/migrations/001-014`) — FK asılılıq sırası ilə:
  sehirler → rayonlar → users → sessiyalar → kuryeler → yukdasima_olculeri →
  dasiyici_olculeri → kurye_bolgeler → sifarisler → abunelikler → odenisler →
  bannerler → ayarlar → legal_logs. Bütün cədvəllər orijinal sənədin DDL-lərinə
  (bölmə 4.2-4.9) sadiq qalaraq yazıldı; InnoDB + utf8mb4_unicode_ci.
  Qeyd: `odenisler` cədvəlinə orijinal DDL-də olmayan, lakin 4.1 əlaqələr cədvəlində
  bəyan edilmiş `fk_od_abunelik`/`fk_od_kurye` FK-ları əlavə edildi (referential
  integrity üçün, sxemin qalan hissəsi ilə tutarlı).
- 4 seed faylı: `sehirler` (Bakı+Sumqayıt), `rayonlar` (Bakı 12 rayon + Sumqayıt 18
  mikrorayon + 4 qəsəbə + 2 massiv = 24 ərazi), `yukdasima_olculeri` (XS-Mega, 7
  kateqoriya), `ayarlar` (`abune_rejimi=aktiv` default).
- `database/migrate.php` və `database/seed.php` — CLI runner-lər, `schema_migrations`/
  `schema_seeds` tracking cədvəlləri ilə idempotent (təkrar işə salmaq təhlükəsizdir).
- **Yoxlama (real MySQL-ə qarşı, sadəcə sintaksis yox):** sandbox-da MariaDB 10.11
  quraşdırılıb işə salındı, `birlikde_test` DB yaradıldı, `php database/migrate.php`
  və `php database/seed.php` UĞURLA icra edildi. Təsdiqləndi:
  - 14 cədvəl də InnoDB + utf8mb4_unicode_ci yaradıldı, 12 FK constraint aktivdir.
  - Runner-lərin idempotentliyi: təkrar işə salınanda bütün fayllar `SKIP` edildi.
  - Seed data düzgündür (Bakı 12 + Sumqayıt 24 ərazi, 7 yükdaşıma ölçüsü, ayarlar).
  - **Race qoruması (bölmə 6.3) real DB-də sınandı:** iki kuryer eyni sifarişi
    götürməyə çalışanda atomic UPDATE ilə yalnız BİRİNCİSİ uğur qazandı
    (`rowCount()=1`), ikincisi `rowCount()=0` aldı — spesifikasiyaya tam uyğun.
  - Test DB/istifadəçi və `.env` təmizləndi, repoya heç bir sirr commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 2 (Autentifikasiya)
  başlanacaq.

### 2026-07-11 — Faza 2 tamamlandı (Autentifikasiya)
- **Core:** `Csrf` (sessiya-bağlı token, `hash_equals`), `RateLimiter` (fayl-əsaslı,
  `storage/cache/ratelimit/`), `ValidationException`.
- **Middleware:** `CsrfGuard` (POST/PUT/DELETE-də token yoxlanışı, uğursuzda 419),
  `RateLimit` (5 cəhd/15 dəq, IP+yol əsaslı, 429), `Auth` (sessiya yoxdursa
  remember-me cookie ilə avtomatik bərpa cəhdi, uğursuzda 401), `RoleGuard`
  (abstract) + `MusteriGuard`/`KuryeGuard` (Faza 3-də sifariş marşrutlarında
  istifadə olunacaq).
- **Models:** `User`, `Kurye`, `DasiyiciOlcusu` (yükdaşıma ölçü N—N əlaqəsi),
  `Sessiya` (remember-me token CRUD), `LegalLog` (append-only audit yazma).
- **Service:** `AuthService::qeydiyyat()` (3 rol: musteri/kurye/yukdasima, rola görə
  şərti validasiya — kuryedə nəqliyyat növü, yükdaşımada ölçü seçimi mütləqdir;
  sözləşmə checkbox MƏCBURİ; unikal telefon yoxlanışı; `password_hash`),
  `AuthService::giris()` (`password_verify`, `session_regenerate_id`, bloklu
  hesab rədd edilir, "məni xatırla" seçilibsə remember-me tokeni yaradılır),
  `AuthService::cixis()` (sessiya destroy + remember-me tokeninin DB-dən silinməsi).
- **Controller:** `AuthController` — `POST /qeydiyyat`, `POST /giris`, `POST /cixis`;
  `GET /csrf-token` (frontend üçün token təchizatı) `routes/web.php`-ə əlavə olundu.
- `config/sozlesme.php` — İstifadəçi Sözləşməsi YER TUTUCU mətni (AZ/RU/EN); hüquqi
  mətn hazır olanda (Faza 7) yalnız bu fayl yenilənəcək, DB strukturu dəyişmir.
- **Yoxlama (real MySQL + real HTTP server-ə qarşı, sadəcə sintaksis yox):**
  - Bütün `.php` faylları `php -l` ilə xətasız.
  - PHP built-in server (`public/index.php`) + MariaDB test DB ilə tam axın sınandı:
    3 rolun qeydiyyatı (musteri/kurye/yukdasima — ölçü seçimi ilə), sözləşmə
    checkbox olmadan rədd, dublikat telefon rədd, CSRF-siz sorğu 419, yanlış
    parolla giriş 401, düzgün giriş + "məni xatırla" ilə remember-me cookie
    yaradıldı, sessiya cookie-si silinərkən YALNIZ remember-me cookie ilə
    `Auth` middleware sessiyanı avtomatik bərpa etdi (7.3.1-ə tam uyğun),
    çıxışda remember-me cookie və DB tokeni silindi, rate-limit 5 cəhddən sonra
    429 qaytardı.
  - DB-də son vəziyyət yoxlanıldı: `users`/`kuryeler`/`dasiyici_olculeri` düzgün
    əlaqələndirilib, `legal_logs`-da qeydiyyat+giriş hadisələri düzgün yazılıb,
    çıxışdan sonra `sessiyalar` cədvəli təmizlənib.
  - Test DB/istifadəçi və `.env` təmizləndi, repoya heç bir sirr commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 3 (Sifariş və SSE)
  başlanacaq.

### 2026-07-11 — Faza 3 tamamlandı (Sifariş və SSE)
- **Kiçik refaktor (Faza 2 üzərində):** `YukdasimaOlcusu` modeli əlavə olundu
  (ölçü kataloqu sorğuları), `DasiyiciOlcusu` isə yalnız kuryerin seçdiyi ölçülərin
  N—N əlaqəsinə fokuslandı (`attachSizes`, `olcuIdsByKurye`). `AuthService`
  müvafiq olaraq yeniləndi, Faza 2 auth axını yenidən test edilib reqressiya yoxdur.
- **Models:** `Sifaris` (create/find/tarixçə/SSE sorğuları/atomic götürmə-tamamlama-
  ləğv/cron passivləşdirmə), `Rayon` (aktiv şəhər/rayon yoxlaması), `KuryeBolge`
  (ərazi N—N sinxronizasiyası, tranzaksiyalı), `Kurye`-yə `findById`/`setOnlayn`/
  `incrementTamamlanan` əlavə olundu.
  Qeyd: `odenisler`-ə əlavə edilmiş FK-lara bənzər şəkildə, bu modellər orijinal
  DDL-ə sadiq qalır, əlavə sxem dəyişikliyi edilməyib.
- **Core:** `WhatsApp::link()` — bölmə 6.4-ə uyğun ön-doldurulmuş `wa.me` linki.
- **Service:** `SifarisService` — sifariş yaratma (tip/şəhər-rayon/ölçü validasiyası),
  ləğv (mülkiyyət yoxlaması ilə), tarixçə, SSE lövhə sorğusu (onlayn+ərazi+tip/ölçü
  filtri — bölmə 5.3, 7.1.1), atomic götürmə + WhatsApp link + legal_logs yazma,
  tamamlama (+`tamamlanan` sayğacı), onlayn/offline, ərazi yeniləmə, cron
  passivləşdirmə.
  **Diqqət (əlavə təhlükəsizlik qərarı):** `gotur()`-da SSE lövhə filtri ilə YANAŞI
  server-tərəfində də tip/ölçü uyğunluğu yoxlanılır (bölmə 7.1.1 "uyğun daşıyıcı"
  qaydası) — əks halda dəyişdirilmiş client sorğusu ilə uyğunsuz sifariş götürülə
  bilərdi. Bu, orijinal sənəddə açıq deyilən amma nəzərdə tutulan qoruma kimi əlavə
  olundu.
- **Controllers:** `SifarisController` (yarat/legv/tarixçə/gotur/tamamla),
  `KuryeController` (onlayn, bölgələr), `SseController` (canlı lövhə, `session_write_close()`,
  `Last-Event-ID` dəstəyi, `nginx fastcgi_read_timeout`-a uyğun 1 saatlıq təhlükəsizlik həddi).
  Qeyd: `POST /kurye/bolgeler` Əlavə A cədvəlində yoxdur, lakin rayon filtrini işlək
  etmək üçün zəruri olduğundan Faza 3 çərçivəsində əlavə edildi (istifadəçiyə bildirilir).
- `cron/sifaris_temizle.php` — 1 saatdan çox `axtarisda` qalan sifarişləri `passiv`
  edir (bölmə 6.1/6.5).
- `routes/web.php`-ə əlavə olundu: `POST /sifaris/yarat`, `POST /sifaris/{id}/legv`,
  `GET /sifaris/tarixce`, `GET /sse/lovhe`, `POST /sifaris/{id}/gotur`,
  `POST /sifaris/{id}/tamamla`, `POST /kurye/onlayn`, `POST /kurye/bolgeler`.
- **Yoxlama (real MySQL + real HTTP server-ə qarşı, sadəcə sintaksis yox):**
  - Bütün `.php` faylları `php -l` ilə xətasız.
  - PHP built-in server-i çox-worker rejimində (`PHP_CLI_SERVER_WORKERS=4`) işə
    salıb tam ssenari sınandı: 3 istifadəçinin qeydiyyatı/girişi, kuryerlərin ərazi
    seçimi + onlayn olması, iki fərqli tipli (kurye/yukdasima) sifarişin yaradılması.
  - **SSE canlı lövhə:** hər kuryer YALNIZ öz tipinə/ölçüsünə/ərazisinə uyğun
    sifarişi gördü (kurye→tip=kurye, yukdasima→tip=yukdasima+uyğun ölçü);
    bağlantı client tərəfdən kəsiləndə (`connection_aborted()`) dövr düzgün
    dayandı.
  - **Uyğunsuz götürmə cəhdi** (avtomobil kuryeri yükdaşıma sifarişini götürməyə
    çalışdı) → 409 "xidmət tipinizə uyğun deyil" ilə rədd edildi.
  - **Race/uyğun götürmə:** hər kuryer öz uyğun sifarişini uğurla götürdü,
    WhatsApp linkləri (`wa.me/...?text=...`) düzgün generasiya olundu.
  - **Tamamlama:** status `tamamlandi`-ya keçdi, `kuryeler.tamamlanan` 0→1 artdı.
  - **IDOR qorumaları:** başqa rol (yukdasima) müştəri sifarişini ləğv etməyə
    çalışanda `MusteriGuard` 403 qaytardı; eyni rollu (musteri) başqa istifadəçinin
    sifarişini ləğv etməyə çalışanda mülkiyyət yoxlaması 422 ilə rədd etdi.
  - **Cron:** `sifaris_temizle.php` əvvəlcə 0 sifariş tapdı; `created_at` süni
    olaraq 2 saat geri çəkiləndə həmin sifarişi düzgün `passiv` etdi.
  - `legal_logs`-da `sifaris_goturuldu` hadisələri düzgün `actor_id`/`sifaris_id`
    ilə yazıldı.
  - Test DB/istifadəçi, `.env` və rate-limit keşi təmizləndi, repoya heç bir sirr
    commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 4 (Admin Paneli)
  başlanacaq.

### 2026-07-11 — Faza 4 tamamlandı (Admin Paneli)
- **Əhatə qərarı (istifadəçiyə soruşulub təsdiqləndi):** bölmə 8.1 (Dashboard) və
  8.7 (Ərazi İdarəsi) Faza 4 roadmap bəndlərində açıq sadalanmadığı üçün bu
  fazaya DAXİL EDİLMƏDİ — yalnız roadmap-da yazılan 4 bənd (admin auth,
  müştəri/kuryer+bloklama, sifariş idarəsi, abunə+banner) tətbiq olundu. İstəsə
  sonra ayrıca əlavə edilə bilər.
- **DB:** `database/migrations/015_create_adminler_table.sql` — ayrı, izolyasiya
  olunmuş `adminler` cədvəli (users-dən tamam ayrı, bölmə 8.8).
  `database/create_admin.php` — CLI provisioning skripti (parol terminalda gizli
  daxil edilir, heç vaxt arqument/koda yazılmır; web-dən admin qeydiyyatı QƏSDƏN
  yoxdur).
- **Sessiya izolyasiyası:** `Session::start()` indi opsional ad qəbul edir;
  `public/admin.php` `ADMIN_SESSION_NAME` (ayrı cookie adı) ötürür — admin və
  müştəri/kuryer sessiyaları tamamilə ayrı cookie/storage-dadır (sınaqla
  təsdiqləndi: müştəri sessiyası ilə admin marşrutuna cəhd 401 verdi).
  `AdminAuth` middleware qısa idle-timeout tətbiq edir (`ADMIN_SESSION_IDLE_MINUTES`,
  default 15 dəq) — sessiya kalıcı DEYİL, remember-me YOXDUR (bölmə 8.8-ə tam uyğun).
- **Models:** `Admin`, `Abunelik`, `Ayar`, `Banner`; `User`-ə admin siyahı/detal/
  bloklama metodları, `Sifaris`-ə admin siyahı/filtr/detal (JOIN ilə müştəri+kuryer
  məlumatı), `LegalLog`-a `bySifarisId()` (sifariş "tarixçəsi" — yeni cədvəl əvəzinə
  mövcud audit jurnalından istifadə edildi), `DasiyiciOlcusu`-na ölçü kod siyahısı.
  **Diqqət:** `Database` `EMULATE_PREPARES=false` istifadə etdiyi üçün eyni adlı
  named placeholder-in bir sorğuda TƏKRAR istifadəsi native MySQL prepare-də
  uğursuz olur — axtarış sorğusunda (`ad/soyad/telefon LIKE`) bu aşkarlanıb
  düzəldildi (hər occurrence üçün ayrı placeholder adı). Bütün Models/Services
  bu baxımdan yoxlanıldı, başqa yer tapılmadı.
- **Services:** `AdminAuthService` (giriş/çıxış/parol dəyiş), `AdminUserService`
  (müştəri/kuryer tab siyahısı axtarışla+pagination, pop-up detal, blokla/
  blokdan-çıxar — səbəb MƏCBURİ), `AdminSifarisService` (filtr: status/rayon/
  tarix aralığı + pagination, detal+tarixçə), `AbunelikService` (fərdi: +gün,
  tip dəyiş, dayandır/aktivləşdir; qlobal: `abune_rejimi` toggle; status label
  hesablama: aktiv/pulsuz/bitib/bloklu/pulsuz_qlobal — qlobal dayandırma bütün
  fərdi vəziyyətləri üstələyir, bölmə 8.5.1-ə uyğun), `BannerService` (yükləmə
  validasiyası: mime whitelist + `getimagesize` + 5MB limit + təsadüfi fayl adı,
  tarix/hədəf/link validasiyası).
- **Controllers:** `AdminAuthController`, `AdminUserController`,
  `AdminSifarisController`, `AdminAbunelikController`, `AdminBannerController`,
  `BannerImageController` (banner şəklini `storage/banners/`-dan təhlükəsiz
  yayımlayır — fayl adı sərt regex (`^[a-f0-9]{32}\.(jpg|jpeg|png|webp)$`) ilə
  yoxlanılır, path traversal sınandı və rədd edildi).
  **Diqqət (Əlavə A-da yoxdur, amma zəruri):** `GET /banner-sekil/{fayl}`
  (`routes/web.php`, ictimai) — banner şəkli `storage/` altında saxlanır və nginx
  bu qovluğu bloklayır (Faza 0 konfiqi), ona görə kiçik bir PHP stream endpoint-i
  əlavə olundu ki, "banner sistemi" bəndi əvvəldən-axıra (yüklə → göstər) işlək olsun.
- `routes/admin.php` tam yazıldı: giriş/çıxış/parol-dəyiş, müştərilər/kuryerlər/
  istifadəçi-detal/blokla/blokdan-çıxar, sifarişlər/sifaris-detal, kurye-abunəlik
  (status/uzat/tip/aktivlik) + qlobal abunə-rejimi, banner/bannerlər/banner-aktivlik.
- **Yoxlama (real MySQL + real HTTP server-ə qarşı, sadəcə sintaksis yox):**
  - Bütün `.php` faylları `php -l` ilə xətasız, migration 015 real MySQL-ə tətbiq
    olundu.
  - Admin CLI ilə yaradıldı, giriş/yanlış-parol/çıxış/parol-dəyişmə (yanlış cari
    parol rədd, düzgün dəyişiklikdən sonra yeni parolla giriş) sınandı.
  - **Sessiya izolyasiyası:** müştəri sessiyası ilə admin marşrutuna cəhd 401.
  - **Idle-timeout:** `ADMIN_SESSION_IDLE_MINUTES=0` ilə məcburi test edildi —
    girişdən dərhal sonra qorunan marşrut 401 verdi (mexanizm işləyir); normal
    (15 dəq) rejimdə həmin marşrut dərhal sonra 200 verdi.
  - Müştəri/kuryer siyahıları, axtarış, pop-up detal (kuryerin nəqliyyat/onlayn/
    tamamlanan sahələri düzgün JOIN edildi) sınandı.
  - **Bloklama:** səbəbsiz blokla → 422; səbəblə blokla → uğurlu, bloklanan
    istifadəçi girişdə "Hesabınız bloklanıb" aldı; blokdan çıxarma bərpa etdi.
  - **Sifariş idarəsi:** status filtri (yanlış status → 422), JOIN edilmiş
    müştəri/kuryer məlumatı, boş `tarixce` (hələ götürülməmiş sifariş üçün) düzgün.
  - **Abunə:** heç bir dövr yoxdursa "bitib"; +30 gün → "aktiv" + düzgün qalan
    gün; tip→pulsuz → label "pulsuz"; qlobal "dayandirilib" → bütün fərdi
    vəziyyətləri üstələyib "pulsuz_qlobal" göstərdi; qlobal geri aktivləşdirmə
    normala qaytardı.
  - **Banner:** real PNG (1080×300) yükləndi, DB-yə yol yazıldı, `GET
    /banner-sekil/{fayl}` ilə düzgün Content-Type (`image/png`) ilə geri
    qaytarıldı; path traversal (`../../.env`) və hash-formatına uyğun olmayan
    fayl adları 404 ilə rədd edildi.
  - `legal_logs`-da bütün admin əməliyyatları (giriş, blokla/blokdan-çıxar,
    abunəlik dəyişiklikləri, qlobal rejim, parol dəyiş) səbəblə birlikdə düzgün
    yazıldı.
  - Test DB/istifadəçi, `.env`, yüklənmiş test banner şəkli və rate-limit keşi
    təmizləndi, repoya heç bir sirr commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 5 (Abunə və
  Ödəniş) başlanacaq.

### 2026-07-11 — Faza 4-ün əlavəsi: bölmə 8.1 (Dashboard) və 8.7 (Ərazi İdarəsi)
İstifadəçinin açıq tapşırığı ilə əvvəllər roadmap-a görə təxirə salınan bu iki
admin bölməsi əlavə olundu.

- **Models:** `Sehir` (yeni — şəhər CRUD), `Rayon`-a admin CRUD metodları
  (`create`, `listBySehir`, `setAktiv`, `isUsedInSifarisler`,
  `isUsedInKuryeBolgeler`, `delete`), `User`/`Kurye`/`Sifaris`/`Banner`-ə
  Dashboard sayğac metodları (`countByStatus`, `countOnlayn`, `countAktiv`),
  `LegalLog`-a `sonuncular()` (son N hadisə).
- **Servislər:** `AdminDashboardService` (sayğaclar: müştəri/kuryer/onlayn-kuryer/
  bloklu-istifadəçi sayları, sifariş statuslarına görə say, aktiv banner sayı;
  son 20 audit hadisəsi), `AdminEraziService` (şəhər yarat/siyahı, rayon yarat/
  siyahı/aktivlik-dəyiş/sil — silmə YALNIZ heç bir sifarişdə VƏ heç bir kuryerin
  seçdiyi bölgələrdə istifadə olunmadıqda icazəlidir, əks halda "deaktiv edin"
  mesajı ilə rədd edilir — bölmə 8.7-yə tam uyğun).
- **Controllers:** `AdminDashboardController` (`GET /admin/dashboard`),
  `AdminEraziController` (`GET /admin/sehirler`, `POST /admin/sehir`,
  `GET /admin/sehir/{sehirId}/rayonlar`, `POST /admin/sehir/{sehirId}/rayon`,
  `POST /admin/rayon/{id}/aktivlik`, `POST /admin/rayon/{id}/sil`).
- Bütün yazma marşrutları `AdminAuth` + `CsrfGuard` ilə qorunur, hər dəyişiklik
  (şəhər/rayon yaratma, aktivlik dəyişmə, silmə) səbəb sahəsi olmadan da
  `legal_logs`-a yazılır (bölmə 8.7 üçün doc səbəb tələb etmir, yalnız 8.5
  abunə dəyişiklikləri üçün "səbəb" MƏCBURİ idi — fərq qorunub).
- **Yoxlama (real MySQL + real HTTP server-ə qarşı):**
  - `php -l` bütün fayllarda təmiz (UTF-8 metod adı `sayğaclar()` daxil olmaqla).
  - Dashboard: boş vəziyyətdə bütün sayğaclar 0 idi; müştəri/kuryer qeydiyyatı
    və sifariş yaradıldıqdan sonra sayğaclar dəqiq yeniləndi (2 müştəri, 2
    kuryer, 1 axtarışda sifariş — real DB vəziyyəti ilə tam uyğun); son
    hadisələr siyahısı düzgün sırada gəldi.
  - Ərazi İdarəsi: yeni şəhər (Gəncə) yaradıldı, dublikat ad rədd edildi (422);
    yeni rayon yaradıldı, siyahıda göründü; mövcud olmayan şəhər üçün 404.
  - **Referential integrity (əsas tələb):** rayon əvvəlcə sifarişdə, sonra isə
    ayrıca kuryerin seçdiyi bölgədə istifadə edilərək HƏR İKİ HALDA silmə
    cəhdi 422 ilə rədd edildi ("deaktiv edin" mesajı ilə); eyni rayonun
    deaktiv edilməsi isə (silmək əvəzinə) uğurla işlədi.
  - Test zamanı ilk cəhddə app server (8104) təsadüfən söndürülmüş vəziyyətdə
    qaldığından test məlumatları yaranmadı (səhv aşkarlandı, server yenidən
    başladıldı, test təkrarlanıb düzgün nəticə alındı) — kodda problem yox idi.
  - Test DB/istifadəçi, `.env` və rate-limit keşi təmizləndi, repoya heç bir
    sirr commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 5 (Abunə və
  Ödəniş) başlanacaq.

### 2026-07-11 — Faza 5 tamamlandı (Abunə və Ödəniş)
- **Mühüm əlaqələndirmə (Faza 3-ün geriyə doldurulması):** Faza 3 SSE lövhə/götürmə
  yazılanda abunə sistemi hələ mövcud deyildi, ona görə "aktiv abunə olmalı, abunə
  bitibsə lövhə bağlıdır" (bölmə 7.2.1) tətbiq edilməmişdi. İndi `SifarisService`-ə
  `AbunelikService` inject edildi və `kuryeAbunesiAktivdirmi()` yoxlaması həm
  `lovheYenileri()` (boş lövhə), həm də `gotur()`-a (409 rədd) əlavə olundu —
  test edilib: abunəsiz kuryerin lövhəsi boş idi, ödənişdən sonra dərhal açıldı.
- **Providers (`app/Providers/`):** `PaymentProvider` interfeysi (`baslat`,
  `callbackDogrula` — bax Əlavə B "9.1 Payment adapterini Birbank üçün tətbiq et"),
  `PaymentSession`/`PaymentResult` DTO-ları, `BirbankProvider`/`PayriffProvider`
  (strukturca eyni: imzalanmış yönləndirmə linki + HMAC-SHA256 webhook doğrulama),
  `PaymentProviderFactory` (config-ə görə seçim).
  **QEYD:** Birbank/Payriff-in real API sənədləşməsi mövcud deyil (roadmap:
  "sonra qoşulur") — sahə adları/URL YER TUTUCUDUR, amma strukturu real hosted-
  checkout provayderlərinin ümumi nümunəsinə (imzalı redirect + imzalı webhook)
  uyğundur və `baslat()`/`callbackDogrula()` şəbəkəyə çıxmadan (pure) işləyir —
  ona görə sandboxda tam test edilə bildi (əsl bank credential-ı lazım deyil).
- **Model/Servis:** `Odenis` (create/findByOrderId/findPendingByKurye/updateStatus),
  `AbunelikService::odenisIleUzat()` (sistem-tərəfli, admin ID/səbəb tələb etmir,
  aktoru ödəyən kuryerin öz istifadəçi ID-si), `OdenisService` (`basla()` — eyni anda
  yalnız bir gözləyən ödəniş, qiymət `ayarlar.abune_qiymeti`-dən; `webhookIsle()` —
  imza etibarsızdırsa DB-yə TOXUNMUR, order_id tapılmırsa rədd, artıq emal
  olunubsa (idempotent) sakitcə çıxır).
  `Abunelik`-ə `bitmisAmmaAktivOlanlar()`/`tezliklaBitecekler()` (window function
  ilə hər kuryerin YALNIZ son dövrü), `LegalLog`-a `buGunXeberdarEdilenKuryeIdler()`
  (cron təkrar-xəbərdarlıq qarşısı).
- **Controller/Marşrut:** `OdenisController` — `POST /odenis/basla` (Auth+KuryeGuard+
  CsrfGuard+RateLimit), `POST /webhook/odenis` (yalnız RateLimit — xarici çağırış,
  Auth/CSRF tətbiq edilmir, imza doğrulaması bunun əvəzinə işləyir).
- `cron/abunelik_yoxla.php` — bitmiş-amma-aktiv dövrləri bağlayır (`aktiv=0`),
  3 gün qalan dövrlərə `abunelik_xeberdarlig` yazır (gündə bir kuryerə bir dəfə).
  QEYD: faktiki Web Push göndərilməsi Faza 6-da (VAPID/push_abuneler) əlavə
  olunacaq — bu cron indi yalnız audit jurnalına yazır, mənbə hazırdır.
- `database/seed/005_ayarlar_abune_qiymeti.sql` — yeni seed faylı (mövcud
  `004_ayarlar.sql`-ı DƏYİŞMƏDƏN, çünki artıq tətbiq olunmuş seed faylları
  təkrar işə düşmür — yeni sətir üçün yeni fayl lazımdır).
- `.env.example`-ə `BIRBANK_PAYMENT_URL`/`PAYRIFF_PAYMENT_URL` əlavə olundu.
- **Yoxlama (real MySQL + real HTTP server-ə qarşı):**
  - `php -l` bütün fayllarda təmiz; yeni seed real DB-yə tətbiq edildi, idempotentlik
    yoxlanıldı (təkrar işə salındıqda bütün fayllar, 005 daxil, SKIP oldu).
  - **Tam ödəniş axını:** abunəsiz kuryerin lövhəsi boş; `/odenis/basla` düzgün
    struktur + HMAC imza ilə redirect URL qaytardı (imza müstəqil hesablanıb
    təsdiqləndi); yanlış imzalı webhook 400 ilə rədd edildi VƏ DB dəyişmədi;
    düzgün imzalı webhook uğurla emal olundu, `abunelikler` sətri yaradıldı
    (30 gün, tip=pullu); EYNİ webhook TEKRAR göndərildi — status və abunə
    dəyişmədi (tam idempotent); ödənişdən sonra kuryerin lövhəsi dərhal açıldı
    və uyğun sifarişi göstərdi.
  - Naməlum `order_id` ilə (düzgün formatlı, lakin mövcud olmayan) webhook 400
    ilə rədd edildi; uğursuz ödəniş ssenarisi (`status=ugursuz`) düzgün emal
    olundu (`odenisler.status='ugursuz'`).
  - **Eyni-anda tək gözləyən ödəniş:** ikinci `/odenis/basla` cəhdi aktiv
    gözləyən ödəniş varkən 422 ilə rədd edildi.
  - **Cron:** süni aşağı salınmış (`bitme` keçmişdə) abunə bağlandı
    (`aktiv=0`, audit yazıldı); 2 gün qalan abunəyə xəbərdarlıq yazıldı; EYNİ
    GÜN ikinci dəfə işə salınanda 0 bağlama/0 xəbərdarlıq (dublikat qarşısı
    alındı, `legal_logs`-da cəmi 1 xəbərdarlıq qaldı).
  - `legal_logs` audit yazıları (`odenis_ugurlu`, `abunelik_odenisle_uzadildi`,
    `abunelik_bagladi`, `abunelik_xeberdarlig`) düzgün, dublikatsız yazıldı.
  - Test DB/istifadəçi, `.env` və rate-limit keşi təmizləndi, repoya heç bir
    sirr commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 6 (PWA və
  Cilalama) başlanacaq.

### 2026-07-11 — Faza 6 tamamlandı (PWA və Cilalama)
- **Əhatə qərarı (istifadəçi ilə əvvəlcədən razılaşdırıldı):** (1) tam frontend
  (həqiqi HTML görünüşlər) bu fazaya daxil edildi, sadəcə PWA/backend infrastrukturu
  ilə kifayətlənilmədi; (2) Web Push üçün RFC 8291 şifrələnmiş göndərmə sıfırdan
  YOXDUR — yalnız infrastruktur (VAPID açar idarəsi, abunəlik saxlama, hadisə
  trigger nöqtələri) hazırlandı, faktiki göndərmə `PushService::gonder()`-də
  YER TUTUCUDUR (storage/logs-a yazır, şəbəkəyə çıxmır).
- **Düzəliş (Faza 5 üzərində geriyə doldurma):** `SifarisService::lovheYenileri()`
  və `gotur()`-a "aktiv abunə tələb olunur" yoxlaması (bölmə 7.2.1) əlavə edildi —
  Faza 3 yazılanda abunə sistemi hələ yox idi, indi tam əlaqələndirildi.
- **Yeni tapılan/düzəldilən boşluq:** `push_abuneler` cədvəli əvvəlki fazalarda
  "artıq var" kimi qeyd edilmişdi, amma HEÇ VAXT yaradılmamışdı — bu fazada
  `database/migrations/016_create_push_abuneler_table.sql` ilə düzəldildi.
- **Core:** `Lang` (i18n resolver: `?dil=` → sessiya → istifadəçi profili → `az`
  default), `View` (sadə PHP-include şablon renderer, `Response::view()` üzərindən).
- **i18n:** `public/assets/lang/{az,ru,en}.json` — 100 tərcümə açarı, hər 3 dildə
  tam paritetli (proqramla yoxlanıldı). Brauzerdə real dil dəyişikliyi test edildi.
- **PWA:** `public/manifest.json`, `public/sw.js` (app-shell cache-first, YALNIZ
  `/assets/*` və `manifest.json` — HTML/JSON API QƏSDƏN toxunulmur, bax aşağıdakı
  bug), `public/assets/icons/icon-{192,512}.png` (GD ilə generasiya edilmiş, OLED
  qara + "B" loqo placeholder).
- **Dizayn:** `public/assets/css/app.css` (bölmə 10.1-in `.glass` bloku EYNİLƏ
  götürülüb), `public/assets/css/admin.css` (eyni dil, masaüstü-yönümlü).
- **Client JS:** `public/assets/js/app.js` (CSRF-aware fetch wrapper, install-
  prompt/iOS aşkarlama, Web Push abunə axını, dil dəyişdirici, XSS-təhlükəsiz
  `escapeHtml`), `public/assets/js/admin.js` (admin üçün analoji).
- **Views (`app/Views/`):** `partials/head.php`+`foot.php` (müştəri/kuryer shell,
  install-prompt overlay), `auth/giris.php`+`qeydiyyat.php` (3-rol tab formu),
  `musteri/panel.php` (sifariş yaratma+tarixçə+aktiv sifariş kartı), `kurye/lovhe.php`
  (SSE-əsaslı canlı lövhə + "Mənim işim"), `kurye/profil.php` (ərazi seçimi, abunə
  statusu+Ödə düyməsi, push icazə düyməsi); `admin/partials/login_head+foot.php`,
  `admin/partials/shell_head+foot.php` (sidebar naviqasiya), `admin/giris.php`,
  `admin/dashboard.php`, `admin/musteriler.php`, `admin/kuryerler.php` (abunəlik
  idarəsi pop-up daxil), `admin/sifarisler.php` (filtr+tarixçə), `admin/bannerler.php`
  (real fayl yükləmə forması), `admin/erazi.php` (şəhər/rayon CRUD).
- **Kiçik boşluq-doldurma endpoint-ləri (Əlavə A-da yoxdur, frontend üçün zəruri):**
  `GET /sehirler`, `GET /sehir/{id}/rayonlar` (ictimai, yalnız aktiv ərazilər —
  admin-in tam siyahısından fərqli), `GET /kurye/bolgeler` (özünə-xidmət oxu),
  `GET /kurye/abunelik`, `GET /kurye/profilim`, `GET /kurye/aktiv-isler` ("Mənim
  işim" — səhifə yenilənəndə SSE-nin görmədiyi köhnə götürmələri bərpa edir).
- **Web Push:** `PushAbune` modeli, `PushController` (`POST /push/abune`,
  `POST /push/legv`), `PushService::gonder()` (3 nümunə trigger nöqtəsinə calışdırıldı:
  `gotur()` → müştəriyə "daşıyıcı tapıldı", ödəniş uğuru → kuryeyə "abunə yeniləndi",
  cron xəbərdarlığı → kuryeyə "abunə bitir"; "yeni sifariş" fan-out bildirişi
  QƏSDƏN YOXDUR — SSE artıq bunu real-vaxtda edir, fan-out sorğusu əlavə mürəkkəblik
  qatardı), `database/generate_vapid_keys.php` (native OpenSSL EC P-256 açar
  cütü, real VAPID formatına uyğunluğu proqramla yoxlanıldı: 65 bayt uncompressed
  point, `0x04` prefiksi).
- **Dev-server düzəlişi:** `public/index.php`/`admin.php`-ə `PHP_SAPI==='cli-server'`
  ilə şərtlənmiş statik fayl keçid məntiqi əlavə edildi (yalnız `php -S` üçün,
  production-da nginx artıq bunu edir) — router skripti ilə işə salınan PHP
  built-in server avtomatik statik fayl xidmətini söndürür.
- **Yoxlama (real MySQL + real HTTP + HƏQİQİ BRAUZER — Playwright/Chromium):**
  - `php -l` bütün fayllarda təmiz; migration 016 real MySQL-ə tətbiq edildi.
  - Bütün statik fayllar (manifest/sw.js/ikonlar/css/js/dil JSON-ları) düzgün
    Content-Type ilə 200 qaytardı; bütün 14 səhifə marşrutu (7 müştəri/kuryer +
    7 admin) PHP xətasız render oldu (server logları YOXDUR yoxlanıldı).
  - **2 HƏQİQİ BUG Playwright ilə tapıldı və düzəldildi (curl testləri bunları
    tuta bilməzdi, çünki JS icra tələb edir):**
    1. `app.js`/`admin.js` `foot.php`/`shell_foot.php`-də (səhifə skriptindən
       SONRA) yüklənirdi — səhifələrin öz inline skriptləri "Birlikde is not
       defined" xətası ilə uğursuz olurdu. Düzəliş: script tag-ları `head.php`/
       `shell_head.php`-ə köçürüldü (skript sıra qaydası).
    2. Service Worker-in `fetch` handler-i BÜTÜN eyni-mənşəli GET sorğularını
       (JSON API daxil) tuturdu; SW daxilində `fetch(event.request)` çağırışı
       Chromium-da credentials-i itirirdi (məlum davranış) — sessiya cookie-si
       API sorğularına getmirdi, nəticədə hər GET sorğusu "boş" cavab verirdi
       (məs. tarixçə həmişə `[]` qaytarırdı, hətta sifariş mövcud olsa belə).
       Düzəliş: SW yalnız `/assets/*` və `manifest.json`-u tutur, HTML
       səhifələrinə/API-lərə TOXUNMUR.
  - **Tam brauzer ssenarisi (Playwright):** müştəri qeydiyyat→giriş→sifariş
    yaratma (aktiv sifariş kartı düzgün göründü); kuryer qeydiyyat→giriş→ərazi
    seçimi→onlayn; **tam SSE dövrəsi**: admin kuryeyə abunəlik verdi → müştəri
    eyni ərazidə sifariş yaratdı → kuryerin lövhəsində SSE ilə DƏRHAL göründü →
    "Götür" → "Mənim işim"ə keçdi → "Tamamlandı" → kart yox oldu.
  - **Admin brauzer ssenarisi:** giriş, müştəri axtarış+bloklama (bloklanan
    dərhal giriş edə bilmədi), real PNG banner yükləmə (siyahıda göründü), yeni
    şəhər/rayon yaratma+deaktivetmə — hamısı UI vasitəsilə.
  - **Dil dəyişikliyi:** eyni səhifədə 3 dilin fərqli mətn qaytardığı təsdiqləndi.
  - **Web Push:** abunə saxlama/silmə endpoint-ləri test edildi; ödəniş webhook-u
    vasitəsilə `PushService::gonder()` trigger edildi və gözlənilən "göndəriləcəkdi"
    log yazısı `storage/logs/`-da düzgün göründü (aktiv abunəlik olmadıqda isə
    sakitcə heç nə etmədiyi də təsdiqləndi).
  - Bütün brauzer konsol/səhifə xətaları YOXDUR (hər Playwright ssenarisində
    `pageerror`/`console.error` dinləyicisi aktiv idi).
  - Test DB/istifadəçi, `.env`, yüklənmiş test bannerləri, rate-limit keşi və
    log faylları təmizləndi, repoya heç bir sirr commit edilmədi.
- Növbəti addım: istifadəçi "Növbəti fazaya keç" deyəndə Faza 7 (Hüquqi) başlanacaq
  — bu, sonuncu fazadır (bax CLAUDE.md bölmə 9 "İş Qaydaları": bütün fazalar
  bitdikdən sonra final directory tree və birləşdirilmiş kod təqdim edilməlidir).

### 2026-07-11 — Faza 7 tamamlandı (Hüquqi)
- İstifadəçi `Birlikde_Huquqi_Paket.docx` yüklədi (10 hüquqi sənəd: İstifadəçi
  Müqaviləsi, Məxfilik Siyasəti, Cookie Siyasəti, Abunə və Ödəniş Şərtləri,
  Məsuliyyətdən İmtina, Kuryer və Yükdaşıma Qaydaları, Müştəri Qaydaları,
  Qadağan Olunmuş Yüklərin Siyahısı, Şikayət və Mübahisələrin Həlli, Fərdi
  Məlumatların Emalına Razılıq) və "hələlik lazım olan hər şeyi buradan götür,
  sonra elə et ki dəyişə bilim" tapşırığı verdi.
- **⚠ Vacib status:** sənəd mənbəyində açıq şəkildə qeyd olunur ki, bu QARALAMADIR
  — süni intellekt tərəfindən hazırlanıb, lisenziyalı hüquqşünas TƏSDİQLƏMƏYİB.
  Mətndə VÖEN, əlaqə nömrəsi, abunə məbləği, saxlanma müddəti kimi sahələr
  bracket-lə (`[...]`) işarələnmiş yer tutucu olaraq qalır — dəyişdirmək asan
  olsun deyə mətn birbaşa PHP faylında saxlanılır (DB-də yox).
- Struktur: `config/legal/sujetler.php` (10 sənədin sıra/başlıq siyahısı) +
  `config/legal/content/{slug}.php` (hər sənədin HTML məzmunu, mənbə mətnin
  bölmə nömrələri, bullet-lər, "⚖ Qanuni istinad" və "⚠" xəbərdarlıq callout-ları
  eynilə saxlanılıb). Yalnız Azərbaycan dilində — RU/EN səhifələrində "rəsmi
  mətn yalnız AZ dilindədir" qeydi göstərilir (uydurma tərcümə edilmədi).
- `LegalController` (`index()` — siyahı, `goster($slug)` — tək sənəd, naməlum
  slug `/huquqi`-yə redirect edir) + `Views/legal/{index,goster}.php` (mövcud
  `partials/head.php`/`foot.php` shell-i istifadə edir, hər səhifədə QARALAMA
  xəbərdarlığı görünür). Marşrutlar: `GET /huquqi`, `GET /huquqi/{slug}` —
  ictimai, giriş tələb olunmur (`routes/web.php`).
- `app.css`-ə `.legal-doc`, `.legal-callout-law`, `.legal-callout-warn`,
  `.legal-list` sinifləri əlavə olundu (mövcud OLED/glass dizaynına uyğun).
- i18n: `huquqi.*` açarları (6 ədəd) az/ru/en JSON-a paritetlə əlavə olundu
  (106 açar/dil, yoxlanıldı).
- `Views/auth/qeydiyyat.php`-dakı sözləşmə qutusuna Tam Mətni Oxu linkləri
  əlavə olundu (`/huquqi/istifadeci-muqavilesi`, `/huquqi/mexfilik-siyaseti`,
  yeni tab-da açılır). `config/sozlesme.php` şərhi yeni yerə istinad edəcək
  şəkildə yeniləndi (mətnin özü dəyişmədi — qısa xülasə olaraq qalır).
- **Test:** bütün yeni/dəyişdirilmiş fayllar `php -l` ilə yoxlanıldı, JSON
  dil faylları paritetli (106/106/106) təsdiqləndi. Real MariaDB (fresh
  migrate+seed) + real HTTP server (`php -S`, 4 worker) ilə: `/huquqi` (200),
  `/qeydiyyat` (200, yeni linklər HTML-də mövcud), bütün 10 sənəd slug-u (200,
  hər birində gözlənilən callout sayı təsdiqləndi), naməlum slug (302 redirect
  `/huquqi`-yə), `?dil=ru` ilə sənəd səhifəsində "yalnız AZ dilindədir" qeydi
  göründü. Test DB/istifadəçi, `.env`, rate-limit keşi təmizləndi.
- **Bütün fazalar (0-7) tamamlandı.** Növbəti addım: istifadəçinin tələbinə
  görə (bax CLAUDE.md bölmə 9.7) final directory tree və birləşdirilmiş kod
  təqdimatı — istifadəçi bunu ayrıca tələb etdikdə hazırlanacaq.

### 2026-07-12 (davam) — Giriş/Qeydiyyat kart karuseli + sifariş izləmə animasiyası + yeni splash

- İstifadəçi 4 istinad video göndərdi (ffmpeg contact-sheet üsulu ilə
  təhlil edildi — Read binary .mp4 aça bilmir, `ffmpeg -vf "fps=X,tile=RxC"`
  ilə tiled PNG çıxarılıb baxıldı): (1) qara kart karuseli UI tutorialı
  (fanned deck, toxunanda öndə böyüyür), (2) Three.js/WebGL "enerji orbu"
  splash tutorialı, (3-4) eyni fayl (MD5 uyğun) — Flutter "kuryer axtarılır
  → yoldadır → çatdı" izləmə ekranı tutorialı (@dailyflutterui).
- İstifadəçi qərarı: 1 və 3 təsdiqləndi, 2 (splash) üçün "maraqlı,
  mükəmməl bir açılış et" — sərbəstlik verildi, Three.js YOX (aşağı-
  səviyyəli Android performans riski əsaslandırılıb, əvvəlcədən razılaşıb).
- **Giriş/Qeydiyyat kart karuseli:** `auth/giris.php` və `auth/qeydiyyat.php`
  hər ikisi indi `#authDeck` (iki fanned kart: Giriş=mavi qradiyent,
  Qeydiyyat=narıncı/çəhrayı qradiyent) ilə açılır — öz kartına toxunanda
  (`Birlikde.initAuthDeck()`, yeni `app.js` funksiyası) kart böyüyüb önə
  keçir, deck sönür, forma (`#authFormWrap`) aşağıdan sürüşərək açılır;
  digər kartına toxunanda kart önə keçir və `?open=1` ilə digər səhifəyə
  keçid edilir (orada eyni animasiya avtomatik təkrarlanır). Yeni CSS:
  `.auth-deck`/`.deck-stack`/`.deck-card` və s. (bax `app.css`). Playwright
  ilə statik test səhifələri üzərində vizual təsdiqləndi (fan effekti,
  toxunma keçidi, forma açılışı) — test faylları müvəqqəti idi, commit-ə
  daxil deyil.
- **Sifariş izləmə animasiyası:** `musteri/panel.php`-də aktiv sifariş
  kartı artıq YALNIZ `axtarisda` deyil, `goturulub` statusunu da göstərir.
  `axtarisda` → radar-puls animasiyası (3 genişlənən halqa + döyünən mərkəz
  ikonu, "Kuryer axtarılır..."). `goturulub` → daşıyıcı kartı (baş hərf
  avatarı, ad, "Yoldadır" yaşıl nöqtə) + hərəkətli irəliləyiş zolağı +
  WhatsApp düyməsi (ləğv düyməsi göstərilmir — backend artıq yalnız
  `axtarisda` statusunda ləğvə icazə verir, `Sifaris::cancel()`). Yeni CSS:
  `.tracking-block`/`.radar`/`.transit-row`/`.transit-track` və s. Real
  GPS/canlı xəritə YOXDUR (bu app-da mövcud deyil) — status-əsaslı
  animasiya ilə "canlı izləmə" hissi verilir, saxta ETA rəqəmi göstərilmir.
- **Yeni splash animasiyası:** köhnə 5-blob "partlayış" əvəz olundu —
  Canvas 2D hissəcik sistemi (`runSplashParticles()`, `app.js`): mərkəzdən
  spiral şəklində genişlənən ~70 parlaq hissəcik (marka rəngləri: mavi/
  narıncı/çəhrayı/yaşıl/sarı, radial-gradient glow), arxada CSS puls
  effektli işıq halosu (`.splash-glow`), üstündə mövcud "Birlikdə" söz
  bounce animasiyası. **Şüurlu qərar: Three.js/WebGL YOX** — Canvas 2D
  ilə ~70 hissəcik 60fps-də aşağı-səviyyəli Android telefonlarda da
  rahat işləyir, WebGL+shader yükü (150-600KB əlavə + performans/enerji
  riski) əsassızdır bu istifadə halı üçün. `prefers-reduced-motion`
  hörmət edilir (hissəciklər tamamilə keçilir). Playwright screenshot
  ilə vizual təsdiqləndi (mərkəzdən partlayan rəngli hissəciklər →
  sözün üstündə sönür).
- 3 yeni i18n blok (az/ru/en): `qapi.*` (deck başlıqları), `musteri.
  axtarilir_*`/`musteri.yolda_*` (izləmə mətnləri). `php -l` bütün
  dəyişən PHP fayllara, `node --check` `app.js`-ə, JSON validasiyası
  3 dil faylına təmiz keçdi.

### 2026-07-12 (davam) — Kart karuseli təkmilləşdirməsi: drag, drawer-dock, logo

- İstifadəçi canlı serverdə (`app.birlikde.biz`) yoxlayıb 6 bənd geri-bildirim
  verdi (real telefon skrinşotları ilə): (1) deck-in altında həddindən artıq
  boş sahə, (2) kartlar YALNIZ toxunma ilə açılır — real-vaxt sürüşdürmə
  (drag) istəyi, (3) drawer-də "Əsas sayta qayıt"/"Hüquqi Sənədlər" siyahıdan
  çıxıb aşağıda animasiyalı ikon-dock olsun, (4) dil bayraqları "Birlikdə"
  yazısının altına, ortada, yanaşı keçsin, (5) "Birlikdə" mətni "bərbaddır",
  müasir logo görünüşünə çevrilsin, (6) kartlar və üzərindəki emoji-lər
  davamlı animasiyalı olsun.
- **Boşluq düzəlişi:** `.auth-page` sarğı div-i (`giris.php`/`qeydiyyat.php`)
  — `.container`-in 84px `padding-bottom`-u (digər səhifələrdəki sürüşən
  siyahılar üçün nəzərdə tutulub) mənfi `margin-bottom:-84px` ilə ləğv
  edilir ki, dikey ortalama HƏQİQƏTƏN görünən ekran sahəsinə görə olsun —
  əvvəlki cəhddə (`min-height: calc(100vh - 170px)`, `margin` olmadan)
  riyazi ortalama düzgün idi, amma container-in öz alt padding-i ORTALAMA
  QUTUSUNDAN SONRA əlavə olunduğu üçün nəticə asimmetrik idi (aşağıda daha
  çox boşluq). Playwright screenshot ilə görüldü, yuxarı/aşağı boşluqlar
  demək olar bərabərləşdi.
- **Sürüşdürmə (drag):** `Birlikde.initAuthDeck()` tam yenidən yazıldı —
  Pointer Events (mouse+toxunma vahid) ilə ön kartı real-vaxtda izləyir
  (`--dragX`/`--dragRot` CSS dəyişənləri, `.deck-card-front` bunları
  `transform`-da oxuyur). 70px-dən az sürüşdürülübsə "toxunma" sayılır və
  forma açılır (köhnə davranış saxlanıldı); 70px-dən çox sürüşdürülübsə kart
  həmin istiqamətə uçub gedir və digər kart önə keçib səhifə dəyişir (eyni
  `?open=1` mexanizmi). Köhnə `:active` scale-down qaydaları silindi (yeni
  drag transformu ilə toqquşurdu — CSS specificity bug, `:active` daha
  spesifik olduğu üçün drag-i "dondururdu"). Playwright ilə mouse
  down→move→up simulyasiyası ilə həm sürüşdürmə izi, həm uçub-getmə,
  həm digər kartın önə keçməsi vizual təsdiqləndi.
- **Drawer yenidən quruldu:** "Əsas sayta qayıt" və "Hüquqi Sənədlər"
  siyahı elementləri çıxarılıb, yeni `.drawer-dock` (2 dairəvi ikon, 🏠/📜,
  davamlı yumşaq üzmə animasiyası `dockFloat`) drawer-in lap altına
  (`.drawer-bottom` sarğısı, `margin-top:auto`) əlavə olundu. Dil
  bayraqları (`.drawer-langs`) aşağıdan çıxarılıb `.drawer-brand`-ın
  (indi `.brand-logo`) altına, ortalanmış/yanaşı köçürüldü. Artıq boş
  qalan `.drawer-section-label` CSS qaydası silindi (istifadə olunmurdu).
- **Yeni "Birlikdə" logo:** paylaşılan `app/Views/partials/logo.php`
  komponenti (`.brand-logo`/`.brand-mark`/`.brand-word` — qradiyentli "B"
  nişanı dairəvi kvadratda + qradiyent-mətn "Birlikdə" sözü, `em`-əsaslı
  miqyaslanma) top-nav, drawer-brand, auth-deck başlığı VƏ splash sözündə
  bütün köhnə düz mətni əvəz etdi — 5 yerdə eyni marka indi vahid görünür.
  (Splash-da mövcud bounce/opacity animasiyası toxunulmadı, yeni logo ona
  daxil edildi.)
- **Kart idle animasiyası:** hər `.deck-card`-ın içinə `.deck-card-inner`
  sarğısı əlavə olundu (drag transformu ilə toqquşmasın deyə — eyni
  elementdə iki fərqli `transform` animasiyası ola bilməz) — yumşaq
  yuxarı-aşağı üzmə (`cardFloat`, 3.4s) + emoji ikonun yellənmə/böyümə
  animasiyası (`iconWiggle`, 2.8s), hər kartda fərqli gecikmə ilə.
- `sw.js` `CACHE_VERSION` v14→v15 (yenə `app.css`/`app.js` dəyişdi).
  `php -l` bütün dəyişən fayllara, `node --check` `app.js`-ə təmiz keçdi.
  Playwright ilə statik test səhifələri üzərində bütün 6 bənd vizual
  təsdiqləndi (müvəqqəti test faylları commit-ə daxil deyil).

### 2026-07-12 (davam) — Kart karuseli ləğv edildi, accordion + bottom-nav + splash təkmilləşdirməsi

- İstifadəçi canlıda 3 yeni video göndərdi (ffmpeg contact-sheet üsulu ilə
  təhlil edildi): (1) "Scoops" dondurma tətbiqi tutorialı — açılış ikon-
  pulse + "Get Started" axını VƏ ev ekranındakı üzən bottom-nav (🏠/💬/🛒/👤,
  aktiv olan ağ dairə ilə vurğulanır); (2) əvvəllər görülmüş kuryer-izləmə
  videosunun təkrarı (eyni MD5, yeni məlumat yox); (3) "Hover Expanding
  Login" kolleksiyası — kompakt "LOGIN" pill-i toxunulanda/hover-də tam
  formaya genişlənir (neon mavi/çəhrayı sərhədli, tünd tema).
- İstifadəçi qərarı: kart-karuseli konsepti LƏĞV edildi ("cart məsələsini
  ləğv edirik, daha müasir bir şey etməliyik") — əvəzinə video (3)-dəki
  "genişlənən pill" konsepsiyası, öz rəng sxemimizdə. Bundan əlavə: "aşağı
  naviqasiya paneli kimi olsun, toxunanda bölmənin adı animasiya ilə
  yazılsın" (video 1-dəki bottom-nav) və splash üçün "ikon pulse edir,
  sonra mətn" ritmi istəndi.
- İstifadəçinin bildirdiyi 2 "xəta": (a) kart sürüşdürüləndə birbaşa
  qeydiyyata keçməsi — bu, əvvəlki sessiyada bilərəkdən qurulmuş sürüşdürmə-
  keçid funksiyası idi, kartların ləğvi ilə mövzu bağlandı, ayrıca düzəliş
  tələb olunmadı; (b) "girişetməmiş istifadəçi üçün sol menyu overlay kimi
  deyil, birbaşa ekranda görünür" — Playwright ilə təcrid olunmuş mühitdə
  (`/giris`, real MySQL + HTTP) yoxlanıldı: `.drawer` `position:fixed`,
  bağlı vəziyyətdə `transform:translateX(-108%)`, açıqda `translateX(0)`
  — TAM DÜZGÜN davranış, reproduksiya olunmadı. Səbəb ehtimalı: köhnə
  Service Worker keşinin hələ aktiv olması (öncəki push-lardan sonra tam
  bağlanıb-açılmayıb). İstifadəçidən bu yeniləmədən sonra yenidən yoxlamaq
  və davam edərsə screenshot göndərmək xahiş olundu.
- **Yeni accordion (auth/giris.php, auth/qeydiyyat.php):** kart-deck tam
  silindi. İndi iki "pill" başlıq (`​.accordion-item`, mavi=Giriş,
  narıncı-çəhrayı=Qeydiyyat qradiyenti) alt-alta göstərilir; öz-səhifənin
  başlığına toxunanda CSS grid `0fr → 1fr` texnikası ilə (JS hündürlük
  ölçmədən, avtomatik uyğunlaşan) forma yerində açılır, digər başlığa
  toxunanda qısa "pulse" əks-əlaqədən sonra `?open=1` ilə digər səhifəyə
  keçilir (orada eyni item avtomatik açılır). `Birlikde.initAuthAccordion()`
  köhnə `initAuthDeck()`-i əvəz etdi (drag/swipe məntiqi ləğv edildi).
  Playwright ilə real DB+HTTP-lə tam axın (bağlı → açıq → doldurulmuş
  qeydiyyat forması, rol tabları daxil) vizual təsdiqləndi.
- **Yeni bottom-nav (yalnız girişli istifadəçilər):** rol-əsaslı naviqasiya
  elementləri (müştəri: Panel; kuryer/yükdaşıma: Lövhə+Sifarişlərim+Profil)
  drawer-dən çıxarılıb üzən, dairəvi `.bottom-nav` panelinə köçürüldü —
  passiv bölmələr yalnız ikon göstərir, AKTİV bölmə qradiyent fon +
  animasiyalı genişlənən label göstərir (`max-width`/`opacity` keçidi,
  bounce). SPA marşrutlaşdırma (`updateDrawerActive()`) indi bottom-nav
  elementlərini də yeniləyir — tab-lar arası keçiddə DOM elementi
  saxlanıldığı üçün animasiya HƏQİQƏTƏN oynayır (tam səhifə yenilənməsində
  isə sadəcə son vəziyyət göstərilir, normal haldır).
- **Splash ikon-pulse:** `.brand-mark` (loqo işarəsi) indi sözdən ƏVVƏL
  öz-özünə "pop" edib 2 dəfə pulse edir (`splashMarkPop`+`splashMarkPulse`),
  sonra "Birlikdə" sözü sıçrayaraq açılır (gecikmə 0→0.7s) — reference
  video-dakı "ikon pulse, sonra mətn" ritmini təqlid edir. Ümumi splash
  müddəti 1650ms→1900ms uzadıldı (yeni ritmə yer üçün). Köhnə istifadə
  olunmayan `.splash-blob`/`@keyframes splashBlobBurst` CSS-i silindi.
- `sw.js` `CACHE_VERSION` v15→v16. `php -l`/`node --check` təmiz keçdi.
  Lokal MariaDB (müvəqqəti test DB, migrate+seed) + PHP daxili server ilə
  Playwright vasitəsilə tam vizual test edildi (accordion açılış/bağlanış,
  qeydiyyat forması, drawer overlay davranışı, bottom-nav aktiv vəziyyəti,
  splash ikon-pulse mərhələsi) — müvəqqəti test faylları/`​.env` commit-ə
  daxil deyil.

### 2026-07-13 — Bug: accordion "donur" (toggle-close yox idi)

- İstifadəçi bildirdi: "açılışda donur giriş və qeydiyyat, həmçinin yenidən
  toxunanda bağlanmır." Kod nəzərdən keçirilərək təsdiqləndi: `initAuthAccordion()`
  yalnız AÇMA məntiqi yazılmışdı (`openItem()`), artıq açıq olan item-ə
  yenidən toxunanda BAĞLAMA (toggle) heç vaxt tətbiq olunmurdu — istifadəçi
  bunu "dondu" kimi hiss edib (açılır, sonra heç nəyə cavab vermir).
  Düzəliş: `header` klik handler-inə `if (item.classList.contains('open'))
  closeAll(); else openItem(item);` şərti əlavə olundu — indi öz-səhifənin
  pill-inə basmaq aç/bağla arasında keçir. Playwright ilə real HTTP-lə
  1-ci toxunuş→open class, 2-ci toxunuş→open class silinir təsdiqləndi.
- İstifadəçi eyni zamanda əvvəllər göndərdiyi kuryer-izləmə videosunu
  (MD5 dəyişməz, artıq `musteri/panel.php`-də tətbiq olunmuş radar-puls
  dizaynı) yenidən "giriş animasiyası üçün" istinad kimi göndərdi — bu
  video splash/giriş ilə əlaqəli deyil, artıq tanınıb istifadə olunub;
  aydınlıq üçün istifadəçidən soruşuldu.
- `sw.js` `CACHE_VERSION` v16→v17.

### 2026-07-13 (davam) — Splash yenidən: radar-puls halqaları (Canvas silindi)

- İstifadəçi "giriş animasiyası" dedikdə açılış (splash) ekranını nəzərdə
  tutduğunu təsdiqlədi (AskUserQuestion ilə aydınlaşdırıldı) və əvvəllər
  göndərilmiş kuryer-izləmə videosundakı radar-puls effektini istəyirdi.
- Canvas hissəcik-partlayışı tam silindi (`runSplashParticles()`,
  `#splashCanvas`) — əvəzinə tam CSS: loqo işarəsinin (`.brand-mark`)
  ətrafında iki rəngli genişlənən/sönən halqa (`::before`/`::after`,
  `splashRingPing`), mövcud ikon-pop/pulse + söz-bounce ritmi ilə birgə.
  Nəticə: daha da yüngül (JS/rAF loop-u tamamilə yox), müştəri panelindəki
  sifariş-axtarışı radar-una vizual uyğunluq yaradır.
- **Tapılan yan-bug:** ilkin versiyada `.splash-glow` (fon işıq halosu)
  ikonla üst-üstə düşmürdü — səbəb: `.brand-word` mətni hələ görünməzkən
  belə öz enini "ehtiyat saxlayırdı" (`opacity:0` amma layout-da yer
  tuturdu), bu da `.splash-center`-in üfüqi mərkəzini sözün gələcək eninə
  görə sürüşdürürdü, ikon isə sol tərəfdə qalırdı. Düzəliş: splash
  kontekstində `.brand-logo` sətir (row) əvəzinə sütun (column) düzülüşünə
  keçirildi (`.splash-word .brand-logo { flex-direction: column }`) —
  ikon və söz indi eyni üfüqi mərkəz xəttində, sözün eni fərq etmir.
  Playwright screenshot ilə həm 0.5s (yalnız ikon+halqa+glow, tam
  mərkəzləşmiş), həm 1s (tam loqo+tagline) anlarında təsdiqləndi.
- `sw.js` `CACHE_VERSION` v17→v18.

### 2026-07-13 (davam) — Telefon-əsaslı ağıllı giriş/qeydiyyat (Bolt-stil), accordion ləğv edildi

- İstifadəçi accordion-un da "donma" hissi verdiyini bildirdi ("tam dəyişsək
  yenə donur") və daha "ideal/super" bir yanaşma istədi. Tövsiyə edildi və
  qəbul olundu: Bolt/Uber tərzi telefon-əsaslı ağıllı tək axın — kart/
  accordion seçimi TAM aradan qaldırıldı.
- **Backend:** `AuthService::telefonMovcuddurmu()` (mövcud `User::
  existsByTelefon()`-dan istifadə edir) + `AuthController::telefonYoxla()` +
  `POST /telefon-yoxla` route (CsrfGuard+RateLimit, digər auth endpoint-ləri
  ilə eyni qorunma). `AppPageController::giris()` indi qeydiyyat üçün lazım
  olan `$olculer`/`$sozlesmeMetni`-ni də hazırlayır (hər iki nəticə eyni
  səhifədə ola bilər); `AppPageController::qeydiyyat()` sadəcə `/giris`-ə
  redirect edir (ayrı qeydiyyat marşrutu artıq yoxdur, köhnə bookmark-lar
  üçün saxlanılıb). `auth/qeydiyyat.php` view faylı silindi.
- **Frontend — 3 addımlı wizard (`auth/giris.php`):** (1) telefon (ölkə kodu
  dropdown, defolt Azərbaycan, dəyişdirilə bilər — istifadəçinin UX Snacks
  TikTok istinadına görə, ayrı `public/assets/js/olke-kodlari.js`, ~90 ölkə,
  bayraq ISO koddan avtomatik yaradılır) → (2a) mövcuddursa Parol, (2b)
  deyilsə tam qeydiyyat sahələri. Addımlar arası keçid ƏN SADƏ üsulla —
  `[hidden]` atributu + mövcud `pageIn` animasiyası (heç bir grid/drag/
  toggle-state riski yoxdur ki, əvvəlki 2 versiyada olduğu kimi "donsun").
  `Birlikde.initAuthWizard()` yeni — `initAuthDeck`/`initAuthAccordion`-u
  əvəz etdi. Qeydiyyatdan keçəndə backend sessiya açmadığı üçün (yalnız
  hesab yaradır) frontend qeydiyyatdan SONRA avtomatik `/giris`-ə eyni
  məlumatla ikinci sorğu göndərib istifadəçini bilavasitə daxil edir —
  əl ilə "indi gir" addımı yoxdur (Bolt-un elə etdiyi kimi).
- **UX Snacks istinadına görə əlavə cilalanma:** (1) parol sahələrində
  göz-ikonu göstər/gizlət düyməsi (`.password-toggle`, `Birlikde.
  initPasswordToggles()`); (2) güclü fokus vəziyyəti — indi `outline`
  əvəzinə həqiqi sərhəd rəngi + 2px "halo" (`box-shadow`); (3) inline
  validasiya — boş "Ad"/"Soyad" və 6 simvoldan qısa parol üçün qırmızı
  sərhəd + sahənin altında KONKRET köməkçi mətn (`Birlikde.showFieldError`/
  `clearFieldError`, ümumi "xəta baş verdi" DEYİL, "Bu sahə mütləqdir" /
  "Ən azı 6 simvol olmalıdır" kimi spesifik mesajlar).
- **Tapılan real bug (test zamanı, Playwright real-mouse-click ilə):**
  "Dəyiş" (geri qayıt) düyməsinin toxunma sahəsi demək olar sıfır idi
  (`padding: 0`, ~15px hündürlük) — proqramatik `.click()` işləyirdi, amma
  DƏQIQ koordinatda REAL siçan/barmaq klikı düyməni "keçib" valideyn
  div-ə düşürdü (Apple/Google minimum 44px toxunma-sahəsi tövsiyəsinin
  pozulması). Bu, real istifadəçilərin hiss etdiyi "səhifə cavab vermir"
  problemlərinin bir mənbəyi ola bilər. Düzəliş: `padding:10px 8px;
  margin:-10px -8px; min-height:44px` (vizual ölçü eyni qalır, toxunma
  sahəsi genişlənir). `.password-toggle` da eyni məntiqlə 40×40px-ə
  böyüdüldü. Real Playwright klik testi ilə düzəlişdən sonra təsdiqləndi.
- Real MySQL+HTTP+Playwright ilə tam axın test edildi: yeni telefon →
  qeydiyyat sahələri → uğurlu təqdim → avtomatik giriş → `/panel`; sonra
  eyni telefonla təzə sessiyada → "Parol" addımı → uğurlu giriş → `/panel`.
  "Dəyiş" düyməsi hər iki addımdan geri qayıdır. İnline validasiya (boş
  sahə, qısa parol) vizual təsdiqləndi.
- `sw.js` `CACHE_VERSION` v18→v19. `php -l`/`node --check`/JSON validasiyası
  bütün dəyişən fayllara təmiz keçdi.

### 2026-07-13 (davam) — Qeydiyyata yönləndirmə animasiyası + drawer dock qismən geri qaytarıldı

- **"Qeydiyyata yönləndirilirsiniz..." keçid addımı:** yeni telefon nömrəsi
  daxil ediləndə artıq birbaşa qeydiyyat formasına keçmir — əvvəlcə yeni
  `.auth-step[data-step="redirecting"]` göstərilir (3 rəngli sıçrayan
  nöqtə animasiyası + bounce-in mətn, ~1.1s), sonra qeydiyyat forması
  açılır. Playwright ilə vaxt-ölçülü test edildi: 400ms-də "redirecting"
  addımı göstərilir, 1400ms-də "register"-ə keçib.
- **Drawer dock qismən geri qaytarıldı:** istifadəçi bildirdi ki, aşağı
  ikon-dock ("Əsas sayta qayıt"/"Hüquqi Sənədlər") "donma" hissi verirdi —
  ikon-only dock (`​.drawer-dock`/`.dock-icon`) tam silindi, bu 2 keçid
  yenidən adi mətn `.drawer-item` kimi göstərilir (əvvəlki dizayna
  bənzər), AMMA dil bayraqları İSTİFADƏÇİNİN AÇIQ TƏLƏBİNƏ görə hazırkı
  mövqedə (brand başlığının altında, yuxarıda) SAXLANILDI — bu, əvvəlki
  (bayraqlar aşağıda olan) vəziyyətə tam geri dönüş DEYİL, qarışıq
  vəziyyətdir (bayraq yeri yeni, keçid stili köhnə).
- `sw.js` `CACHE_VERSION` v19→v20. `php -l`/`node --check`/JSON
  validasiyası təmiz keçdi.

### 2026-07-13 (davam) — Kuryer profili: böyük "hero" foto+ad formatı

- İstifadəçi istinad şəkli göndərdi (pro-oyunçu profil kartı UI-si) və
  "şəkil və ad belə görünsün" dedi. `kurye/profil.php` yenidən quruldu:
  kiçik dairəvi avatar (88px) əvəzinə kartın tam eninə böyük "hero" foto
  (230px hündürlük, `.profil-photo-wrap`), redaktə qələm-düyməsi fotonun
  üstündə üzən dairə kimi (sağ-alt küncdə), ad indi FOTONUN ALTINDA iri
  şriftlə (18px→25px). Qaranlıq overlay/mətn-üstə-foto YOX — Canlı Şəhər
  açıq mövzusuna uyğunlaşdırıldı (ad aydın fonda, foto altında).
  JS-ə toxunulmadı (yalnız CSS sinif adları dəyişdi: `profil-avatar*` →
  `profil-photo*`, ID-lər eyni qaldı). Real qeydiyyat+giriş+profil axını
  ilə Playwright screenshot vasitəsilə vizual təsdiqləndi (foto yoxdursa
  qradiyent fonda baş hərf, foto varsa tam-eninə şəkil).
- `sw.js` `CACHE_VERSION` v20→v21. `php -l` təmiz keçdi.

## Qeydlər / Açıq Suallar (fazalar arası unudulmamalı)

- Hüquqi mətnlər (Müqavilə/Məxfilik) hələ yoxdur — Faza 7-yə saxlanılıb, infrastruktur
  (checkbox + `sozlesme_qebul`/`sozlesme_tarix` + `legal_logs`) Faza 2-də hazırlanmalıdır.
- Sumqayıt ərazi siyahısı seed məlumatı canlıya keçmədən əvvəl rəsmi mənbədən təsdiqlənməlidir.
- Ödəniş provayderi (Birbank/Payriff) seçimi Faza 5-də konkretləşdiriləcək — kod
  provayder-neytral interfeyslə yazılır.
- Faza 5 tamamlandı: `PaymentProvider` interfeysi + Birbank/Payriff adapterləri
  hazırdır, lakin sahə adları/URL YER TUTUCUDUR (real API sənədləşməsi
  gələndə `app/Providers/BirbankProvider.php` və `PayriffProvider.php`
  yenilənməlidir — imza sxemi/sahə adları bankın həqiqi tələblərinə uyğunlaşdırılmalıdır).
- Abunə qiyməti `ayarlar.abune_qiymeti`-də saxlanır (seed default 15.00 AZN) —
  admin panelindən qiymət DƏYİŞMƏ endpoint-i YOXDUR (yalnız DB-dən dəyişdirilə
  bilər), bölmə 8.5-də açıq tələb olunmadığı üçün əlavə edilmədi.
- Faza 5 cron-u (`abunelik_xeberdarlig`) yalnız audit jurnalına yazır — faktiki
  Web Push bildirişi göndərmə Faza 6-da (VAPID açarları, `push_abuneler`
  cədvəli, Service Worker) qurulacaq.
- Faza 6 tamamlandı: yuxarıdakı bənd üçün infrastruktur (VAPID, `push_abuneler`,
  SW, trigger nöqtələri) hazırdır, AMMA faktiki şifrələnmiş göndərmə (RFC 8291)
  hələ YOXDUR — `PushService::gonder()` yalnız `storage/logs/`-a yazır. Real
  göndərmə əlavə ediləndə YALNIZ bu metodun daxili hissəsi dəyişməlidir.
- `push_abuneler` cədvəli əvvəlki fazalarda səhvən "artıq mövcuddur" hesab
  edilmişdi — Faza 6-da düzəldilib, migration 016 kimi əlavə olundu.
- Canlıya keçmədən əvvəl: real VAPID açarları `database/generate_vapid_keys.php`
  ilə yaradılıb `.env`-ə yazılmalıdır (hazırkı `.env.example` yer tutucudur).
- **PWA ikonları yeniləndi (2026-07-11):** əvvəlki `icon-192.png`/`icon-512.png`
  şəffaf künclü dairə idi — "maskable" tələbini pozurdu (OS maska tətbiq edəndə
  şəffaf küncdən boşluq görünə bilərdi). `database/generate_pwa_icons.php`
  yazıldı (GD, 8x supersample + downsample), tam-bleed qara kvadrat fon + ağ
  "B" glif, maskable safe-zone daxilində (~%47-48 canvas, limit %80) yenidən
  yaradıldı, künc opaklığı təsdiqləndi (`alpha=0` = tam opak GD-də). Hələ də
  proqramla çəkilmiş sadə plaseholder-dir (real marka loqosu deyil) — real
  dizayn hazır olanda ya skript yenidən yazılmalı, ya da fayllar birbaşa
  əvəzlənməlidir (manifest.json/head.php dəyişməyəcək, eyni fayl adları).
- **Canlıya keçmədən əvvəl (Faza 7, MÜTLƏQ):** `config/legal/content/*.php`
  içindəki hüquqi mətnlər lisenziyalı hüquqşünas tərəfindən yoxlanılıb
  təsdiqlənməlidir; bracket-lə (`[...]`) işarələnmiş yer tutucular (VÖEN,
  operator adı, əlaqə nömrəsi, abunə məbləği, saxlanma müddəti, geri qaytarılma
  siyasəti) doldurulmalıdır. Bu, hazırkı halında hüquqi məsləhət DEYİL.

### 2026-07-12 — Canlıya çıxarma prosesi + UX düzəlişləri (post-launch)

- Layihə Hetzner+aaPanel üzərində canlıya çıxarıldı (`app.birlikde.biz` +
  `appadmin.birlikde.biz`, ikinci sayt birincinin qovluğuna symlink,
  eyni kodu/storage-ı paylaşır — iki ayrı klon YOXDUR). aaPanel-ə xas
  düzəlişlər: `.user.ini` immutable bayrağı (`chattr -i`), `putenv`/`system`
  funksiyalarının `disable_functions`-dan çıxarılması, PHP-FPM soketi
  (`unix:/tmp/php-cgi-83.sock`) `location = /sse/lovhe` blokunda əl ilə
  təkrarlanmalı oldu (aaPanel-in avtomatik generasiya etdiyi `enable-php-83.conf`
  yalnız ümumi `.php$` reqex-i əhatə edir), URL Rewrite qaydaları GUI-dən
  deyil, birbaşa `/www/server/panel/vhost/rewrite/{domen}.conf` fayllarına
  yazıldı (`try_files $uri $uri/ /index.php?$query_string;` və admin üçün
  `/admin.php?$query_string`).
- **UX/biznes-məntiq dəyişikliyi (istifadəçi tələbi ilə):** kuryerin ayrıca
  "Tamamla" addımı LƏĞV edildi — "Götür" basılan kimi sifariş ATOMIC olaraq
  birbaşa `axtarisda → tamamlandi`-yə keçir (əvvəlki iki-addımlı
  `axtarisda → goturulub → tamamlandi` axını sadələşdirildi). Nəticələr:
  - `Sifaris::atomicGotur()` birbaşa `status='tamamlandi'` yazır,
    `Sifaris::complete()` və `listByKuryeAktiv()` (dead code) silindi.
  - `SifarisService::gotur()` `kuryeler.tamamlanan` sayğacını dərhal artırır
    (əvvəllər `tamamla()`-da idi, o metod tamamilə silindi).
  - `POST /sifaris/{id}/tamamla` və `GET /kurye/aktiv-isler` marşrutları
    silindi (404 verir).
  - Kuryer lövhəsində "Mənim işlərim" bölməsi tamamilə çıxarıldı; "Götür"
    basılan kimi kart lövhədən silinir, əvəzinə müştərinin adı+ünvanları+
    WhatsApp keçidi olan pop-up (`.modal-overlay`) açılır.
  - Müştəri tərəfdə `SifarisService::tarixce()` indi daşıyıcının adı və
    hazır WhatsApp linkini (`dasiyici_adi`, `dasiyici_whatsapp_link`) hər
    sifariş üçün qaytarır — götürülüb-tamamlanmış sifarişlərdə tarixçədə
    daimi görünür (bax `Views/musteri/panel.php`).
- **Bug düzəlişi:** kuryer lövhəyə hər girişində/yeniləməsində vizual olaraq
  "offline"-a sıfırlanırdı (`onlaynToggle.checked = false` hardcode idi,
  serverdəki həqiqi vəziyyət heç vaxt oxunmurdu). İndi səhifə açılanda
  `GET /kurye/profilim`-dən həqiqi `onlayn` vəziyyəti oxunur və UI ona görə
  qurulur (SSE bağlantısı da yalnız faktiki onlayn olduqda açılır).
- **Vizual yeniləmələr:** dil seçimi indi bayraq emoji-ləri ilə (`.lang-flag`,
  aktiv dil boyalı/parlaq, digərləri boz/solğun); onlayn/offline indi mobil-
  tətbiq tərzi toggle switch (`.toggle-switch`) + status nöqtəsi
  (`.lovhe-status-dot`, onlayn olanda yaşıl "halo"); "Canlı Lövhə" başlıq
  bloku (`.lovhe-header`) təmiz kart dizaynına keçirildi. Rəng sxemi
  (OLED qara + `.glass`) toxunulmadı, yalnız komponent səviyyəsində.
- Real MySQL + HTTP (curl, o cümlədən race-safety üçün təkrar götürmə cəhdi
  409 qaytardığı təsdiqləndi) + real Playwright/Chromium skrinşotları ilə
  test edildi (dil bayraqları, toggle switch, pop-up, müştəri tarixçəsi
  daşıyıcı əlaqə bloku ilə birlikdə vizual təsdiqləndi). RU bayrağının
  headless Linux Chromium-da monoxrom göründüyü müşahidə olundu — kodda
  Unicode kod nöqtələri (`&#127479;&#127482;`) proqramla təsdiqləndi ki,
  düzgündür, bu sırf test mühitinin emoji-font məhdudiyyətidir, real
  iOS/Android-də normal rəngli bayraq kimi görünəcək.
- Test DB/istifadəçi, `.env`, keş, sessiya və log faylları təmizləndi.

### 2026-07-12 — Tam vizual redizayn: "Canlı Şəhər"

- İstifadəçi bütün saytın (müştəri+kuryer+yükdaşıma+admin) dizaynını dəyişmək
  istədi. 3 fərqli prototip (Artifact olaraq) təqdim edildi: A) Neon Marşrut
  (qaranlıq, neon), B) Poçt Dəftəri (isti kağız), C) Nəzarət Mərkəzi (OLED
  terminal). İstifadəçi A-nı bəyənmədi ("tamamfərqli" istədi) — A tamamilə
  yenidən, "Canlı Şəhər" istiqamətində (açıq lavanda fon, çoxrəngli mavi/
  narıncı/yaşıl/çəhrayı aksent sistemi, sıçrayışlı hərəkət, rəngli-partlayış
  splash) hazırlandı və bəyənildi. B/C tələb olunmadı, birbaşa əsl kodda
  tətbiq edildi.
- **CSS tam yenidən yazıldı** (`public/assets/css/app.css`, `admin.css`) —
  BÜTÜN mövcud sinif adları (`.card`, `.glass`, `.btn-primary/-ghost/-danger`,
  `.badge-*`, `.tab`, `.toggle-switch`, `.modal-overlay` və s.) və CSS
  dəyişən adları (`--text`, `--text-dim`, `--glass-border` və s.) SAXLANILDI —
  yalnız dəyərlər dəyişdi. Bu sayədə TƏK BİR view faylına toxunmadan bütün
  səhifələr yeni görünüşü avtomatik aldı (rənglər, künc radiusu, kölgələr,
  animasiyalar mərkəzi CSS-dən idarə olunur).
- **Yeni komponentlər əlavə olundu** (view dəyişikliyi tələb etdi):
  - Hamburger + sürüşən menyu (drawer): `partials/head.php`-ə əlavə olundu
    (rol-əsaslı naviqasiya + dil bayraqları köçürüldü), `app.js`-də
    `Birlikde.initDrawer()`.
  - Açılış (splash) animasiyası: hər səhifə YÜKLƏNƏNDƏ yox, **sessiyada bir
    dəfə** (`sessionStorage`) göstərilir — hər klikdə göstərilsəydi
    naviqasiya əzab verici olardı. Sinxron inline skript flash-ı önləyir
    (`app.js`-də `Birlikde.playSplashOnce()`).
  - Admin: sabit yan-menyu SAXLANILDI (drawer əvəzinə — desktop alət üçün
    daha münasib), amma eyni rəng/hərəkət dili tətbiq olundu; splash yalnız
    admin GİRİŞ səhifəsində (idarəetmə zamanı hər naviqasiyada göstərmək
    pis UX olardı).
- Fontlar: `@font-face` ilə xüsusi şrift YÜKLƏNMƏDİ (sistemin öz şrift
  yığını saxlanıldı) — prototip Artifact-larında istifadə olunan böyük
  base64 TTF-lər yalnız Artifact CSP-sinin xarici font CDN-ni bloklaması
  ucbatından idi, əsl saytda belə bir məhdudiyyət yoxdur və hər səhifə
  yükləməsində çoxlu MB font yükləmək performansa zərərli olardı; "böyük,
  qalın" görünüş `font-weight:800` ilə əldə edilib.
- 2 yeni i18n açarı (`splash.tagline`, `drawer.diger`) az/ru/en-ə paritetlə
  əlavə olundu (110/110/110). `manifest.json`-un `theme_color`/
  `background_color`-u da yeni fona uyğunlaşdırıldı.
- Real MySQL + HTTP + Playwright ilə tam test edildi: giriş/qeydiyyat/
  drawer/müştəri panel+tarixçə/kuryer lövhə+profil/hüquqi/admin giriş+
  dashboard — heç bir konsol xətası yoxdur, bütün ekranlar skrinşotla
  vizual təsdiqləndi. Test DB/istifadəçi/`.env`/keş təmizləndi.
- **Qeyd:** PWA ikonları (`icon-192.png`/`icon-512.png`) hələ köhnə qara
  fonlu "B" işarəsidir — yeni açıq temaya uyğunlaşdırılması (istəyə bağlı,
  bloklayıcı deyil) sonrakı bir addımda edilə bilər.

### 2026-07-12 — Canlı testdən sonra düzəlişlər (PWA keş, Sifarişlərim, profil)

İstifadəçi canlı serverdə "Canlı Şəhər" redizaynını sınadıqdan sonra 7 bənddən
ibarət geri bildirim verdi. Hamısı tətbiq, real MySQL+Playwright ilə test,
commit edildi:

1. **Kök səbəb tapıldı — PWA keş bayatlığı:** "Kuryer sifariş götürəndə kart
   yox olur amma pop-up açılmır" şikayəti Playwright ilə real klik axını
   canlandırılaraq araşdırıldı — təmiz mühitdə (fresh DB, fresh brauzer)
   axın SƏHVSIZ işlədi (`goturModalGoster()` düzgün çağırılır, modal
   `.visible` alır). Əsl kök səbəb tapıldı: `public/sw.js`-də `CACHE_NAME`
   heç vaxt versiyalanmırdı (`birlikde-shell-v1` sabit qalırdı), `fetch`
   handler isə "stale-first" strategiyası ilə KEŞLƏNMIŞ köhnə cavabı DƏRHAL
   qaytarır, şəbəkədən təzəsini yalnız FON REJIMDƏ növbəti dəfə üçün
   yeniləyir (`cached || networkFetch`). Nəticə: artıq quraşdırılmış PWA-sı
   olan kuryerin cihazı hər yeni deploy-dan sonra köhnə `app.js`/`app.css`
   ilə "ilişib qala" bilirdi, `activate` hadisəsinin köhnə keş təmizləməsi
   isə CACHE_NAME dəyişmədiyi üçün heç vaxt işə düşmürdü. **Düzəliş:**
   `CACHE_VERSION` sabiti əlavə olundu (`v1` → `v2`) + şərh ilə "hər
   asset dəyişikliyində bu artırılmalıdır" xəbərdarlığı yazıldı.
2. **Splash — hər səhifə açılışında:** əvvəlki "sessiyada bir dəfə"
   (`sessionStorage`) məhdudiyyəti İSTİFADƏÇİNİN AÇIQ TƏLƏBİ ilə geri
   çevrildi — `app.js`/`admin.js`-də `playSplashOnce()`-dən
   `sessionStorage` oxuma/yazma tamamilə çıxarıldı, `head.php`/
   `login_head.php`-dəki sinxron ön-yoxlama skripti silindi.
3. **SSE yeni sifarişlər — başda görünür:** `kurye/lovhe.php`-də
   `connectSse()`-nin `yeni_sifaris` handler-i `appendChild` əvəzinə
   `insertBefore(kartlarEl.firstChild)` istifadə edir — yeni sifariş
   həmişə lövhənin başında çıxır, mövcud `.card` `cardIn` animasiyası
   yeni əlavə olunan DOM elementinə avtomatik tətbiq olunur (əlavə CSS
   lazım olmadı).
4. **"Sifarişlərim" bölməsi (kuryer/yükdaşıma):** `Sifaris::listByKurye()`
   (yeni model metodu) + `SifarisService::kuryeSifarisleri()` (müştəri adı/
   WhatsApp linki ilə zənginləşdirir) + `SifarisController::kuryeSifarisleri()`
   + `GET /kurye/sifarislerim` marşrutu. `kurye/lovhe.php`-yə "Canlı Lövhə /
   Sifarişlərim" tab-ları əlavə olundu (müştəri panelindəki tab pattern-i
   ilə eyni), götürülmüş sifarişlər ən yenidən köhnəyə (`goturulme_vaxti
   DESC`) müştəri əlaqə məlumatı ilə göstərilir.
5. **Profil şəkli yükləmə:** `017_add_sekil_to_kuryeler.sql` migrasiyası
   (`kuryeler.sekil VARCHAR(64) NULL`), `Kurye::setSekil()`,
   `SifarisService::sekilYukle()` (BannerService-dəki eyni validasiya
   qaydası: JPEG/PNG/WEBP, 5MB limit, `getimagesize` yoxlaması,
   `storage/kurye-sekiller/`-də təsadüfi ad ilə saxlama), yeni
   `KuryeSekilController` (banner şəkil controller-inin eyni naming/
   təhlükəsizlik qaydası ilə) + `GET /kurye-sekil/{fayl}` marşrutu,
   `POST /kurye/sekil` yükləmə marşrutu.
6. **Profil — sosial-şəbəkə stilli redizayn:** `kurye/profil.php` böyük
   dairəvi avatar (və ya baş hərf placeholder-i) + qələm ikonlu redaktə
   düyməsi + ad/soyad + nəqliyyat nişanı + "tamamlanan sifariş"/"abunə"
   stat pill-ləri ilə yenidən qurulduq. `KuryeController::profilim()`
   indi `ad`/`soyad`/`sekil` də qaytarır (əvvəllər yalnız `neqliyyat`/
   `onlayn`/`tamamlanan`). Yeni CSS: `.profil-hero`, `.profil-avatar*`,
   `.profil-stats`, `.profil-stat-pill` (`app.css`).
7. **Yığcam (compact) dizayn keçidi — müştəri/kuryer/yükdaşıma:**
   `app.css`-də (admin.css TOXUNULMADI) `.container`/`.card`/`.field`/
   `.btn`/`.tabs`/`.tab`/`.top-nav`/`.lovhe-header`/`.empty-state`
   padding+margin dəyərləri azaldıldı, `h1/h2/h3` üçün ölçülü `font-size`
   + sıx `margin` əlavə olundu, `.card p` margini sıxlaşdırıldı — məqsəd
   həddindən artıq scroll-u azaltmaq idi, rəng/forma sxeminə toxunulmadı.
- 2 yeni i18n açarı (`kurye.sifarislerim`, `kurye.sifarislerim_bos`)
  az/ru/en-ə paritetlə əlavə olundu (112/112/112).
- Bütün dəyişikliklər real MySQL (təmiz, sıfırdan qurulmuş disposable DB)
  + `php -S` HTTP server + Playwright/Chromium ilə test edildi: splash
  hər səhifə açılışında göründüyü (həm `/lovhe`, həm `/profil` üçün ayrı-
  ayrı) təsdiqləndi; profil şəkli yükləmə+göstərmə axını uçdan-uca
  işlədiyi (fayl yükləndi, `/kurye-sekil/{fayl}` ilə göstərildi)
  təsdiqləndi; "Sifarişlərim" boş/dolu vəziyyətləri, iki sifariş
  götürüləndən sonra siyahının düzgün (ən yeni başda) göründüyü
  təsdiqləndi; SSE prepend + Götür pop-up axını təkrar-təsdiqləndi.
  `php -l` bütün `app/`, `config/`, `database/`, `routes/`, `public/`
  PHP fayllarında səhvsiz, `node --check` `app.js`/`admin.js`/`sw.js`-də
  səhvsiz. Test DB/istifadəçi, `.env`, keş/sessiya/log faylları,
  yüklənmiş test şəkilləri təmizləndi.

### 2026-07-12 (davam) — Sifarişlərim ayrıca səhifə, foto HEIC bug-ı, SPA naviqasiya

- İstifadəçi pop-up variantından imtina etdi: "Sifarişlərim" Canlı Lövhədən
  tamamilə çıxarıldı, əvəzində `GET /sifarislerim` ayrıca səhifəsi yaradıldı
  (profildəki "Tamamlanan sifariş" stat-pill-inə basanda açılır, drawer
  menyusuna da əlavə olundu) — hər kartda # + tarix + tam marşrut + müştəri
  adı + WhatsApp əlaqə düyməsi. "Şikayət və təklif üçün müraciət" rəngli
  (`.btn-support`, pink→mavi gradient) düymə oldu, müştəri panelindəki eyni
  funksiya da bu stilə keçirildi və "Təklif və iradlar" adlandırıldı.
- Profildə Abunə statusunun İKİ DƏFƏ göstərilməsi bug-ı düzəldildi (hero
  stat-pill + ayrıca Abunə kartı eyni məlumatı təkrarlayırdı), bölmə
  başlıqlarına kiçik rəngli ikon-nişanlar əlavə olundu (`.section-icon`).
- **Profil şəkli yükləmə bug-ı (iOS HEIC):** istifadəçi real iPhone-da
  yüklədiyi şəkilin göstərilmədiyini bildirdi — kök səbəb: iPhone-un default
  foto formatı HEIC serverdə səssizcə rədd olunurdu, uğursuzluq halında
  UI-da heç bir xəbərdarlıq yox idi. Düzəliş: seçilən şəkil (HEIC daxil,
  Safari-nin doğma HEIC dekodlaması vasitəsilə) brauzerdə canvas ilə kiçik
  JPEG-ə çevrilib yüklənir (`Birlikde.imageToJpegBlob`), uğursuz olarsa
  aydın xəta mesajı göstərilir (əvvəllər tam səssiz idi).
- **Səhifələr arası SPA-vari naviqasiya:** istifadəçinin "hər şey saniyədən
  sürətli, səhifə yenilənmədən işləməlidir" tələbi ilə `Birlikde.initRouter()`
  əlavə olundu — bütün eyni-origin `<a>` keçidləri (drawer, hüquqi sənəd
  siyahısı, "Sifarişlərim" keçidi və s.) client tərəfdə tutulur, yalnız
  `.container` DOM məzmunu fetch+swap edilir, səhifənin öz `<script>`-i
  yenidən icra olunur, `history.pushState`/`popstate` ilə geri/irəli
  dəstəklənir. Giriş/qeydiyyat/çıxış/dil dəyişimi ŞÜURLU olaraq kənarda
  saxlanıldı (tam yenilənmə davam edir — rol-əsaslı drawer render tələb
  edir). `kurye/lovhe.php`-də SSE bağlantısı üçün `Birlikde.onPageLeave()`
  cleanup hook-u əlavə olundu ki, səhifədən SPA ilə ayrılanda bağlantı
  arxa fonda açıq qalmasın.
- Real MySQL+HTTP+Playwright ilə geniş test: heç bir real səhifə
  yenilənməsinin baş vermədiyi (window sentinel dəyəri saxlanıldı), drawer
  aktiv nişanının düzgün yeniləndiyi, geri/irəli funksionallığının, hüquqi
  sənəd zəncirinin, SSE-nin (onlayn keçid + sifariş götürmə) SPA
  naviqasiyasından sonra da qüsursuz işlədiyi, foto yükləmənin səhifə
  yenilənmədən dərhal göründüyü təsdiqləndi — 0 konsol xətası. `php -l` və
  `node --check` bütün dəyişən fayllarda səhvsiz. `sw.js` `CACHE_VERSION`
  bu sessiyada v1→v6 addım-addım artırıldı (hər CSS/JS dəyişikliyi ilə).

### 2026-07-12 (davam) — Dizayn cilası, admin mobil, çıxış bug-ı, modal bug-ı

- **Dizayn zərif toxunuşlar:** `.glass` blur/şəffaflıq effekti gücləndirildi
  (`rgba(255,255,255,0.72-0.76)` + `backdrop-filter: blur(14-16px)
  saturate(160%)`), tab/toggle-lar pill-track stilinə keçdi, kart künclərinə
  "Təcili" ribbon-nişanı (`.card-ribbon`), input sahələrinə ikon-in-field
  naxışı (`.input-icon-wrap`), marşrut göstərilməsi hər yerdə vertikal
  nöqtə-xətt stepper-ə (`Birlikde.routeStepperHtml`) keçirildi.
- **Admin panel tam mobil-responsiv edildi:** sürüşən yan-menyu (off-canvas
  drawer, `BirlikdeAdmin.initDrawer`), bütün cədvəllər üfüqi sürüşmə
  wrapper-inə alındı (`.table-wrap-admin`, overflow varsa qıraq-fade
  ipucu ilə), toolbar filtr sahələrinə stil əlavə olundu (əvvəllər
  görünməz idi — köhnə tünd-tema `rgba(...,0.04)` fonu unudulmuşdu).
- **Çıxış düyməsi bug-ı** ("heç nə baş vermir"): kök səbəb kimi köhnə
  keşlənmiş `admin.js`/`app.css` faylının brauzerdə saxlanması müəyyən
  edildi (yeni funksiya çağırışı köhnə skriptdə tapılmayıb bütün
  `<script>` blokunu dayandırırdı, çıxış handler-i heç vaxt bağlanmırdı).
  Düzəliş: `App\Core\Asset::v()` — `filemtime()`-əsaslı avtomatik
  keş-sındırma sinifi yaradıldı, bütün CSS/JS `<link>`/`<script>`
  teqlərinə (admin + app) tətbiq olundu.
- **Admin modal pop-up-ların boz/solğun görünüş bug-ı** (istifadəçi
  skrinşotla bildirdi): admin mobil dizaynında `.glass`-a əlavə olunan
  blur+şəffaflıq (`rgba(255,255,255,0.76)` + `backdrop-filter: blur`)
  `.modal`/`modal-sheet` siniflərinə də tətbiq olunurdu — modal öz tünd
  overlay-i (`rgba(26,26,46,0.45)`) üzərində oturduğundan, blur bu tünd
  fonu modalın özünə sızdırıb boz/kirli görünüş yaradırdı. Düzəliş: həm
  admin (`.modal`), həm app (`.modal-sheet`) tam qeyri-şəffaf ağ fona
  (`var(--surface)`, `backdrop-filter: none`) keçirildi — modal artıq
  frosted-glass effektinə ehtiyac duymur. Playwright ilə real DB + HTTP
  test edilib (müştəri VƏ kuryer detal modalları, həm masaüstü, həm mobil
  ölçüdə) — modal kartı təmiz ağ, overlay isə normal tündləşdirmə effekti
  kimi göründü. `sw.js` `CACHE_VERSION` v7→v8.

### 2026-07-12 (davam) — Modal-ın yuxarı hissəsi gizlənmə bug-ı (CSS containing-block)

- İstifadəçi eyni modal-ı yenidən skrinşotla göstərdi: bu dəfə fon ağ idi,
  amma kartın YUXARI hissəsi (ad, telefon, status) ekrandan kənarda qalıb
  görünmürdü. Kök səbəb tapıldı: `.admin-main` (və app tərəfdə `.container`)
  `animation: pageIn ... both` istifadə edirdi — "both" fill-mode animasiya
  bitəndən sonra son keyframe-in `transform: translateY(0)` dəyərini əbədi
  saxlayır; CSS spesifikasiyasına görə `none` olmayan istənilən `transform`
  əcdad elementi `position:fixed` övladları üçün yeni containing block edir
  — nəticədə `.modal-overlay` əsl viewport əvəzinə `.admin-main`-in kiçik
  boksuna həbs olunurdu. Düzəliş: animasiyalardan `both` çıxarıldı (vizual
  fərq yoxdur, defolt fill-mode son vəziyyəti eyni saxlayır, sadəcə
  transform əbədi qalmır). Əlavə olaraq: admin modal-larının daxili scroll
  mövqeyi (`#modalIcerik`) hər açılışda sıfırlanır (əvvəllər eyni DOM
  elementi təkrar istifadə olunduğundan əvvəlki kartın scroll mövqeyi
  qalırdı). Playwright ilə `overlayRect`/`modalRect` koordinatları
  ölçülərək təsdiqləndi (əvvəl: overlay 301px hündürlükdə həbs olunmuşdu;
  sonra: tam 700px viewport-u əhatə etdi). `sw.js` `CACHE_VERSION` v8→v9.

### 2026-07-12 (davam) — Payriff real API inteqrasiyası + admin toplu pulsuz/pullu

- İstifadəçinin tələbi: "avtomatik abunəlik" — hər AY OTOMATİK KART ÇƏKMƏ
  YOX, bitmə tarixi keçəndə bildiriş + lövhənin avtomatik bağlanması
  (bu, artıq Faza 3/5-dən bəri `cron/abunelik_yoxla.php` ilə mövcud idi,
  yalnız təsdiqləndi/sənədləşdirildi). Real dəyişiklik: ödəniş Payriff-ə
  keçirildi (əvvəlki `PayriffProvider` tam YER TUTUCU idi — saxta HMAC
  imza, real API çağırışı YOX idi).
- **PayriffProvider tam yenidən yazıldı** — rəsmi nümunə repo
  (github.com/payriff-com/payriff-examples) və istifadəçinin paylaşdığı
  əsl callback payload nümunəsi əsasında: `POST https://api.payriff.com/
  api/v2/createOrder` (`Authorization: <SECRET_KEY>` başlığı, body:
  amount/currencyType/description/language/approveURL/cancelURL/
  declineURL/merchant), cavabdan `payload.paymentUrl`+`payload.orderId`
  oxunur (Payriff öz UUID order id-ni özü yaradır — buna görə
  `OdenisService::basla()` sırası dəyişdirildi: əvvəlcə provayderdən
  sessiya alınır, YALNIZ SONRA yerli `odenisler` qeydi Payriff-in real
  order id-si ilə açılır). `approveURL=cancelURL=declineURL` eyni
  `/odenis/qayit` ünvanına göstərir (istifadəçinin Payriff dəstəyindən
  aldığı təsdiqə görə: "callback və return URL eynidir", Payriff bura
  HƏM POST payload göndərir, HƏM brauzeri paralel yönləndirir) — nəticə
  `payload.paymentStatus === 'PAID'` sahəsindən oxunur.
- **Yeni `/odenis/qayit` səhifəsi:** kuryer ödənişdən sonra bura düşür,
  spinner göstərilir, `GET /odenis/son-hal` ilə 1.5 saniyəlik polling
  edilir (webhook asinxron gələ bilər), uğurlu olduqda ✅ görünür və
  1.8 saniyə sonra avtomatik `/lovhe`-yə yönləndirilir, uğursuz olduqda
  "Yenidən cəhd et" düyməsi göstərilir. `.odenis-spinner` fırlanan dairə
  CSS-i əlavə olundu.
- **⚠ QEYD (şəffaflıq):** rəsmi Payriff sənədləşməsi (docs.payriff.com)
  bu inkişaf mühitindən şəbəkə siyasətinə görə (proxy allowlist) əlçatan
  olmadı — createOrder sorğu/cavab formatı rəsmi nümunə repo + istifadəçi
  ilə Payriff dəstəyi arasındakı yazışmadan (paylaşılan əsl callback
  payload) tərtib olundu, YÜKSƏK ETİBAR səviyyəsindədir, AMMA callback-in
  kriptoqrafik imza sxemi (əgər varsa) tam təsdiqlənə bilməyib. Hazırkı
  müdafiə: `order_id` Payriff tərəfindən yaradılan təxmin edilə bilməyən
  UUID-dir (yerli `odenisler.order_id` UNIQUE) + yalnız `gozlemede`
  statuslu uyğun qeyd varsa emal olunur (idempotent). **Canlıya keçmədən
  əvvəl real Payriff merchant hesabı ilə test kartlarla (VISA
  4000007546012078, MC 5000005541096514, 3DS icbari, OTP 123456) tam
  axın yoxlanmalıdır** — istifadəçi bunu edə bilər, mən sandbox-dan
  api.payriff.com-a çata bilmirəm (test zamanı bu, gözlənilən "CONNECT
  tunnel failed" xətası ilə təsdiqləndi, kодun özü səhvsiz idi).
- **Admin: "Hamısını PULSUZ/PULLU et" toplu düyməsi** (`kuryerler.php`,
  `AbunelikService::hamisiniDeyis`) — bir kliklə BÜTÜN kuryerlərin abunə
  vəziyyətini dəyişir. "Pulsuz": hər kuryerə 30 günlük aktiv+pulsuz dövr
  təmin edilir (yoxdursa yaradılır, varsa uzadılır) VƏ rəsmi kampaniya
  mətni ilə bildiriş göndərilir (PushService — hazırda yalnız infrastruktur
  səviyyəsində, real Web Push göndərmə hələ YER TUTUCUDUR, bax
  PushService qeydi). "Pullu": yalnız hazırda pulsuz olanlar geri
  çevrilir, qalan müddət toxunulmur. Səbəb sahəsi məcburi, audit
  jurnalına (`legal_logs`) yazılır. Real DB+HTTP+Playwright ilə test
  edildi: yeni qeyd yaratma VƏ mövcud qeydi uzatma hər iki qol, validasiya
  xətaları, UI-də təsdiq dialoqları.
- `.env.example` yeniləndi: `PAYMENT_PROVIDER=payriff` default oldu,
  `PAYRIFF_PAYMENT_URL` silindi (URL artıq kod daxilində sabit).
  `sw.js` `CACHE_VERSION` v9→v10.

### 2026-07-12 (davam) — Tam təhlükəsizlik auditi (app + admin)

- İstifadəçinin tələbi: "bütün saytı tam qorumaya almaq" — həm app.birlikde.biz,
  həm appadmin.birlikde.biz. Middleware (Session, Csrf, RateLimit, Auth,
  AdminAuth, RoleGuard), bütün Controller/Service qatı, Model-lərdəki SQL
  sorğuları, JS-də bütün `innerHTML` interpolasiyaları, fayl yükləmə/göstərmə
  yolu, nginx konfiqurasiyası sistemli şəkildə yoxlanıldı.
- **Tapılan və düzəldilən boşluqlar:**
  1. **Təhlükəsizlik başlıqları tamamilə yox idi** (CSP, X-Frame-Options,
     X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS) —
     CLAUDE.md-in özü "CSP header" tələb etsə də heç yerdə tətbiq olunmurdu.
     Yeni `App\Core\SecurityHeaders` sinifi yaradıldı, hər iki front
     controller-də çağırılır. Statik fayllar üçün `deploy/nginx/*.conf`-a
     paralel `add_header` əlavə olundu (bunun serverdə əl ilə tətbiqi
     lazımdır — `nginx -t && systemctl reload nginx`).
  2. **Qeydiyyat endpoint-i (`/qeydiyyat`) rate-limitsiz idi** — sərhədsiz
     avtomatik hesab yaratma/spam mümkün idi. `RateLimit` middleware əlavə
     olundu (5 cəhd/15 dəq, digər endpoint-lərlə eyni).
  3. **Giriş timing side-channel** — mövcud olmayan telefon nömrəsi üçün
     `password_verify()` heç çağırılmırdı (bcrypt hesablama xərci yox idi),
     mövcud hesabın səhv parolu isə tam bcrypt vaxtı çəkirdi — bu fərq
     nəzəri olaraq hansı nömrələrin qeydiyyatdan keçdiyini ayırd etməyə
     imkan verirdi. Düzəliş: hər iki halda da sabit dummy-hash ilə
     `password_verify` çağırılır (`AuthService::DUMMY_HASH`,
     `AdminAuthService::DUMMY_HASH`) — cavab vaxtı sabitləşdi. Həm app, həm
     admin girişində.
- **Təsdiqlənən, DƏYİŞDİRİLMƏYƏN sahələr (audit zamanı yoxlanıldı, boşluq
  tapılmadı):** SQL injection (bütün sorğular PDO prepared, `EMULATE_PREPARES
  =false`), XSS (server tərəfdə `htmlspecialchars`, client tərəfdə
  `escapeHtml` — bütün `innerHTML` interpolasiyaları yoxlanıldı, boş yer
  tapılmadı), CSRF (bütün yazma sorğularında), IDOR (rol middleware-i ilə
  yanaşı sahiblik DB sorğusunun özündə də var, məs. `Sifaris::cancel()`),
  fayl yükləmə (`is_uploaded_file`, real MIME + `getimagesize` yoxlaması,
  təsadüfi ad), fayl göstərmə (`basename()` + sərt regex whitelist, path
  traversal qorunub), open-redirect (bütün `Response::redirect()` çağırışları
  sabit daxili yollara), CORS (heç bir başlıq yoxdur — defolt olaraq
  same-origin, düzgün), admin sessiya izolyasiyası (ayrı cookie adı, qısa
  idle-timeout, remember-me yoxdur), parol hash (`PASSWORD_DEFAULT`/bcrypt).
- Real DB+HTTP+Playwright ilə geniş test: CSP başlığı ilə HEÇ bir konsol
  xətası/CSP pozuntusu yaranmadı (qeydiyyat, giriş, drawer, SSE canlı lövhə,
  onlayn toggle, admin panel — hamısı sınaqdan keçirildi), rate-limit dəqiq
  5-ci cəhddə 429 qaytardı, giriş enumeration-ı eyni ümumi mesajla bağlandı.

### 2026-07-12 (davam) — Real Web Push göndərmə (RFC 8291 + VAPID)

- İstifadəçinin tələbi: "real bildiriş hissəsini həll edək" — indiyədək
  `PushService` yalnız infrastruktur idi (niyyət `storage/logs`-a yazılırdı,
  faktiki brauzerə heç nə getmirdi). İstifadəçiyə iki yol təklif olundu
  (tam native PHP-də sıfırdan RFC 8291/8292 kriptoqrafiyası YOXSA kiçik,
  sənaye-standart Composer kitabxanəsi) — istifadəçi ikincini seçdi.
- **Composer ilk dəfə layihəyə əlavə olundu** (yalnız bu bir məqsəd üçün,
  layihənin qalan hissəsi native PHP olaraq qalır) — `minishlink/web-push`
  v10.1.0 (yalnız `curl`/`json`/`mbstring`/`openssl` genişlənmələri tələb
  edir, GMP/BCMath MƏCBURİ deyil). `vendor/` əvvəldən `.gitignore`-da idi
  (gözlənilirmiş) — `composer.lock` commit olunur, serverdə `composer
  install --no-dev` işə salınmalıdır (`git pull` təkbaşına kifayət etmir).
- `bootstrap.php`-ə `vendor/autoload.php` müdafiəli (`is_file` yoxlaması ilə)
  qoşuldu — mövcud `App\` avtoloader-inə əlavə, onu əvəz etmir.
- `PushService::gonder()` tam yenidən yazıldı: `push_abuneler`-dəki hər
  abunəlik üçün RFC 8291 şifrələnmiş + VAPID imzalı bildiriş göndərilir,
  cavab uğursuz olub subscription bitibsə (404/410) həmin sətir avtomatik
  `push_abuneler`-dən silinir.
- **Tapılan real bug:** `PushService` əvvəllər hər `SifarisService` (demək
  ki, demək olar hər kuryer sorğusu) konstruktorunda HƏVƏSLƏ `WebPush`
  müştərisi yaradırdı. Kitabxana GMP/BCMath yoxdursa performans
  xəbərdarlığı (`trigger_error`) verir — `App\Core\ErrorHandler`
  `APP_DEBUG=true` olduqda İSTƏNİLƏN notice/warning-i istisnaya çevirib
  atır, ona görə bu, push-la heç əlaqəsi olmayan onlarla endpoint-i 500
  ilə çökürdü (test mühitində real DB+HTTP ilə aşkarlandı). Düzəliş:
  `WebPush` müştərisi TƏNBƏL yaradılır (yalnız `gonder()` faktiki
  çağırılanda, boş abunəlik siyahısında heç yaradılmır), tikinti `@` ilə
  örtülür (kitabxananın öz `error_reporting()` yoxlamasına uyğun səhih
  susdurma üsulu). Production-da `php-bcmath` quraşdırmaq bu xəbərdarlığı
  kökündən aradan qaldırır (tövsiyə, məcburi deyil).
- **Test:** real EC P-256 açar cütü ilə saxta-amma-kriptoqrafik-cəhətdən-
  düzgün push abunəliyi DB-yə əlavə edilib `gonder()` birbaşa çağırıldı —
  ECDH razılaşma açarı, AES-128-GCM şifrələmə və VAPID JWT imzası
  UĞURLA hesablandı, real HTTPS POST `fcm.googleapis.com`-a çatdı, Google
  `410 Gone` qaytardı (gözlənilən — saxta endpoint ID) və sətir avtomatik
  silindi. Brauzerin öz `pushManager.subscribe()` çağırışı bu sandbox-dan
  şəbəkə yolu olmadığı üçün test edilə bilmədi (eyni səbəb Payriff API
  sınağı ilə) — real cihazda/serverdə yoxlanmalıdır, amma server-tərəf
  kriptoqrafiya + çatdırma zənciri tam təsdiqləndi.
- `sw.js`-dəki "göndərmə tərəfi YER TUTUCUDUR" qeydi silindi (artıq doğru
  deyil). `composer.json`/`composer.lock` yeni fayllar, `bootstrap.php`
  və `PushService.php` yeniləndi.

### 2026-07-12 (davam) — Bildiriş düyməsi bug-ı: səssiz uğursuzluq

- İstifadəçi VAPID+Composer serverə tam quraşdırdıqdan sonra ("Bildirişlərə
  icazə ver" düyməsini) sınadı: "Bildiris duymesi saytdada pwa dada islemir".
  Kök səbəb: `kurye/profil.php`-dəki düymə handler-i `Birlikde.subscribeToPush()`-
  un nəticəsini TAMAMILƏ NƏZƏRƏ ALMIRDI (`await ...;` — dəyər atılırdı) —
  brauzer icazəni rədd etsə (və ya istifadəçi əvvəllər səhvən "İcazə vermə"
  seçmişdisə, hansı ki bu halda `Notification.requestPermission()` popup
  belə göstərmədən dərhal "denied" qaytarır), səhifədə HEÇ NƏ baş vermirdi —
  nə uğur mesajı, nə xəta. Bu, "düymə işləmir" hissi yaradırdı, baxmayaraq ki
  arxa-plan kodu düzgün işləyirdi.
- Düzəliş: `Birlikde.subscribeToPush()` (`app.js`) try/catch ilə tam əhatə
  olundu, dəqiq nəticə (`denied`/`error`/`supported:false`) qaytarır.
  `profil.php`-də düymənin yanına görünən uğur (`.success-box`, yeni CSS
  sinif) və xəta (`.error-box`, mövcud naxış) qutuları əlavə olundu — hər
  bir hal (dəstəklənmir, icazə rədd edildi, server xətası, uğur) indi aydın
  mesajla göstərilir. 4 yeni i18n açarı (az/ru/en) əlavə olundu.
- Playwright ilə real DB+HTTP test edildi: icazə verilmədiyi ssenaridə
  "İcazə verilmədi — brauzer ayarlarından bu sayt üçün bildirişə icazə
  verməlisən." mesajı düzgün göründü (əvvəllər tam səssiz idi) — bu, əsl
  bug-ın diaqnozunu təsdiqlədi. `sw.js` `CACHE_VERSION` v10→v11.
- **iOS Safari xüsusi mesajı:** istifadəçi real iPhone-da sınayanda "Bu
  brauzer bildirişləri dəstəkləmir" mesajını gördü — bu, BUG DEYİL, Apple-ın
  öz qərarıdır (Safari-də Push API yalnız sayt "Ana ekrana əlavə et" ilə
  quraşdırılandan sonra mövcuddur, CLAUDE.md bölmə 5-də əvvəldən
  sənədləşdirilib). Amma köhnə mesaj bunu izah etmirdi. Düzəliş:
  `Birlikde.subscribeToPush()`-a iOS Safari + qeyri-standalone aşkarlama
  əlavə olundu (`navigator.standalone` / `display-mode: standalone` +
  UA yoxlaması) — bu vəziyyətdə dəqiq təlimatlı mesaj göstərilir ("Safari-də
  paylaşma düyməsinə basıb Ana ekrana əlavə et"). Playwright ilə saxta iOS
  Safari UA + `PushManager` silinməsi ilə simulyasiya edilib, düzgün mesaj
  təsdiqləndi. `sw.js` `CACHE_VERSION` v11→v12.
- **İstifadəçi tələbi: "Bu yazı bildirişi elə etki pop up açılsın və müasir
  şəkildə izah etsin"** — düz mətn xəta qutusu əvəzinə, iOS quraşdırma
  təlimatı indi mövcud `.modal-overlay`/`.modal-sheet` naxışı ilə (tam
  qeyri-şəffaf ağ kart, bax əvvəlki modal bug-ı düzəlişi) rəngli nişan-
  dairəli 3 addımlı pop-up kimi göstərilir (📱 ikon + "Bildirişləri
  aktivləşdirmək üçün" başlıq + 1/2/3 nömrələnmiş mavi dairələr + "Bağla").
  Yeni `.step-list`/`.step-item`/`.step-num`/`.step-text` CSS sinifləri
  əlavə olundu. Playwright ilə saxta iOS UA-la vizual təsdiqləndi —
  pop-up düzgün açılır/bağlanır, dizayn sisteminə tam uyğundur.
  `sw.js` `CACHE_VERSION` v12→v13.

### 2026-07-12 (davam) — Yeni sifariş push bildirişi (əsl boşluq tapıldı)

- İstifadəçi VAPID+Composer+iOS düzəlişlərindən sonra real sifariş verib
  yoxladı: "Bildiris yenə gəlmədi... girəndə görürəm ki canlı lövhədə
  sifariş var". Kök səbəb: `PushService::gonder()` mövcud idi və düzgün
  işləyirdi (əvvəlki sessiyada tam təsdiqlənmişdi), AMMA sifariş
  yaradılanda ONU HEÇ KIM ÇAĞIRMIRDI — `SifarisService::yarat()`-da push
  tetikləyicisi ümumiyyətlə yox idi. Mövcud yeganə push nöqtələri:
  ödəniş uğuru, abunə xəbərdarlığı (cron), admin toplu kampaniya, VƏ
  sifariş GÖTÜRÜLƏNDƏ müştəriyə "Daşıyıcı tapıldı" — kuryerə "yeni sifariş
  var" bildirişi heç vaxt olmayıb. SSE canlı lövhə isə YALNIZ kuryer
  tətbiqi açıq saxlayanda işləyir — tətbiq bağlı olanda heç nə
  bilmirdi, elə bu şikayətin əsl kökü idi.
- **Düzəliş:** `Kurye::rayonaVeTipeUygunlar($rayonId, $tip, $olcuId)` yeni
  model metodu — sifarişin götürülmə rayonuna VƏ tipinə (kurye/yükdaşıma,
  yükdaşımada ölçü də) uyğun bütün kuryerləri tapır (`kurye_bolgeler` +
  `users.rol` + lazım olsa `dasiyici_olculeri` join-ləri ilə).
  `SifarisService::yarat()` sifariş yaradılan kimi bu siyahını çəkib, hər
  namizəd üçün aktiv abunə yoxlayır (`kuryeAbunesiAktivdirmi` — mövcud
  metod), keçənlərə "Yeni sifariş!" (təcili olarsa "Təcili yeni
  sifariş!") push göndərir, `/lovhe`-yə keçid linki ilə.
  **Şüurlu qərar: onlayn/offline statusundan ASILI OLMAYARAQ göndərilir**
  — çünki push-un məqsədi elə budur ki, tətbiqi bağlı/offline olan
  kuryeri işə çağırsın; yalnız onlayn olanlara göndərmək bütün funksiyanı
  mənasız edərdi.
- Real DB+HTTP ilə tam test edildi: real EC açarla saxta-amma-kriptoqrafik-
  düzgün push abunəliyi olan OFFLINE kuryer üçün sifariş yaradılanda real
  HTTPS FCM-ə çatdı (410 Gone — saxta endpoint, gözlənilən), fərqli
  rayonda olan ikinci kuryer üçün isə HEÇ bir göndəriş cəhdi olmadı
  (rayon filtri düzgün işləyir, təsdiqləndi).
