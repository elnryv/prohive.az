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
  MySQL 8.0, Hostinger/Hetzner VPS, çoxdilli (AZ/RU/EN), bütün əlaqə WhatsApp
  Click-to-Chat üzərindən.
- **Dizayn sistemi — "Bənövşəyi Şəhər" (yenilənib 2026-07-13, bax bölmə 10.1):**
  istifadəçinin göndərdiyi referans mockup-a (indiqo/bənövşəyi tema) uyğun tam
  rəng dəyişikliyi — açıq lavanda fon (`#f5f5fc`) saxlanıb, əsas aksent indiqo
  (`--accent: #4f46e5`=marka/keçidlər, `--accent-warm: #4338ca`=əsas əməliyyat
  düymələri, iki ton bir-birini tamamlayır), yaşıl=onlayn/uğur, çəhrayı=təcili
  DƏYİŞMƏDİ. Bütün hardcode edilmiş köhnə mavi/narıncı `rgba()` kölgə dəyərləri
  (app.css + admin.css, `:root` xaricində, məs. buton box-shadow-ları) də uyğun
  yeniləndi ki, rəng keçidi tam olsun. Splash ekranı TAM YENİDƏN QURULDU: əvvəlki
  açıq-fonlu-kiçik-mark versiyası əvəzinə indi tam-ekran tünd indiqo qradient fon
  + böyük ağ dəyirmi mark + ağ "Birlikdə" mətni + alt "yüklənmə zolağı" pill-i
  (mockup-un splash ekranına uyğun) — animasiya ritmi (pulse→radar halqa→söz
  sıçrayışı) DƏYİŞMƏDİ, yalnız rənglər tünd fona uyğun tərsinə çevrildi. Bottom-
  nav-dakı emoji ikonlar (🏠📋🦺👤) təmiz xətt-əsaslı inline SVG ikonlarla
  (`currentColor` ilə aktiv/passiv vəziyyətə uyğunlaşır) əvəz olundu. PWA
  ikonları (`icon-192.png`/`icon-512.png`) yeni indiqo fon + ağ "B" hərfi ilə
  yenidən yaradıldı (`ffmpeg drawtext`, DejaVu Sans Bold), `manifest.json`
  `background_color`/`theme_color` uyğunlaşdırıldı. **Şüurlu qərar:** mockup-da
  görünən canlı GPS xəritə izləmə, Google/Apple ilə giriş, real interaktiv
  xəritə və reytinq sistemi TƏTBIQ OLUNMADI — bunlar dizayn dəyişikliyi deyil,
  yeni funksiyalardır (istifadəçiyə bildirildi, ayrıca qərar tələb edir).
  Köhnə dizayna geri qayıtmaq üçün `dizayn-v1-oncesi` git branch-i (GitHub-a
  push edilib) istinad nöqtəsi kimi saxlanılır. Hər tətbiqə TƏZƏ girişdə
  (kənar keçid — `document.referrer` eyni origin deyilsə; daxili keçidlərdə YOX)
  tam CSS splash animasiyası: loqo işarəsi (`.brand-mark`) əvvəlcə pulse edir,
  ətrafında radar-tipli genişlənən halqalar (`::before`/`::after`), sonra
  "Birlikdə" sözü sıçrayaraq açılır (WebGL/Canvas BİLƏRƏKDƏN işlədilmir,
  aşağı-səviyyəli Android performansı üçün). **Giriş/Qeydiyyat vahid telefon-
  əsaslı ağıllı axındır (Bolt/Uber-stil, kart-karuseli/accordion konseptləri
  ləğv edilib):** istifadəçi əvvəlcə yalnız telefon nömrəsini daxil edir
  (dəyişdirilə bilən ölkə-kodu dropdown-u, defolt Azərbaycan), `Birlikde.
  initAuthWizard()` `POST /telefon-yoxla` ilə mövcudluğu yoxlayır — varsa
  "Parol" addımı, yoxdursa tam qeydiyyat sahələri eyni ekranda `[hidden]`
  atributu ilə (JS grid/drag trikləri YOXDUR) açılır; qeydiyyatdan sonra
  avtomatik daxil edilir. Parol sahələrində göz-ikonu (göstər/gizlət),
  bütün sahələrdə güclü fokus halqası + boş/yanlış sahədə qırmızı sərhəd +
  konkret köməkçi mətn (inline validasiya). Müştəri panelində aktiv sifariş
  radar-puls ("axtarışda") və hərəkətli
  irəliləyiş zolağı ("götürülüb") animasiyaları ilə göstərilir (canlı
  GPS YOXDUR, status-əsaslı animasiya). Bütün səhifələrdə hamburger menyu
  (sürüşən drawer) — dil bayraqları da drawer-in içindədir. Admin panel
  EYNİ dizayn dilini paylaşır (sabit yan-menyu + eyni rəng/hərəkət
  tokenləri). Köhnə sinif adları
  (`.card`, `.btn-primary`, `.glass`, `.badge-*` və s.) saxlanılıb — yalnız CSS
  dəyərləri dəyişib, view fayllarına toxunulmayıb.
