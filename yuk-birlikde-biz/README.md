# Yük.Birlikdə.biz

Müştəriləri və yükdaşıma sürücülərini birləşdirən rəqəmsal elan və təklif platforması (PWA). Platforma yük daşımır, qiymət müəyyən etmir, komissiya hesablamır və tərəflər arasındakı razılaşmanın iştirakçısı deyil — bax "Qızıl Qayda", tam layihə sənədi.

**Stek:** Native PHP 8.3 · MySQL 8.0 · Nginx · Vanilla JavaScript (SPA-shell, build addımı yoxdur) · SSE · Web Push · Payriff. Framework, Composer və build tool yoxdur.

## Quraşdırma (lokal inkişaf)

```bash
cp .env.example .env   # DB məlumatlarını doldurun
mysql -u root -p -e "CREATE DATABASE yuk_birlikde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
# --default-character-set=utf8mb4 vacibdir — olmasa Azərbaycan hərfləri mysql
# client-in defolt latin1 kodlaşdırması ilə import zamanı korlanır.
for f in migrations/*.sql; do mysql --default-character-set=utf8mb4 -u root -p yuk_birlikde < "$f"; done

php app/generate_vapid_keys.php   # bir dəfəlik Web Push açarları (app/vapid_keys.php — git-ə düşmür)

cd public
# PHP_CLI_SERVER_WORKERS vacibdir — olmasa uzunömürlü SSE bağlantıları (/sse/stream.php)
# builtin server-in tək worker-ini tutub qalan bütün sorğuları bloklayır.
PHP_CLI_SERVER_WORKERS=16 php -S 0.0.0.0:8000 router-dev.php
```

Production-da `nginx.conf.example` faylındakı rewrite qaydaları istifadə olunur (pretty API URL-ləri, `/uploads/` statik xidməti, SPA fallback).

### Admin paneli (yukadmin.birlikde.biz)

Ayrı server-render PHP tətbiqdir, eyni DB-ni istifadə edir (bax "Fayl strukturu").

```bash
php admin/app/create_admin.php <username> <email> <parol>   # ilk admin hesabı
cd admin/public
php -S 0.0.0.0:8001
```

Cron (production crontab):

```
*/5 * * * * php /var/www/yuk-birlikde-biz/app/cron/expire_orders.php
* * * * *   php /var/www/yuk-birlikde-biz/app/cron/reminders.php
0 3 * * *   php /var/www/yuk-birlikde-biz/app/cron/cleanup_events.php
0 9 * * *   php /var/www/yuk-birlikde-biz/app/cron/subscription_notices.php
* * * * *   php /var/www/yuk-birlikde-biz/app/cron/broadcasts.php
```

## Fayl strukturu

```
app/            Backend nüvə modulları (config, db, auth, csrf, settings, ratelimit, texts, image)
migrations/     Nömrələnmiş SQL miqrasiya faylları (bütün cədvəllər + seed data)
public/
  index.php     SPA-shell giriş nöqtəsi
  api/v1/       Endpoint faylları
  assets/       CSS token/komponentlər, JS router/api/sse/store, ekranlar
  e/            Public paylaşım səhifəsi (/e/{slug}, OG meta teqləri)
  og/           OG kart generator endpoint-i (/og/{slug}.png)
admin/          Admin paneli — ayrı server-render PHP tətbiq, eyni DB (Hissə 10)
  app/          admin_auth.php, admin_layout.php, create_admin.php
  public/       Giriş + bütün admin səhifələri (dashboard, customers, orders, ...)
storage/        uploads/, logs/, backups/, og/ (git-ə düşmür)
```

## Faza vəziyyəti (Hissə 13 — İcra Planı)

