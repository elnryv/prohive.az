# BİRLİKDƏ — Layihə Yaddaşı (CLAUDE.md)

> Bu fayl Claude üçün layihə yaddaşıdır. Hər sessiyada əvvəlcə bu fayl və `PROGRESS.md` oxunur.
> Mənbə sənəd: `Birlikde_Layihe_v2.docx` (istifadəçi tərəfindən yükləndi, tam oxunub, bu fayla köçürülüb).

## 1. Layihə Xülasəsi

**Birlikdə** (app.birlikde.biz) — kuryerlərlə çatdırılmaya ehtiyacı olan müştəriləri
əlaqələndirən **vasitəçilik** platformasıdır. Platforma çatdırılmanı icra etmir,
ödənişə qarışmır, məsuliyyət daşımır — yalnız iki tərəfi WhatsApp (Click-to-Chat)
üzərindən əlaqələndirir. Razılaşma/ödəniş/çatdırılma tərəflər arasında baş verir.

- **Gəlir modeli:** yalnız kuryerdən aylıq abunə haqqı. Müştəri üçün tam pulsuz.
  Platforma çatdırılma pulundan komissiya almır. Əlavə gəlir: ana səhifə banner reklamları.
- **Subdomenlər:**
  - `app.birlikde.biz` — Müştəri + Kuryer (eyni giriş, `users.rol`-a görə yönləndirmə)
  - `appadmin.birlikde.biz` — Admin (ayrı, izolyasiya olunmuş panel)