- **Hüquqi qeyd (yenilənib 2026-07-13):** 10 hüquqi sənəd (Faza 7, `config/legal/`,
  `GET /huquqi`) istifadəçinin qərarı ilə sayt-üzü (public-facing) "QARALAMA/
  hüquqşünas təsdiqi gözlənilir" xəbərdarlıqları və bracket yer tutucuları (`[VÖEN]`,
  `[məbləğ]` və s.) LƏĞV EDİLİB — sənədlər indi bitmiş mətn kimi göstərilir (bax
  Post-launch qeydləri). **DAXİLİ QEYD (Claude üçün, saytda GÖSTƏRİLMİR):** məzmun
  yenə də süni intellekt tərəfindən yazılıb, lisenziyalı hüquqşünas tərəfindən
  TƏSDİQLƏNMƏYİB — bu, istifadəçinin (platforma sahibinin) məlumatlı qərarı ilə
  belədir, real istifadəçi sayı/gəlir artdıqca peşəkar hüquqi yoxlama tövsiyə olunur.
  Operator fərdi sahibkar kimi fəaliyyət göstərir, ayrıca şirkət adı yoxdur —
  VÖEN 1406574562 istifadəçi tərəfindən təsdiqləndi və `istifadeci-muqavilesi.php`
  1.1.1-ə əlavə olundu (2026-07-13). Checkbox + `sozlesme_qebul`/`sozlesme_tarix` + audit
  (legal_logs) infrastrukturu Faza 2-dən bəri hazırdır.

## 2. Texnologiya Yığını

| Qat | Texnologiya | Qeyd |
|---|---|---|
| Server/OS | Ubuntu (Hostinger VPS) | Nginx + PHP-FPM 8.3 |
| Backend | Native PHP 8.3 | Framework yox, **hər faylda `declare(strict_types=1)`**. Tək istisna: Composer + `minishlink/web-push` (yalnız real Web Push göndərmə üçün, 2026-07-12 əlavə olundu — bax bölmə 5 "Web Push") |
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
- **Yeni sifariş push bildirişi (2026-07-12 əlavə olundu):** SSE canlı lövhə
  yalnız tətbiq açıq olanda işləyir — bağlı olan kuryerə xəbər vermək üçün
  `SifarisService::yarat()` sifariş yaradılan kimi uyğun ərazidəki (rayon +
  tip + yükdaşıma olarsa ölçü uyğunluğu, `Kurye::rayonaVeTipeUygunlar`)
  BÜTÜN aktiv-abunəli kuryerlərə push göndərir — **onlayn/offline
  statusundan asılı olmayaraq** (məqsəd elə budur: bağlı kuryeri işə
  çağırmaq). Real DB+HTTP ilə test edilib: uyğun ərazidəki offline kuryer
  bildiriş aldı, fərqli ərazidəki kuryer almadı.
