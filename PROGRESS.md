# BİRLİKDƏ — Progress Log

Cari status izləmə jurnalı. Hər faza bitəndə burada yenilənir.

## Status

- **Cari faza:** Faza 5 tamamlandı — "Növbəti fazaya keç" əmri gözlənilir (Faza 6 üçün)

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
| 6 | PWA və Cilalama | ⏳ Gözləyir | — |
| 7 | Hüquqi (sonra) | ⏳ Gözləyir | — |

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
