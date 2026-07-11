# BİRLİKDƏ — Progress Log

Cari status izləmə jurnalı. Hər faza bitəndə burada yenilənir.

## Status

- **Cari faza:** Faza 2 tamamlandı — "Növbəti fazaya keç" əmri gözlənilir (Faza 3 üçün)

## Faza Cədvəli

| Faza | Ad | Status | Tarix |
|---|---|---|---|
| — | Yaddaş sistemi (CLAUDE.md/PROGRESS.md) qurulması | ✅ Tamamlandı | 2026-07-11 |
| 0 | Təməl (VPS, qovluq, Core, config, error handler) | ✅ Tamamlandı | 2026-07-11 |
| 1 | Verilənlər Bazası (migrations, seed) | ✅ Tamamlandı | 2026-07-11 |
| 2 | Autentifikasiya (qeydiyyat/giriş/sözləşmə/middleware) | ✅ Tamamlandı | 2026-07-11 |
| 3 | Sifariş və SSE (race qoruması, WhatsApp) | ⏳ Gözləyir | — |
| 4 | Admin Paneli | ⏳ Gözləyir | — |
| 5 | Abunə və Ödəniş (adapter, webhook) | ⏳ Gözləyir | — |
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

## Qeydlər / Açıq Suallar (fazalar arası unudulmamalı)

- Hüquqi mətnlər (Müqavilə/Məxfilik) hələ yoxdur — Faza 7-yə saxlanılıb, infrastruktur
  (checkbox + `sozlesme_qebul`/`sozlesme_tarix` + `legal_logs`) Faza 2-də hazırlanmalıdır.
- Sumqayıt ərazi siyahısı seed məlumatı canlıya keçmədən əvvəl rəsmi mənbədən təsdiqlənməlidir.
- Ödəniş provayderi (Birbank/Payriff) seçimi Faza 5-də konkretləşdiriləcək — kod
  provayder-neytral interfeyslə yazılır.