- **WhatsApp:** platforma söhbətə qarışmır/saxlamır, yalnız ön-doldurulmuş `wa.me` linki açır.
- **Sessiya modeli:** müştəri/kuryer — KALICI (yalnız "Çıxış" və ya parol dəyişəndə bitir,
  remember-me cookie httponly+secure+samesite). Admin — QISA idle-timeout, KALICI DEYİL.
- **Abunə:** yarım-avtomatik ödəniş (recurring/avtomatik kart çəkmə YOXDUR — "avtomatik"
  yalnız bitmə tarixi keçəndə bildiriş + lövhənin avtomatik bağlanması mənasındadır,
  `cron/abunelik_yoxla.php`). Kuryer hər ay özü "Ödə" basır. Admin panelində fərdi
  (+30 gün, tip dəyiş, dayandır) VƏ qlobal (bütün platformada abunəni dayandır/
  aktivləşdir — `abune_rejimi`) idarə səviyyələri var. Bundan ƏLAVƏ: kuryerlər
  səhifəsində "Hamısını PULSUZ/PULLU et" toplu düyməsi var (`AbunelikService::
  hamisiniDeyis`) — "pulsuz" bütün kuryerlərə 30 günlük aktiv+pulsuz dövr verir
  (yoxdursa yaradır, varsa uzadır) VƏ rəsmi kampaniya bildirişi göndərir (PushService);
  "pullu" yalnız hazırda pulsuz olanları geri "pullu"-ya çevirir, qalan müddətə
  toxunmur.
- **Ödəniş:** `PaymentProvider` interfeysi (`baslat`, `callbackDogrula`). **Payriff REAL
  API-yə qoşulub** (`PayriffProvider`, `POST https://api.payriff.com/api/v2/createOrder`,
  `Authorization: <SECRET_KEY>` başlığı, cavabda `payload.paymentUrl`/`payload.orderId`) —
  default provayder. `approveURL`/`cancelURL`/`declineURL` eyni `/odenis/qayit` ünvanına
  göstərir (Payriff bura HƏM POST payload göndərir, HƏM brauzeri yönləndirir; nəticə
  `payload.paymentStatus === 'PAID'`-dan oxunur). Ödənişdən sonra kuryer `/odenis/qayit`
  təsdiq səhifəsində JS polling (`GET /odenis/son-hal`) ilə nəticəni gözləyir, uğurlu
  olduqda avtomatik `/lovhe`-yə yönləndirilir. **Qeyd:** rəsmi Payriff sənədləşməsi
  (docs.payriff.com) bu inkişaf mühitindən şəbəkə siyasətinə görə əlçatan olmadığı üçün
  callback-in kriptoqrafik imza sxemi tam təsdiqlənə bilməyib — hazırkı müdafiə xətti
  `order_id`-nin təxmin edilə bilməyən UUID olması + məbləğ/vəziyyət yoxlamasıdır (bax
  `PayriffProvider.php` qeydi). Canlıya keçmədən əvvəl real Payriff hesabı ilə test
  kartlarla (VISA/MC, 3DS icbari) tam axın yoxlanmalıdır. Birbank (`BirbankProvider`) hələ
  YER TUTUCUDUR. Webhook idempotent olmalı (eyni `order_id` iki dəfə emal olunmaz). Kart
  məlumatı platformada saxlanmır.
- **PWA/iOS:** `beforeinstallprompt` iOS Safari-də dəstəklənmir — vizual təlimat göstərilir.
  iOS-da Web Push YALNIZ ana ekrana əlavədən sonra işləyir.
- **Web Push (2026-07-12 real edildi):** əvvəllər yalnız infrastruktur idi (niyyət
  loglanırdı, faktiki göndərilmirdi) — indi `App\Services\PushService` real RFC 8291
  (ECDH+AES-128-GCM şifrələmə) + RFC 8292 (VAPID JWT) ilə `minishlink/web-push`
  (Composer) vasitəsilə göndərir. Serverdə **`composer install --no-dev`** işə
  salınmalıdır (`vendor/` gitignore-dadır, commit olunmur). WebPush müştərisi
  TƏNBƏL yaradılır (yalnız faktiki göndəriş anında) — kitabxananın GMP/BCMath
  yoxdursa verdiyi performans xəbərdarlığı `@` ilə susdurulur (`ErrorHandler`
  APP_DEBUG=true-da hər notice-i istisnaya çevirir, bu olmasa hər sorğu 500
  verərdi — real bug tapılıb düzəldildi). `php-bcmath` genişlənməsinin serverdə
  quraşdırılması tövsiyə olunur (performans, tələb olunmur). Ötürülmə uğursuz
  olub (404/410) subscription bitibsə `push_abuneler`-dən avtomatik silinir.
