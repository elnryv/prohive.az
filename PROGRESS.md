# BİRLİKDƏ — Progress Log

Cari status izləmə jurnalı. Hər faza bitəndə burada yenilənir.

## Status

- **Cari faza:** Faza 0 tamamlandı — "Növbəti fazaya keç" əmri gözlənilir (Faza 1 üçün)

## Faza Cədvəli

| Faza | Ad | Status | Tarix |
|---|---|---|---|
| — | Yaddaş sistemi (CLAUDE.md/PROGRESS.md) qurulması | ✅ Tamamlandı | 2026-07-11 |
| 0 | Təməl (VPS, qovluq, Core, config, error handler) | ✅ Tamamlandı | 2026-07-11 |
| 1 | Verilənlər Bazası (migrations, seed) | ⏳ Gözləyir | — |
| 2 | Autentifikasiya (qeydiyyat/giriş/sözləşmə/middleware) | ⏳ Gözləyir | — |
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

## Qeydlər / Açıq Suallar (fazalar arası unudulmamalı)

- Hüquqi mətnlər (Müqavilə/Məxfilik) hələ yoxdur — Faza 7-yə saxlanılıb, infrastruktur
  (checkbox + `sozlesme_qebul`/`sozlesme_tarix` + `legal_logs`) Faza 2-də hazırlanmalıdır.
- Sumqayıt ərazi siyahısı seed məlumatı canlıya keçmədən əvvəl rəsmi mənbədən təsdiqlənməlidir.
- Ödəniş provayderi (Birbank/Payriff) seçimi Faza 5-də konkretləşdiriləcək — kod
  provayder-neytral interfeyslə yazılır.