- [x] **Faza 1 — Təməl**: bütün DB miqrasiyaları · settings modulu · SPA shell + router + API/SSE client skeletləri · dizayn tokenləri · komponent kitabxanası (Hissə 2.5) · Splash → Telefon → check-phone → PIN/Qeydiyyat vahid axını (operator sheet, PinPad, rol seçimi, bütün addımlar) · sessiya sistemi · CMS səhifələri + razılıq.
- [x] **Faza 2 — Elan dövriyyəsi**: 6 addımlıq elan yaratma + şəkil yükləmə + icmal · YK nömrələmə + slug · statuslar + expire cron · sürücü lenti (adi yüklənmə, scope/filtr) · elan detalları (hər iki baxış) · təklif sheet-i + dəyiş/geri çək · Təkliflərim ekranı · seçim (atomik) → nömrə açılışı → Danışıq Gedir → Bağla/Ləğv et → yenidən açılma → imtina · qiymətləndirmə · Elanlarım tabları · Bildirişlər ekranı · Profil (hər iki rol, bildiriş ayarları, şikayət, hesab silmə).
- [x] **Faza 3 — Real-time (SSE)**: events cədvəli + publish helper + `/sse/stream.php` (kanal icazələri, Last-Event-ID catch-up, 25s heartbeat) · lentdə slide-down/collapse + "N yeni elan" düyməsi · müştəridə canlı təkliflər (listing:{id}) · reconnect + visibility sync (iOS arxa fon) · bağlantı statusu zolaqları · xatırlatma cron-u (15/30/60/1440 dəq, settings-dən) · events təmizləmə cron-u.
- [x] **Faza 4 — PWA + Push**: manifest + ikonlar + splash rəngləri · Service Worker (app-shell precache, API network-only + offline fallback, versiya yeniləmə toast-ı) · Hissə 2.6 qadağaları (pull-to-refresh, long-press, tap-highlight) · gecikmiş bildiriş icazəsi axını (izah sheet-i, 7 gün təkrar) · install prompt (Android beforeinstallprompt + iOS təlimat, 3 gün/maks 3 dəfə) · native Web Push (VAPID + aes128gcm şifrələmə, kənar asılılıqsız) + `/api/v1/push` + bütün mövcud bildiriş hadisələri üçün avtomatik göndəriş · marşrut izləmə (`/api/v1/routes`, Profil ekranı) + uyğun yeni elanda push.
- [x] **Faza 5 — Abunə**: rejim toggle məntiqi (`subscription_mode` free/paid, `/api/v1/offers` bu şərtlə kilidlənir) · Abunə səhifəsi (`/abune`) — status/qiymət canlı, ödəniş tarixçəsi · native Payriff V3 inteqrasiyası (`app/payriff.php` — createOrder + getOrderInformation, kənar kitabxanasız) · `/api/v1/subscription/{status,checkout,payriff-callback}` · webhook idempotent və body-yə etibar etmir — Payriff-in öz serverindən (gizli açarımızla) real statusu təsdiqləyir · ödəniş uğurlu olduqda dərhal aktivləşmə + SSE `subscription.activated` + push · abunə bitmə cron-u (3 gün/1 gün əvvəl xəbərdarlıq + bitmə keçidi, `subscription.expired`) · admin əl ilə abunə vermə üçün backend hazır (`subscription_grant()`, Faza 6-da UI-a bağlanacaq).

  > **Qeyd:** Real Payriff mərçant açarı sandbox-da mövcud deyil — inteqrasiya `docs.payriff.com`/rəsmi nümunələr əsasında yazılıb və yerli mock Payriff serveri ilə tam axın (checkout → webhook → aktivləşmə → idempotentlik) end-to-end doğrulanıb. Production-da yalnız `.env`-də `PAYRIFF_SECRET_KEY` təyin olunmalıdır.
- [x] **Faza 6 — Admin panel** (`admin/` — ayrı server-render tətbiq, eyni DB, `admin_sessions`/`admin_users` üzərindən müstəqil giriş): giriş + uğursuz cəhd bloklaması · canlı Dashboard (5s polling) · Müştəri/Sürücü idarəetməsi (axtarış/filtr, blok/tam blok/sil, sürücüdə avtomobil redaktəsi + əl ilə abunə ver/uzat/azalt/ləğv et + tarixçə) · Elan idarəetməsi (bağla/yenidən aktiv et/sil, real-time lentdən silinmə) · Təklif idarəetməsi (sil, real-time geri çəkilmə) · Abunə parametrləri (rejim/qiymət/müddət/aktivləşmə qaydası, dəyişəndə SSE `subscription.mode_changed`) · Payriff ödəniş tarixçəsi (raw JSON baxışı) · Banner CRUD (şəkil yükləmə, 4 yerdə render — `home_top`/`feed`/`profile`/`subscription`, klik sayğacı, SSE `banner.updated`) · Bildiriş göndərmə (auditoriya seçimi, dərhal/planlaşdırılmış, `cron/broadcasts.php`) · Şikayət idarəetməsi (cavab → istifadəçiyə bildiriş, status axını) · CMS redaktoru (`pages` cədvəli) · Analitika (tarix aralığı, canvas qrafiklər, kənar kitabxanasız) · Sistem parametrləri (qeydiyyat/PIN/TTL/xatırlatma/sessiya/bildiriş/PWA/baxım + operator/avtomobil/ölçü/yük növü arayış cədvəlləri) · Sayt konfiqurasiyası (ad/loqo/favicon/əlaqə/sosial/copyright) · Backup (mysqldump + uploads arxivi, yüklə, ikiqat təsdiqlə bərpa) · Xəta/Audit jurnalları · Qlobal axtarış.

  > **Qeyd:** İlk admin hesabı `php admin/app/create_admin.php <username> <email> <password>` ilə yaradılır. Bütün mutasiya əməliyyatları `audit_logs`-a yazılır.