- **Ərazi idarəsi:** hardcode yox, hamısı DB-dən (admin əlavə/deaktiv/sil edə bilər).
  Sumqayıt siyahısı canlıya keçməzdən əvvəl rəsmi mənbədən yoxlanmalıdır.

## 6. Təhlükəsizlik (MƏCBURİ, hər fazada tətbiq olunur)

- **Hər PHP faylında `declare(strict_types=1);`**
- **PDO prepared statements** — heç bir raw SQL interpolation
- **CSRF token** bütün yazma (POST/PUT/DELETE) sorğularında, `hash_equals` ilə yoxlama
- **XSS:** çıxış `htmlspecialchars` ilə escape (server), `Birlikde.escapeHtml`/
  `BirlikdeAdmin.escapeHtml` (JS-də render olunan bütün dinamik sahələr — 2026-07-12
  tam auditlə təsdiqləndi, escape-siz `innerHTML` interpolasiyası tapılmadı), CSP header
  (`App\Core\SecurityHeaders`, bax aşağı).
- **Təhlükəsizlik başlıqları (2026-07-12 əlavə olundu):** `App\Core\SecurityHeaders::apply()`
  hər iki front controller-də (`public/index.php`, `public/admin.php`) çağırılır —
  CSP (`default-src 'self'`, `object-src 'none'`, `frame-ancestors 'none'`,
  `base-uri/form-action 'self'`; `script-src`/`style-src`-də geniş inline istifadəyə görə
  `'unsafe-inline'` saxlanılıb, nonce-əsaslı refaktorinq gələcək iş kimi qalır),
  `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy`,
  `Permissions-Policy`, `Cross-Origin-Opener-Policy`, HSTS (`SESSION_SECURE=true`-da).
  Statik fayllar (nginx birbaşa verir, PHP-ə çatmır) üçün eyni əsas başlıqlar
  `deploy/nginx/*.conf`-da `add_header` ilə təkrarlanıb — **DİQQƏT: bu nginx dəyişiklikləri
  serverə əl ilə köçürülüb `nginx -t && systemctl reload nginx` ilə tətbiq edilməlidir,
  `git pull` avtomatik aktivləşdirmir**. `sw.js` xüsusi `Cache-Control: no-cache` alır (uzun
  keş PWA yeniləmələrini gecikdirməsin deyə).
- **Sessiya:** httponly + secure + samesite cookie, login-də `session_regenerate_id`
- **Parol:** yalnız `password_hash`/`password_verify` (bcrypt/argon2), heç vaxt açıq mətn.
  Giriş (həm app, həm admin) mövcud olmayan hesab üçün də sabit hash ilə `password_verify`
  çağırır (`AuthService::DUMMY_HASH`) — timing side-channel ilə hesab mövcudluğunun
  müəyyənləşdirilməsinin qarşısı alınır.
- **Rol qoruması / IDOR:** middleware hər sorğuda rolu yoxlayır (müştəri yalnız öz sifarişini
  görür); sahiblik yoxlaması DB sorğusunun özündə də var (məs. `Sifaris::cancel()`
  `WHERE id=:id AND musteri_id=:musteri_id`), təkcə tətbiq qatında deyil.
- **Fayl yükləmə:** ölçü limiti, `is_uploaded_file()`, real `mime_content_type()` +
  `getimagesize()` yoxlaması (uzantı saxtakarlığına qarşı), təsadüfi fayl adı, faylı
  göstərən controller-lər (`KuryeSekilController`, `BannerImageController`) sərt regex
  whitelist ilə `basename()` istifadə edir (path traversal qoruması).