- **Əsas prinsiplər:** PWA (iOS+Android), SSE ilə real-time, Native PHP 8.3 (framework yox),
  MySQL 8.0, Hostinger VPS, çoxdilli (AZ/RU/EN), OLED qara (#000000) + glassmorphism dizayn,
  bütün əlaqə WhatsApp Click-to-Chat üzərindən.
- **Hüquqi qeyd:** İstifadəçi Müqaviləsi / Məxfilik Siyasəti mətnləri HƏLƏ yoxdur (Faza 7-yə
  saxlanılıb, hüquqşünas gözlənilir). Amma checkbox + `sozlesme_qebul`/`sozlesme_tarix` + audit
  (legal_logs) infrastrukturu kodda BAŞDAN hazır olmalıdır.

## 2. Texnologiya Yığını

| Qat | Texnologiya | Qeyd |
|---|---|---|
| Server/OS | Ubuntu (Hostinger VPS) | Nginx + PHP-FPM 8.3 |
| Backend | Native PHP 8.3 | Framework yox, **hər faylda `declare(strict_types=1)`** |
| DB | MySQL 8.0 | InnoDB, utf8mb4_unicode_ci, tranzaksiyalar |
| Real-time | SSE (Server-Sent Events) | Server → kuryer bir-istiqamətli push |
| Frontend | Vanilla JS + PWA | Service Worker, manifest, offline dəstəyi |
| Ödəniş | Adapter (Birbank/Payriff) | `PaymentProvider` interfeysi, provayder-neytral |
| Giriş | Telefon nömrəsi + parol (`password_hash`) | **OTP/SMS YOXDUR** |
| Əlaqə | WhatsApp Click-to-Chat | `wa.me/994XXXXXXXXX?text=...` linkləri |

### Qovluq Strukturu (hədəf)
```
/var/www/birlikde/
├── public/              # Nginx document root
│   ├── index.php        # Front controller (app)
│   ├── admin.php        # Admin giriş nöqtəsi (və ya ayrı vhost)
│   ├── sw.js             # Service Worker (PWA)
│   ├── manifest.json     # PWA manifest
│   └── assets/           # css, js, ikonlar, dil faylları
├── app/
│   ├── Core/             # Router, DB (PDO), Session, Request, Response
│   ├── Controllers/      # Musteri, Kurye, Admin, Auth, Sifaris
│   ├── Models/           # User, Kurye, Sifaris, Abunelik, Banner, Sehir
│   ├── Middleware/        # Auth, RoleGuard, CsrfGuard, RateLimit
│   ├── Services/          # SseService, PaymentService
│   ├── Providers/         # PaymentProvider (adapter)
│   └── Views/              # Şablonlar + dil (az/ru/en)
├── config/               # .env oxuyan konfiqurasiya
├── storage/              # loglar, cache, sessiya, banner şəkilləri
├── database/             # migrations, seed (şəhər/rayon)
└── cron/                 # sifaris_temizle.php, abunelik_yoxla.php
```

## 3. Aktyorlar

| Aktyor | Təsvir | Əsas səlahiyyət |
|---|---|---|
| Müştəri | Çatdırılmaya ehtiyacı olan şəxs | Sifariş yarat/ləğv et/tarixçəyə bax |
| Kuryer | Abunəli çatdırılma icraçısı (adi VƏ ya yükdaşıma) | Lövhə, sifariş götür, onlayn/offline |
| Admin | Platforma operatoru | İstifadəçi/sifariş idarəsi, abunə, banner, bloklama |
| Sistem (Cron) | Avtomatik proseslər | Sifariş təmizləmə, abunə yoxlaması, xəbərdarlıq |

`users.rol` ENUM: `musteri | kurye | yukdasima` (kuryer və yükdaşıma eyni cədvəldə,
rolla fərqlənir; ikisi də `kuryeler` cədvəlində əlavə məlumata malikdir).

**Qeydiyyat:** yalnız telefon+parol, OTP/SMS yoxdur. 3 qeydiyyat forması: müştəri,
kuryer (adi), yükdaşıma (ağır yük — ölçü kateqoriyaları XS-Mega çoxlu seçim).
Sözləşmə checkbox HƏR ÜÇÜNDƏ MƏCBURİDİR (`sozlesme_qebul`, `sozlesme_tarix`).
Ərazi seçimi qeydiyyatda YOX, sonra profildən edilir.

## 4. Verilənlər Bazası (icmal)

Bütün cədvəllər InnoDB + utf8mb4_unicode_ci. Tam DDL orijinal sənəddə (bölmə 4) və
Faza 1 tamamlanınca `database/migrations/`-da olacaq.

- `users` — ümumi hesab (müştəri/kuryer/yükdaşıma), `rol`, `sozlesme_qebul/tarix`
- `sessiyalar` — remember-me token (müştəri/kuryer üçün kalıcı giriş)
- `kuryeler` — kuryer/yükdaşıma əlavə məlumat (1—1 users), `neqliyyat`, `onlayn`, `tamamlanan`
- `yukdasima_olculeri` — ölçü kateqoriyaları seed (XS,S,M,L,XL,XXL,Mega)
- `dasiyici_olculeri` — daşıyıcının seçdiyi ölçülər (N—N kuryeler↔ölçü)
- `sehirler` / `rayonlar` — ərazi iyerarxiyası (ad_az/ad_ru/ad_en), seed: Bakı+Sumqayıt
- `kurye_bolgeler` — kuryerin xidmət etdiyi rayonlar (N—N)
- `sifarisler` — nüvə cədvəl; `status` ENUM(axtarisda,goturulub,tamamlandi,legv,passiv),
  `version` (optimistic lock / race qoruması üçün əsasdır)
- `abunelikler` / `odenisler` — kuryer abunə dövrləri, ödəniş əməliyyatları (idempotent `order_id`)
- `bannerler` — reklam bannerləri (yol DB-də, şəkil storage-da)
- `ayarlar` — global toggle (məs. `abune_rejimi`)
- `legal_logs` — append-only audit jurnalı (UPDATE/DELETE tətbiq səviyyəsində qadağan)
- `push_abuneler` — Web Push (VAPID) abunəlikləri

## 5. Əsas Biznes Qaydaları (unudulmamalı)

- **Race qoruması (6.3):** sifariş götürmə atomic conditional UPDATE ilə:
  `UPDATE sifarisler SET kurye_id=:k, status='goturulub', version=version+1
  WHERE id=:id AND status='axtarisda' AND kurye_id IS NULL`. `rowCount()===1` → uğur.
- **SSE (6.2):** `session_write_close()` MÜTLƏQ çağırılmalı (əks halda sessiya kilidi bloklayır).
  Nginx: `proxy_buffering off`, `fastcgi_read_timeout` artırılmalı.
- **Kuryer lövhəsi** yalnız öz seçdiyi rayonlardan gələn sifarişləri göstərir
  (`WHERE goturulme_rayon_id IN (...)`) — "hamısını göstər" toggle YOXDUR.
- **WhatsApp:** platforma söhbətə qarışmır/saxlamır, yalnız ön-doldurulmuş `wa.me` linki açır.
- **Sessiya modeli:** müştəri/kuryer — KALICI (yalnız "Çıxış" və ya parol dəyişəndə bitir,
  remember-me cookie httponly+secure+samesite). Admin — QISA idle-timeout, KALICI DEYİL.
- **Abunə:** yarım-avtomatik ödəniş (recurring/avtomatik kart çəkmə YOXDUR). Kuryer hər ay
  özü "Ödə" basır. Admin panelində fərdi (+30 gün, tip dəyiş, dayandır) VƏ qlobal
  (bütün platformada abunəni dayandır/aktivləşdir) idarə səviyyələri var — fərqlidir.
- **Ödəniş:** `PaymentProvider` interfeysi (`baslat`, `callbackDogrula`), Birbank/Payriff
  adapterləri. Webhook idempotent olmalı (eyni `order_id` iki dəfə emal olunmaz). Kart
  məlumatı platformada saxlanmır.
- **PWA/iOS:** `beforeinstallprompt` iOS Safari-də dəstəklənmir — vizual təlimat göstərilir.
  iOS-da Web Push YALNIZ ana ekrana əlavədən sonra işləyir.
- **Ərazi idarəsi:** hardcode yox, hamısı DB-dən (admin əlavə/deaktiv/sil edə bilər).
  Sumqayıt siyahısı canlıya keçməzdən əvvəl rəsmi mənbədən yoxlanmalıdır.

## 6. Təhlükəsizlik (MƏCBURİ, hər fazada tətbiq olunur)

- **Hər PHP faylında `declare(strict_types=1);`**
- **PDO prepared statements** — heç bir raw SQL interpolation
- **CSRF token** bütün yazma (POST/PUT/DELETE) sorğularında, `hash_equals` ilə yoxlama
- **XSS:** çıxış `htmlspecialchars` ilə escape, CSP header
- **Sessiya:** httponly + secure + samesite cookie, login-də `session_regenerate_id`
- **Parol:** yalnız `password_hash`/`password_verify` (bcrypt/argon2), heç vaxt açıq mətn
- **Rol qoruması / IDOR:** middleware hər sorğuda rolu yoxlayır (müştəri yalnız öz sifarişini görür)
- **Rate-limit:** giriş, SSE, ödəniş endpoint-lərində

## 7. Endpoint Xəritəsi (Əlavə A)

| Metod & Yol | Rol | Təyinat |
|---|---|---|
| POST /qeydiyyat | Hər üçü | Qeydiyyat (nömrə+parol+sözləşmə) |
| POST /giris | Hər üçü | Giriş |
| POST /cixis | Hər üçü | Çıxış |
| POST /sifaris/yarat | Müştəri | Sifariş yaratma |
| POST /sifaris/{id}/legv | Müştəri | Ləğv |
| GET /sse/lovhe | Kuryer | Canlı lövhə (rayon filtrli) |
| POST /sifaris/{id}/gotur | Kuryer | Atomic götürmə |
| POST /sifaris/{id}/tamamla | Kuryer | Tamamlama |
| POST /kurye/onlayn | Kuryer | Onlayn/offline toggle |
| POST /odenis/basla | Kuryer | Abunə ödənişi |
| POST /webhook/odenis | Sistem | Ödəniş callback |
| GET /admin/... | Admin | Panel bölmələri |
| POST /admin/banner | Admin | Banner idarəsi |

## 8. Faza Planı (Texniki Yol Xəritəsi)

> **QAYDA: İstifadəçi "Növbəti fazaya keç" demədikcə növbəti fazaya keçilmir.**
> Hər faza bitəndə: kod fayl adları ilə raportlanır (məzmun ekrana YAZILMIR),
> `php -l` ilə sintaksis yoxlanılır, `CLAUDE.md` və `PROGRESS.md` yenilənir,
> istifadəçiyə YALNIZ bu deyilir: **"Faza X bitdi, yoxlamalar uğurludur, növbəti fazaya hazıram."**

- **Faza 0 — Təməl ✅ (tamamlandı 2026-07-11):** VPS qurulum qeydləri (Nginx, PHP-FPM 8.3,
  MySQL 8.0, TLS — bax `deploy/`), qovluq strukturu, Core (Router, PDO DB, Session,
  Request/Response, ErrorHandler, Logger), `bootstrap.php` avtoloader, config/.env sistemi.
  Ətraflı: `PROGRESS.md`.
- **Faza 1 — Verilənlər Bazası ✅ (tamamlandı 2026-07-11):** bütün cədvəllərin
  migration-ları (bölmə 4) `database/migrations/001-014` — real MySQL-ə qarşı
  test edilib. Seed (`database/seed/001-004`): şəhərlər (Bakı+Sumqayıt), rayonlar
  (36 ərazi), yukdasima_olculeri (XS-Mega), ayarlar (abune_rejimi). Runner-lər:
  `database/migrate.php`, `database/seed.php` (idempotent, tracking cədvəlli).
  Ətraflı: `PROGRESS.md`.
- **Faza 2 — Autentifikasiya:** qeydiyyat/giriş (müştəri+kuryer+yükdaşıma), rol yönləndirmə,
  nömrə+parol, sözləşmə qəbulu checkbox+tarix, sessiya/CSRF/rate-limit middleware.
- **Faza 3 — Sifariş və SSE:** müştəri sifariş yaratma/ləğv/tarixçə, SSE lövhə + rayon filtri,
  atomic götürmə (race qoruması) + WhatsApp link generasiyası, onlayn/offline, tamamlama,
  cron (1 saat passivləşmə).
- **Faza 4 — Admin Paneli:** ayrı subdomen, admin auth, müştəri/kuryer tablar + pop-up +
  bloklama, sifariş idarəsi (filtr/pagination), abunə idarəsi (fərdi+qlobal), banner sistemi.
- **Faza 5 — Abunə və Ödəniş:** abunə məntiqi/xəbərdarlıq/cron, payment adapter
  (Birbank/Payriff), webhook doğrulama (idempotent).
- **Faza 6 — PWA və Cilalama:** manifest, Service Worker, ana ekrana əlavə (Android+iOS),
  çoxdilli (AZ/RU/EN), dizayn cilası (glassmorphism), test, təhlükəsizlik yoxlaması, canlıya keçid.
- **Faza 7 — Hüquqi (sonra):** İstifadəçi Müqaviləsi + Məxfilik Siyasəti mətnləri
  (hüquqşünasdan sonra) aktivləşdirilir; infrastruktur artıq Faza 2-də hazırdır.

## 9. İş Qaydaları (Claude üçün məcburi davranış)

1. Hər sessiyaya bu fayl və `PROGRESS.md`-i oxumaqla başla — layihənin bütün detallarını
   təkrar-təkrar oxumaq/çıxarmaq lazım deyil, bu fayl kifayətdir.
2. Yazılan kodu ekrana TAM dərc ETMƏ. Yalnız: "Fayl: /path/to/file.php yaradıldı və kodu
   yazıldı" formatında rapor ver.
3. `.env` üçün həmişə placeholder dəyərlər istifadə et (əsl sirr/parol yazma).
4. Hər faza bitəndə: `CLAUDE.md` və `PROGRESS.md`-i yenilə, `php -l` ilə sintaksis yoxla.
5. İstifadəçi açıq şəkildə "Növbəti fazaya keç" deməyənə qədər sonrakı fazaya keçmə.
6. Faza bitmə raportu YALNIZ bu formatda: "Faza X bitdi, yoxlamalar uğurludur, növbəti
   fazaya hazıram." — kodu bu mesaja daxil etmə.
7. Bütün fazalar (0-7) bitdikdən sonra: final directory tree göstər və bütün yaradılan
   kodları birləşdirilmiş/strukturlu formada (kod blokları daxilində) təqdim et.
8. Təhlükəsizlik: hər yerdə PDO prepared statements, CSRF qoruması, `strict_types=1`.

## 10. İstinad Konvensiyası

Orijinal sənədin bənd nömrələrinə istinad edərək kod yazıla bilər, məs:
"6.3 bəndinə uyğun atomic götürmə kodunu yaz", "4.6-dakı sifarisler DDL-inə əsasən Sifaris
modelini qur", "9.1 Payment adapterini Birbank üçün tətbiq et", "8.4 banner sistemini qur".
Bu fayldakı bölmə nömrələri orijinal sənədin bölmələri ilə üst-üstə düşür (bölmə 1-11 + Əlavə A/B).