- [x] **Faza 7 — Paylaşım + Cilalama**: OG kart generatoru (`app/og_generator.php`, GD ilə brend qradiyent + marşrut + yük növü + tarix, bundled Inter (Regular/Bold, `app/fonts/`) ilə Azərbaycan hərfləri düzgün render olunur — veb tətbiqlə eyni font, tam brend uyğunluğu) + `GET /og/{slug}.png` (bir dəfə generasiya, sonra statik fayl kimi keşlənir) · public `/e/{slug}` paylaşım səhifəsi (OG meta teqləri, qeydiyyatsız istifadəçiyə elan önizləməsi — nömrəsiz, daxil olmuş istifadəçi birbaşa SPA-ya yönləndirilir) · Elan detalında paylaş ikonu (Web Share API, dəstəklənmirsə keçid kopyalanır) · `robots.txt` + meta description (Lighthouse SEO 100) · **Inter fontları mənbələndirildi** (Regular/Medium/SemiBold/Bold woff2, `public/assets/fonts/`) — SIL OFL 1.1, upstream "latin" + "latin-ext" subsetlərinin `fonttools merge` ilə birləşdirilməsi (hər ikisi ayrı-ayrılıqda Azərbaycan hərflərinin fərqli alt-dəstini əhatə edir; birləşmədən sonra ə/ı/ş/ç/ö/ü/ğ/Ə/İ/Ş/Ç/Ö/Ü/Ğ-nin hamısı fontTools cmap yoxlaması ilə təsdiqlənib) · Hissə 14 test planının kritik bəndlərinin yenidən doğrulanması: təklif seçimi atomikliyi (paralel sorğu ilə təsdiqləndi), nömrə gizliliyi, CSRF, rate limit, fayl yükləmə hücum ssenarisi (saxta PHP faylı rədd edildi), 220+ paralel SSE bağlantısı yük testi.

  > **Tapılan və düzəldilmiş bug:** `/sse/stream.php` başlıqlardan sonra heç bir output göndərmirdi — bu, ilk hadisə və ya 25 saniyəlik heartbeat-ə qədər `EventSource`-un "open" hadisəsini görməməsinə səbəb olurdu (yük testi zamanı aşkarlanıb: 220 paralel bağlantıdan heç biri 8 saniyə ərzində qoşulma təsdiqi almadı). Bağlantı açılan kimi boş SSE şərhi göndərilməsi ilə düzəldilib — indi bağlantı demək olar ki, dərhal qurulur.

  > **Qeyd:** Real cihaz testləri (iOS Safari, Android Chrome) sandbox mühitində fiziki cihaz olmadığından icra edilə bilməyib — Faza 4-də PWA/SW/push funksionallığı Chromium-da ayrıca yoxlanılıb. Lighthouse-un ayrıca "PWA" kateqoriyası alətin v10+ versiyalarında çıxarılıb (əl ilə yoxlama siyahısına keçirilib) — manifest/SW/installability Faza 4-də funksional test edilib.

## Qızıl Qayda

Platforma yalnız müştərilər və yükdaşıma sürücüləri arasında əlaqə yaradan rəqəmsal vasitəçidir. Qiymət hesablama, təklif sıralama və "tövsiyə" tipli funksiyalar qəti qadağandır — seçim hüququ tamamilə müştəriyə məxsusdur.