- **Rate-limit:** giriş, qeydiyyat, SSE, ödəniş endpoint-lərində (fayl-əsaslı, IP+yol üzrə,
  5 cəhd/15 dəq).

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
| GET /odenis/qayit | Kuryer | Ödəniş təsdiq səhifəsi (Əlavə A-da yoxdur, Payriff return/callback üçün əlavə olundu) |
| POST /odenis/qayit | Sistem | Payriff callback (eyni ünvan — approveURL=cancelURL=declineURL) |
| GET /odenis/son-hal | Kuryer | Ödəniş vəziyyəti polling (JSON) |
| POST /kuryeler/abunelik/hamisi | Admin | Bütün kuryerləri pulsuz/pullu et (Əlavə A-da yoxdur, əlavə olundu) |
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
- **Faza 2 — Autentifikasiya ✅ (tamamlandı 2026-07-11):** `AuthController` +
  `AuthService` — qeydiyyat (3 rol: musteri/kurye/yukdasima), giriş, çıxış;
  `password_hash`/`password_verify`; sözləşmə checkbox+tarix (`config/sozlesme.php`
  YER TUTUCU mətn); `CsrfGuard`/`RateLimit`/`Auth`/`RoleGuard` middleware; remember-me
  (7.3.1) `Sessiya` modeli ilə. Real MySQL+HTTP ilə test edildi. Ətraflı: `PROGRESS.md`.
  Qeyd: rol-əsaslı panel YÖNLƏNDIRMƏSI (HTML redirect) Faza 6-da Views qatı ilə
  gələcək — hazırda giriş JSON `{rol: ...}` qaytarır.
- **Faza 3 — Sifariş və SSE ✅ (tamamlandı 2026-07-11):** müştəri sifariş yaratma/ləğv/
  tarixçə, SSE lövhə + rayon/tip/ölçü filtri, atomic götürmə (race qoruması, server-tərəfdə
  də "uyğun daşıyıcı" yoxlaması) + WhatsApp link generasiyası, onlayn/offline, ərazi
  idarəsi (`POST /kurye/bolgeler` — Əlavə A-da yoxdur, filtri işlək etmək üçün əlavə
  olundu), tamamlama, cron (`cron/sifaris_temizle.php`, 1 saat passivləşmə). Real
  MySQL + çox-worker HTTP server ilə test edildi. Ətraflı: `PROGRESS.md`.
- **Faza 4 — Admin Paneli ✅ (tamamlandı 2026-07-11):** ayrı subdomen (`appadmin`,
  tam izolyasiya olunmuş sessiya/cookie), qısa idle-timeout admin auth (remember-me
  YOXDUR), ayrı `adminler` cədvəli (yalnız CLI ilə yaradılır, web qeydiyyatı yoxdur),
  müştəri/kuryer tablar + pop-up + bloklama (səbəb məcburi), sifariş idarəsi
  (status/rayon/tarix filtri + pagination + tarixçə), abunə idarəsi (fərdi: +gün/
  tip/dayandır; qlobal: `abune_rejimi` toggle), banner sistemi (yükləmə validasiyası
  + `GET /banner-sekil/{fayl}` göstərmə marşrutu — Əlavə A-da yoxdur, zəruri olduğu
  üçün əlavə olundu). Bölmə 8.1 (Dashboard: sayğaclar+son hadisələr) və 8.7
  (Ərazi İdarəsi: şəhər/rayon yarat/aktivlik-dəyiş/sil, referential-integrity
  qorumalı) əlavə tapşırıqla sonradan əlavə olundu. Real MySQL+HTTP ilə test
  edildi. Ətraflı: `PROGRESS.md`.
- **Faza 5 — Abunə və Ödəniş ✅ (tamamlandı 2026-07-11):** `PaymentProvider`
  interfeysi + `BirbankProvider`/`PayriffProvider` (imzalı redirect + HMAC-SHA256
  webhook doğrulama, sahə adları/URL YER TUTUCUDUR — real API sənədləşməsi
  gələndə yenilənməlidir), `POST /odenis/basla` + `POST /webhook/odenis`
  (tam idempotent), `cron/abunelik_yoxla.php` (bağlama+xəbərdarlıq, dublikatsız).
  Faza 3-ün SSE/götürmə axınına "aktiv abunə tələb olunur" yoxlaması geriyə
  doldurularaq əlavə edildi (bölmə 7.2.1). Real MySQL+HTTP ilə test edildi
  (əsl bank credential-ı olmadan, imza yoxlaması yerli/pure əməliyyat olduğu
  üçün tam test edilə bildi). Ətraflı: `PROGRESS.md`.
- **Faza 6 — PWA və Cilalama ✅ (tamamlandı 2026-07-11):** manifest+Service Worker
  (app-shell cache-first, statik fayllarla məhdudlaşdırılıb), PWA ikonları, tam
  frontend (`app/Views/` — müştəri/kuryer/admin bütün səhifələr, real HTML+JS),
  çoxdilli (AZ/RU/EN, 100 açar, paritetli), dizayn cilası (`.glass` bloku bölmə
  10.1-dən eynilə), Web Push infrastrukturu (VAPID, `push_abuneler`, trigger
  nöqtələri — faktiki şifrələnmiş göndərmə YER TUTUCUDUR). Faza 3-ün abunə
  yoxlaması geriyə doldurularaq bağlandı. Real MySQL+HTTP+Playwright/Chromium
  (həqiqi brauzer) ilə test edildi — 2 real bug (skript yükləmə sırası, Service
  Worker credentials itirilməsi) tapılıb düzəldildi. Ətraflı: `PROGRESS.md`.
- **Faza 7 — Hüquqi ✅ (tamamlandı 2026-07-11):** istifadəçinin yüklədiyi
  `Birlikde_Huquqi_Paket.docx`-dan (10 sənəd: İstifadəçi Müqaviləsi, Məxfilik
  Siyasəti, Cookie Siyasəti, Abunə/Ödəniş Şərtləri, Məsuliyyətdən İmtina, Kuryer/
  Yükdaşıma Qaydaları, Müştəri Qaydaları, Qadağan Yüklər, Şikayət/Mübahisə,
  Fərdi Məlumat Razılığı) məzmun `config/legal/sujetler.php` + `config/legal/
  content/{slug}.php`-ə köçürüldü, `LegalController` + `Views/legal/*` +
  `GET /huquqi`, `GET /huquqi/{slug}` marşrutları ilə canlı tətbiqə bağlandı,
  qeydiyyat sözləşmə qutusuna "tam mətni oxu" linkləri əlavə olundu. Bracket yer
  tutucular (`[VÖEN]`, `[məbləğ]`, `[müddət]` və s.) və sayt-üzü "QARALAMA/
  hüquqşünas gözlənilir" xəbərdarlıqları 2026-07-13-də istifadəçinin qərarı ilə
  ləğv edildi/dolduruldu (bax Post-launch qeydləri və bölmə 1 "Hüquqi qeyd" — daxili
  qeyd oradadır, saytda göstərilmir). Yalnız AZ dilində; RU/EN səhifələrində "rəsmi
  mətn AZ dilindədir" qeydi var (uydurma tərcümə edilmədi). Real MySQL+HTTP ilə test
  edildi. Ətraflı: PROGRESS.md. **Bütün fazalar (0-7) tamamlandı.**
- **Post-launch (canlı Hetzner/aaPanel serverə yerləşdirmə + UX/dizayn
  iterasiyaları):** layihə Faza 7-dən sonra canlıya keçirildi, real
  istifadəçi testindən gələn düzəlişlər davam edən iterasiyalarla tətbiq
  olunur (PWA keş versiyalanması, kuryer "Sifarişlərim" bölməsi, profil
  şəkli, sosial-stil profil, yığcam dizayn və s.) — tam xronoloji detallar
  PROGRESS.md-də tarixli qeydlərlə saxlanılır, bu fayl yalnız yekun
  vəziyyəti (bölmə 1-7) əks etdirir.

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
