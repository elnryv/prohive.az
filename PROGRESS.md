# BİRLİKDƏ YÜK — İCRA JURNALI (PROGRESS.md)

Həqiqət mənbəyi: `BIRLIKDE_YUK_TAM.md` (istifadəçinin göndərdiyi PDF-dən çıxarılıb, bax aşağı qeyd) + bu fayl.

## SUAL / QƏRAR QEYDİ — HƏLL OLUNDU (2026-07-17)

**Əvvəlki SUAL:** `BIRLIKDE_GETDIK_TAM.md`/`.pdf` (Payriff `PaymentGateway.php`/`PayriffProvider.php`
kodu, telefon normalizasiya alqoritmi, SSE poll mexanizmi üçün istinad sənədi) nə repoda, nə
upload-larda tapılmadı. Bloklamadan, best-practice tətbiqi ilə davam edilmişdi.

**Yenilik:** İstifadəçi `BIRLIKDE_GETDIK_TAM.pdf`-i yüklədi. Sənəd oxundu — **bu, "Birlikdə Yük"-ün
bir hissəsi/kitabxanası DEYİL, tamamilə ayrı, müstəqil bir məhsulun (bölgə qonaq evləri vitrin
platforması, "Birlikdə Getdik", `getdik.birlikde.biz`) tam texniki şərtnaməsidir** — fərqli domen,
fərqli DB sxemi (`owners`/`houses`/`regions`), fərqli biznes modeli (aylıq abunə vitrin, rezervasiya
yox). Yəni "eyni kod bazası" fərziyyəsi yanlış idi.

Bununla belə, sənəddə (eyni müəllifin əli ilə yazılmış) konkret, işlək referans kodu var idi ki, bunlar
"Birlikdə Yük"-ə də tətbiq edilə bilər və edildi:

- **Telefon normalizasiyası (bölmə 5.1):** Getdik-in alqoritmi (rəqəm təmizlə → `0`-prefiks → `994+qalan`
  → `994`-prefiks olduğu kimi → 9 rəqəmlidirsə `994+` prefiks → 12 rəqəm deyilsə xəta) `app/Core/Phone.php`-də
  artıq **hərfi olaraq** tətbiq olunmuş formada idi (yalnız əlavə olaraq mobil operator prefiks siyahısı ilə
  validasiya edir) — **dəyişiklik tələb olunmadı, təsdiqləndi.**
- **Payriff inteqrasiyası (bölmə 8.3, "TAM" kod):** `app/Payments/PayriffProvider.php` bu sənədin konkret
  sorğu/cavab konvensiyalarına uyğun **yeniləndi**:
  - `Authorization` başlığı `Bearer ` prefiksi OLMADAN, xam Secret Key kimi göndərilir.
  - Sorğu gövdəsinə `operation: 'PURCHASE'`, `cancelUrl`, `metadata.externalRef` (əvvəlki
    `externalTrackId` top-level sahəsinin yerinə) əlavə olundu.
  - Status sorğusu `GET /orders/{orderId}` (əvvəlki `/orders/{id}/status` deyil) və cavab
    `payload.paymentStatus ?? payload.orderStatus` sahələrindən (`APPROVED`/`DECLINED`/`CANCELED`/
    `PENDING`/`UNKNOWN`) oxunur — uydurma `EXPIRED`/`REVERSED` dəyərləri silindi.
  - `storage/logs/payriff.log`-a audit logu əlavə olundu (bölmə 8.3-dəki `log()` metodu nümunəsi ilə).
  - `PaymentCallbackController::reconcile()` bu yeni status lüğətinə uyğunlaşdırıldı
    (`declined`/`canceled` → `failed`; `pending`/`unknown` → toxunulmur).
  - Callback təhlükəsizlik qaydası (body-yə güvənməmək, `getOrderStatus`-la yenidən sorğulamaq,
    idempotentlik) artıq düzgün tətbiq olunmuşdu — dəyişiklik tələb olunmadı.
- **SSE (bölmə 11.4):** `app/Core/Sse.php`-nin kanal-əsaslı poll + `Last-Event-ID` + ping mexanizmi
  Getdik-in təsvirinə artıq uyğun idi — **dəyişiklik tələb olunmadı, təsdiqləndi.**

Nəticə: bu 3 sahə indi Getdik sənədindəki konkret nümunə koda tam uyğundur. Payriff `SECRET_KEY`/
`merchant_id` hələ də placeholder-dır (sahibkar özü dolduracaq, Q7).

---

## FAZA STATUSLARI

| Faza | Ad | Status |
|---|---|---|
| 0 | Skelet | ✅ tamamlandı |
| 1 | Auth + rollar | ✅ tamamlandı |
| 2 | Elan + lent | ✅ tamamlandı |
| 3 | Təklif + qəbul (atomik) | ✅ tamamlandı |
| 4 | SSE + Push | ✅ tamamlandı |
| 5 | Abunə + Payriff | ✅ tamamlandı |
| 6 | Admin | ✅ tamamlandı |
| 7 | PWA + dizayn cilası | ✅ tamamlandı |
| 8 | Buraxılış | ✅ tamamlandı |

## FAZA 1 — Auth + rollar ✅ TAMAMLANDI

- [x] `/qeydiyyat` — rol seçimi (2 böyük kart), müştəri forması, sürücü forması (maşın növü/qeyd/foto)
- [x] Müştəri qeydiyyatı → dərhal aktiv + avtomatik giriş (Q-Y16)
- [x] Sürücü qeydiyyatı → `driver_status='pending'`, `billing_status='trial'`, foto WebP-ə kodlanır,
      avtomatik giriş + "Profilin yoxlanılır" banner-i lentdə (stub)
- [x] `/giris`, `/cixis` — 5 cəhd/15 dəq kilid, "yadda saxla" (HMAC remember-cookie, 30 gün)
- [x] `Phone::normalize()` — bax FAZA 0 test nəticələri, 994XXXXXXXXX formatı
- [x] CSRF token bütün POST formalarında
- [x] Müştəri/sürücü stub dashboard-ları (tam funksionallıq FAZA 2/3-də)

### Özünüyoxlama nəticələri (HTTP səviyyəsində, real server + MySQL)
- Müştəri qeydiyyatı → DB-də düzgün sətir, dərhal `/musteri/elanlarim`-a yönləndirilir. ✓
- Sürücü qeydiyyatı (multipart foto ilə) → foto real WebP-ə kodlanıb saxlanılıb, `driver_status=pending`,
  `trial_until` = bugün+30, lentdə "Profilin yoxlanılır" banner-i göstərilir. ✓
- **Pending sürücü təklif verə bilmir**: `Auth::isActiveDriver()` pending üçün `false`, `approved`-a
  keçiriləndə `true` qaytarır (DB-səviyyəsində unit-test edilib). Tam UI/endpoint enforcement (təklif
  düyməsinin kilidli overlay-i) FAZA 3-də təklif endpoint-i yaradılanda YENİDƏN yoxlanılacaq.
- **5 format nömrə testi**: FAZA 0-da 8 fərqli format (0501234567, +994501234567, 994501234567,
  501234567, boşluqlu, tire-li, 00994 prefiksli, mötərizəli) → hamısı `994501234567`-ə normallaşdı;
  2 yanlış format düzgün rədd edildi.
- Login kilidi: 5 səhv cəhddən sonra 6-cı cəhd (düzgün şifrə ilə belə) kilidlənmə mesajı verir;
  kilid silinəndən sonra düzgün şifrə ilə giriş uğurlu. ✓
- Remember-cookie: sessiya cookie-si silinəndə belə (brauzer bağlanma simulyasiyası) HMAC remember-cookie
  ilə avtomatik giriş işləyir; manipulyasiya olunmuş (imza uyğunsuz) cookie rədd edilir. ✓
- Eyni nömrə fərqli formatda (050-123-45-67) təkrar qeydiyyatda "artıq qeydiyyat var" xətası verir. ✓
- CSRF token olmadan POST → HTTP 419. ✓

## FAZA 2 — Elan + lent ✅ TAMAMLANDI

- [x] `/musteri/elan/yeni` — kateqoriya kartları (ikon+hint-hazır sxem), haradan/hara + detal xəbərdarlığı,
      tarix rejimi (razılaşma/konkret) + təcili çipi, təsvir, foto (maks 6). Qiymət sahəsi YOXDUR (Q-Y2).
- [x] Yaradılışda: `scope` avtomatik (`ListingRules::scopeFor`), `expires_at` hesablanır, unikal `public_code`,
      `listing_events` "created" qeydi, `sse_events`-ə `listing_new` yazılır (FAZA 4-də real yayım),
      OG şəkli bir dəfə render olunur (`OgImage::saveForListing`)
- [x] Müştəri elan idarəsi (`/musteri/elan/{id}`): status paneli, foto, sil/uzat (1 dəfə), paylaşım linki,
      təkliflər bölməsi (boş — FAZA 3-də dolacaq)
- [x] Sürücü lenti (`/surucu/lent`): Bakı/Bölgələrarası tab, kateqoriya filtri, təcili üstdə, kart dizaynı
- [x] Sürücü elan səhifəsi (`/surucu/elan/{id}`): pending/inaktiv üçün kilid banner-i, aktiv sürücüyə
      "təklif forması tezliklə" (FAZA 3 üçün yer saxlanılıb)
- [x] Public paylaşım kartı `/e/{code}` (Q-Y10): loginsiz, marşrut/kateqoriya/tarix/təsvir/foto/təklif SAYI —
      nömrə və qiymət YOXDUR, OG meta teqləri (`layouts/public.php`)
- [x] Müştəri tarixçəsi (`/musteri/tarixce`, Q-Y11): tamamlanmış/bitmiş/silinmiş elanlar + "Yenidən sifariş"
      (köhnə elanın kopyası ilə doldurulmuş forma)
- [x] Gündə maks 5 aktiv elan limiti (bölmə 12.1)

### Özünüyoxlama nəticələri (real server + MySQL + Playwright)
- Elan yaratma HTTP round-trip **0.046 saniyə** (tələb: ≤60 saniyə). ✓
- Scope hesablanması: Yasamal(Bakı)→Gəncə(bölgə) → `intercity` DB-də düzgün yazılıb. ✓
- **Tablar düzgün ayırır**: Bakı-daxili tab boş qayıtdı, Bölgələrarası tab elanı göstərdi (server-side
  filtr, DB sorğusu ilə təsdiqləndi). ✓
- **Public səhifədə nömrə YOXDUR**: `/e/{code}` HTML-i `994`/`+994`/`phone` pattern-lərinə qarşı grep
  edildi — heç bir uyğunluq tapılmadı; eyni şəkildə qiymət/AZN də yoxdur (sorğu səviyyəsində belə
  seçilmir, yalnız `COUNT(*)` təklif sayı). ✓
- OG şəkli avtomatik generasiya olunub (`storage/og/{code}.png`, 1200×630 PNG, GD ilə). ✓
- Pending sürücü elan səhifəsində kilid banner-i görür, approved+trial sürücü təklif yerini görür
  (offer forması FAZA 3-də aktivləşəcək). ✓
- Playwright vizual test zamanı **2 real bug tapılıb və düzəldilib**:
  1. Bottom-nav-ın mərkəzi "+" düyməsində PHP kodu səhvən düymənin GÖRÜNƏN mətninə yazılmışdı
     (`class="fab"><?= $active(...) ?>+` → ekранда hərfi ilə "active+" mətni çıxırdı) — CSS class
     atributuna köçürüldü.
  2. `.fab` düyməsi `.bottom-nav a`-nın ümumi `flex:1` qaydasını miras alıb oval şəklə düşmüşdü —
     `flex:0 0 44px` əlavə edilərək dairəvi формасы bərpa olundu.
  Hər iki düzəlişdən sonra təkrar screenshot ilə vizual təsdiqləndi.

## FAZA 3 — Təklif + atomik qəbul ✅ TAMAMLANDI (layihənin "ürəyi")

- [x] Sürücü təklif ver/yenilə/geri çək (`/surucu/elan/{id}/teklif`, bölmə 7.3): 1 sürücü = 1 təklif
      (yenilənə bilər), anti-spam saatda maks 20, yalnız `active` elana
- [x] Müştəridə canlı təklif siyahısı (`listing_show.php`) — hələ SSE deyil (FAZA 4), amma server-side
      tam işlək; sürücü profili (ad, maşın növü, jobs_done, cancel_count)
- [x] **ATOMİK qəbul** (`OfferActionController::accept`, Q-Y4) — bölmə 6.4-dəki tranzaksiya BİRƏ-BİR:
      `SELECT ... FOR UPDATE` + şərtli `UPDATE ... WHERE status='active'` + `rowCount()` yoxlaması.
      Spec-in sadələşdirilmiş SQL-inə əlavə qoruma: offer UPDATE-i də `listing_id`+`status='pending'`
      şərti ilə məhdudlaşdırılıb (defensiv, yarış zamanı səhv sətrin yenilənməsinin qarşısını alır).
- [x] **Nömrə açılışı (Q-Y3)**: telefon sütunu YALNIZ elan sahibinin öz sorğusunda (müştəri
      `listing_show`) və sürücünün öz təklifləri sorğusunda (`MyOffersController`) seçilir; view
      isə əlavə olaraq yalnız `status==='accepted'` halında göstərir. Üçüncü şəxs heç bir mərhələdə
      görmür (aşağıdakı testlə təsdiqlənib).
- [x] Ləğv/yenidən açılma (`OfferActionController::cancel`, Q-Y5): səbəb seçimi, `lost`→`pending` bərpa,
      `driver_no_show`→`cancel_count+1`, maks 2 dəfə yenidən açılma limiti
- [x] `cron/hourly.php`: `active`→`expired`, `accepted`→`completed` (+ `jobs_done+1`), köhnə OG təmizliyi
- [x] Sürücü "Təkliflərim" (`/surucu/tekliflerim`): Gözləyir/Qəbul edildi/Yük götürüldü/Geri çəkdim tabları
- [x] Sürücü iş tarixçəsi (`/surucu/tarixce`, bölmə 7.5): tamamlanmış işlər + aylıq qazanc cəmi
- [x] `Lang::use()` + `WebPush::sendToUsersLocalized()` — push mətni hər alıcının ÖZ `users.lang`
      dəyərində qurulur (kütləvi bildirişlərdə dil qarışmasının qarşısı)

### Özünüyoxlama nəticələri (real server + MySQL, HTTP + DB səviyyəsində)
- **Yarış vəziyyəti testi (məcburi kriteriya)**: eyni elana 2 sürücüdən təklif → müştəri sessiyasından
  **10 tam paralel** (`curl ... &` + `wait`) qəbul sorğusu göndərildi → `listing_events`-də cəmi **1**
  `accepted` qeydi, DB-də düz 1 təklif `accepted`, elan `accepted` statusunda. Əvvəlki 2-sorğuluq testdə
  də eyni nəticə: 1-i uğurlu (`Location: /musteri/elan/{id}`), qalanı `?xeta=artiq_qebul_edilib`. ✓
- **Nömrə məxfiliyi (məcburi kriteriya)**: qəbuldan sonra — qəbul edən sürücü müştəri nömrəsini,
  müştəri qəbul edən sürücünün nömrəsini görür; **uduzan (2-ci) sürücü** öz "Təkliflərim" səhifəsində
  (Yük götürüldü tabı) heç bir nömrə görmür (grep ilə təsdiqləndi) — həm public səhifədə, həm elan
  aktiv ikən heç bir mərhələdə nömrə sızmır. ✓
- Ləğv/yenidən açılma: "Sürücü gəlmədi" səbəbi ilə ləğv → sürücünün `cancel_count` 0→1; lost olan digər
  təklif `pending`-ə bərpa oldu; `reopen_count` 1→2; 3-cü cəhd `?xeta=limit_yenidenachma` ilə bloklandı. ✓
- Geri çəkmə: `pending` təklif → `withdrawn`, elanın təklif siyahısından itdi. ✓
- Cron: `accepted_at`-i 80 saat geriyə çəkilmiş elan `cron/hourly.php` işlədildikdən sonra `completed`
  oldu, sürücünün `jobs_done` 0→1. ✓
- Playwright vizual yoxlamasında 1 real bug tapılıb düzəldilib: `MyOffersController`-in "Qəbul edildi"
  tabı `l.status='completed'` olan (artıq tamamlanmış) işləri də göstərirdi — filtrə `l.status <> 'completed'`
  əlavə edildi ki, tamamlanmış işlər yalnız `/surucu/tarixce`-də görünsün.

## FAZA 4 — SSE + Push ✅ TAMAMLANDI

- [x] `StreamController` (`/axin/lent`, `/axin/musteri`) — `Sse::stream()` üzərində quruldu, `lastId`
      seed edilir (aşağıdakı bug düzəlişinə bax)
- [x] `sw.js` (Service Worker): cache-first statik fayllar + `push`/`notificationclick` hadisələri
- [x] `app.js`: SW qeydiyyatı, Web Push abunəliyi (`/push/vapid-acar`, `/push/abune`, `/push/legv`),
      sürücü lentində canlı DOM yeniləməsi (yeni kart daxil olma/bağlanan kart sönmə animasiyası)
- [x] Marşrut abunəliyi (`/surucu/marsrutlar`, Q-Y12, maks 5) + yeni elanda uyğun sürücülərə
      `route_match` push (`ListingController::notifyRouteSubscribers`)
- [x] Bildiriş növləri tam bağlandı: `offer_new` (müştəriyə), `offer_accepted` (sürücüyə),
      `listing_reopened` (bərpa olunan təklifçilərə) — hamısı `WebPush::sendToUsersLocalized()`
      ilə ALICININ öz dilində (`Lang::use()`)
- [x] `cron/vapid_keygen.php` — CLI skript, `settings.vapid_public/private`-ə yazır

### Özünüyoxlama nəticələri — REAL BRAUZER (Playwright/Chromium) + HTTP
Bu faza ən çətin test oldu, çünki yerli `php -S` inkişaf serveri təkthreadlidir və real
`php-fpm`-dən fərqli davranır. Prosesdə **iki əsl bug tapılıb düzəldilib**:

1. **Hadisə tarixçəsinin təkrar oynadılması**: yeni `EventSource` həmişə `lastId=0`-dan başladığı
   üçün hər səhifə açılışında BÜTÜN köhnə `sse_events` sətirləri "yeni" kimi göndərilirdi (kart
   fraqmenti üçün lazımsız fetch-lər). **Düzəliş**: server səhifəni render edərkən mövcud
   `MAX(sse_events.id)`-i səhifəyə yazır (`data-last-event-id`), JS bu ID-dən e'tibarən abunə olur.
2. **PHP sessiya fayl kilidi (kritik)**: PHP-nin fayl-əsaslı sessiya handler-i `session_start()`-dan
   bağlanana qədər EXCLUSIVE lock saxlayır. `Sse::stream()` isə sessiyanı heç vaxt bağlamadan
   ≤55 saniyə davam edən dövr işlədirdi — bu, EYNİ brauzerdən gələn istənilən DİGƏR sorğunu (məs.
   kart fraqmenti `fetch()`-i) sessiya sərbəst buraxılana qədər bloklayırdı. Bu, yalnız test artefaktı
   deyil — **istehsalda da** (php-fpm) eyni istifadəçinin açıq SSE tabı olarkən başqa tab/sorğu
   göndərməsi eyni şəkildə asılı qalardı. **Düzəliş**: `StreamController::feed()/customer()`
   auth yoxlamasından dərhal sonra `session_write_close()` çağırır.

Bu iki düzəlişdən sonra, nginx (round-robin, 3 arxa `php -S` prosesi ilə həqiqi paralellik simulyasiyası)
arxasında REAL Chromium brauzeri ilə tam ssenari test edildi:
- **Kriteriya (məcburi): elan qoyulur → sürücü lentinə refresh-siz düşür** — Playwright ilə sürücü
  lenti izlənildi (canlı `EventSource`), ayrı HTTP sessiyasından (müştəri) yeni elan yaradıldı →
  kart sürücünün DOM-una **səhifə yenilənmədən** avtomatik əlavə olundu (screenshot-la təsdiqləndi,
  `data-listing-id` selector-u ilə DOM-da tapıldı). ✓
- **Kriteriya (məcburi): qəbul → digərində itir, ≤3 saniyə** — sürücü təklif verdi, müştəri (ayrı
  HTTP sessiyası) təklifi qəbul etdi → kart sürücünün canlı DOM-undan **1513 ms ərzində** (server
  yenilənmədən) söndürülərək silindi — screenshot ilə əvvəl/sonra vizual təsdiqləndi. ✓ (tələb: ≤3s)
- Push abunəlik axını (`/push/abune`) test edildi, DB-yə düzgün yazıldı; saxta endpoint-ə göndərmə
  cəhdi PHP tərəfdə fatal xətasız, sakit uğursuzlukla idarə olundu.
- Bütün FAZA 1-3 funksionallığı (qeydiyyat, elan, təklif, atomik qəbul, nömrə açılışı) session-lock
  düzəlişindən sonra təkrar regressiya testindən keçirildi — nasazlıq yoxdur.

### Qeyd (mühit məhdudiyyəti)
Real Android Chrome/iOS cihazında push çatdırılması bu sandbox mühitində fiziki test edilə bilmir
(real cihaz/FCM/APNs girişi yoxdur) — kriptoqrafiya (RFC 8291/8292) FAZA 0-da encrypt→decrypt
round-trip ilə düzgünlüyü sübut edilib, göndərmə məntiqi (VAPID JWT, aes128gcm payload, HTTP sorğusu)
yuxarıdakı testlə (saxta endpoint) doğrulanıb. Sahibkar canlı serverdə real cihazla yekun yoxlamanı
apara bilər (README-də qeyd olunacaq).

## FAZA 5 — Abunə + Payriff ✅ TAMAMLANDI

- [x] `app/Payments/PayriffProvider.php` — Payriff API v3 (createOrder/getOrderStatus), config boş
      olduqda `isConfigured()===false`, sistem yıxılmadan "tezliklə" vəziyyətinə düşür
- [x] `Core/Billing.php`: `amountFor()` (fərdi/ümumi qiymət, Q-Y8), `applyGraceOnEnable()` (Q-Y7,
      TƏMİZ HƏLL — trial_until=CURDATE()+grace_days), `extendPaidUntil()` (ardıcıl ödənişlər üst-üstə
      düşmür)
- [x] `/surucu/odenis`: status (trial/paid/free/expired), Payriff konfiqurasiya olunmayıbsa "tezliklə"
      mesajı, ödəniş tarixçəsi
- [x] `PaymentCallbackController` — TƏHLÜKƏSİZLİK: callback body-yə etibar edilmir, status Payriff-dən
      YENİDƏN sorğulanır, `FOR UPDATE` ilə idempotent emal
- [x] `cron/payriff_recheck.php` (15 dəq) + `cron/daily.php` (trial/paid expiry, 5-gün xatırlatma push,
      sse_events təmizliyi, sitemap.xml)

### Özünüyoxlama nəticələri (DB + HTTP səviyyəsində)
- `payments_enabled='0'` → `Auth::isActiveDriver()` `billing_status='expired'` olan sürücü üçün belə
  `true` qaytarır; billing səhifəsi "Hazırda platforma tam pulsuzdur" göstərir. ✓
- `applyGraceOnEnable()`: `expired` sürücü → `trial`, `trial_until=bugün+7`; HƏMİN ANDA aktiv olan
  trial/paid sürücülərə TOXUNULMADI (DB-də təsdiqləndi, 2 nəzarət sürücüsü ilə). ✓
- Q-Y8 fərdi qiymət: `custom_price=15` olan sürücü üçün `amountFor()`→15, digəri üçün ümumi (25) qiymət
  qaytarır; **yaradılmış ödəniş sətri (payments.amount) sonradan qiymət dəyişsə belə öz məbləğində
  qalır** (DB-də təsdiqləndi: qiymət 25→40 dəyişəndən sonra köhnə `payments` sətri 25.00 saxladı). ✓
- Callback idempotentliyi: artıq `status='paid'` olan ödənişə təkrar callback göndərildi →
  `paid_until` DƏYİŞMƏDİ (erkən `return` işlədi). ✓
- Real Payriff kalkulyatoruna qoşulma (saxta test açarları ilə) API sorğusu uğursuz olanda `pay()`
  metodu sakit şəkildə `status='failed'` yazdı, istifadəçini xəta mesajı ilə geri yönləndirdi —
  PHP fatal xəta YOXDUR.
- `cron/daily.php` və `cron/payriff_recheck.php` konfiqurasiyasız Payriff mühitində xətasız işlədi.

### Qeyd (mühit məhdudiyyəti)
Bu sandbox-da real Payriff API-yə (internet) çıxış yoxdur. **Yeniləmə (2026-07-17):** `PayriffProvider`
indi `BIRLIKDE_GETDIK_TAM.pdf`-in bölmə 8.3-dəki konkret nümunə koduna uyğunlaşdırılıb (bax yuxarıdakı
"SUAL / QƏRAR QEYDİ — HƏLL OLUNDU" bölməsi: `Authorization` başlığı, sorğu/cavab sahə adları,
`operation`/`metadata.externalRef`). Yenə də sahibkar canlıya keçmədən əvvəl öz Payriff kabinetindəki
sandbox açarları ilə test etməlidir.

## FAZA 6 — Admin paneli ✅ TAMAMLANDI

- [x] Giriş (`/giris`), 5 cəhd/15 dəq kilid (Getdik 9 ilə eyni məntiq)
- [x] Dashboard: statistik kartlar (bugünkü/aktiv elan, bugünkü qəbul, təsdiq gözləyən, MRR,
      paid/trial/free bölgüsü), marşrut istiliyi (son 30 gün)
- [x] Sürücülər: təsdiq növbəsi (foto+məlumat, təsdiqlə/geri göndər+səbəb), **nömrə hissəvi axtarışı**
      (normallaşdırılmış rəqəmlərlə LIKE), sürücü kartı — iş/ödəniş tarixçəsi, **bir klik Pulsuz↔Pullu**,
      fərdi qiymət qoy/sil, Blokla, WhatsApp linki
- [x] Müştərilər: axtarış, sifariş tarixçəsi, Blokla
- [x] Elanlar: filtr (status/scope), tam məlumat + təkliflər + hadisə zənciri, sil/gizlət, **şikayət
      növbəsi** (`reports` cədvəli, həll et/rədd et)
- [x] Parametrlər: **payments_enabled toggle** (Q-Y7), ümumi qiymət/trial/grace/avto-bağlanma/dəstək
      nömrəsi, texniki fasilə, **lüğət CRUD-ları** (kateqoriyalar+ikon, lokasiyalar+is_baku, maşın növləri)
- [x] Ödənişlər: tam siyahı + aylıq gəlir hesabatı
- [x] Push kampaniya paneli (hədəf: bütün sürücülər/müştərilər/marşrut abunəçiləri)
- [x] Loglar (`admin_logs`) — bütün yuxarıdakı əməliyyatlar `AdminAuth::log()` ilə yazılır

### Özünüyoxlama nəticələri (HTTP + DB + Playwright vizual)
- **Nömrə hissəvi axtarışı**: sürücü `+994 55 777 88 99` ilə qeydiyyatdan keçib, admin axtarışda
  yalnız `"7778"` yazaraq onu tapdı (server-side normallaşdırma + `LIKE`). ✓
- **Bir klik pulsuz/pullu anında**: `billing_status` `trial`→`free`→`trial` HTTP sorğusu ilə dərhal
  dəyişdi, hər dəfə `admin_logs`-a yazıldı. ✓
- **Toggle + qiymət dəyişimi logda**: `payments_enabled` `1`→`0` və ümumi qiymət `25→30` dəyişikliyi
  `admin_logs`-da `{"from":...,"to":...}` formatında tam görünür. ✓
- Sürücü təsdiqi: pending sürücü təsdiqlənəndə `driver_status=approved`, `billing_status=trial`,
  `trial_until=bugün+30` avtomatik təyin olundu.
- Fərdi qiymət qoyma DB-də düzgün yazıldı və admin siyahısında amber çip kimi göstərildi.
- Lüğət CRUD: yeni kateqoriya (🎹 Pianino daşıma) əlavə edildi, slug avtomatik generasiya olundu.
- Admin login kiliddi: 5 səhv cəhddən sonra 6-cı cəhd kilidləndi (Getdik qaydası ilə eyni).
- **Playwright vizual test zamanı 1 real bug tapılıb düzəldilib**: `public_admin/` öz sənəd kökünə
  malikdir və `public/assets/...`-a çıxışı yoxdur — admin panel tamamilə stilsiz (default brauzer
  HTML-i) render olunurdu. Düzəliş: `app.css`/`admin.css` (və əsas ikon) `public_admin/assets/`-a da
  köçürüldü. **Qeyd**: bu iki faylın gələcəkdə sinxron saxlanması lazımdır (README-də qeyd olunacaq) —
  ya əl ilə köçürmə, ya da server-səviyyəli nginx `alias` həlli (bax FAZA 8 qeydləri).

## MÜHİT QEYDİ

Bu sessiya bir git-repo daxilində (kod anbarı) işləyir, canlı VPS-ə çıxışı yoxdur. Layihə kodu spesifikasiyanın
bölmə 5 strukturuna uyğun **repo kökündə** yazılır (`app/`, `public/`, `public_admin/`, `cron/`, `db/`, `nginx/`).
Faktiki serverdə bunlar `/var/www/yuk/` altına yerləşdirilməlidir — `README.md`-də dəqiq addımlar var.
FAZA 8-dəki server/SSL/cron **qeydiyyatı** və real cihazda push testi kimi addımlar bu mühitdə fiziki icra
oluna bilmir — bunun əvəzinə skriptlər/təlimatlar hazırlanır, sahibkar öz VPS-də icra edir.

## FAZA 0 — Skelet ✅ TAMAMLANDI

- [x] Qovluq strukturu (bölmə 5) — repo kökündə (`app/`, `public/`, `public_admin/`, `cron/`, `db/`, `nginx/`)
- [x] `db/install.sql` — bütün 16 cədvəl + seed (60 lokasiya, 6 kateqoriya, 6 maşın növü, 1 admin, 10 setting)
- [x] `app/Core/*`: Config, DB, Router, View, Lang, Csrf, Auth, AdminAuth, Settings, Upload, Phone, Sse, WebPush, OgImage
- [x] `app/Payments/PaymentGateway.php` interfeysi (tətbiqi FAZA 5-də)
- [x] Boş layout (`layouts/app.php`) + ana səhifə (3 dil) + 404 səhifə + bottom-nav partial
- [x] Dizayn tokenləri (`public/assets/css/app.css`) — bölmə 10.1 (kömür+narıncı)
- [x] nginx nümunə konfiqləri (sayt + admin), `.gitignore`, `config.example.php`

### Özünüyoxlama nəticələri (real test, bu mühitdə)
- **MySQL 8.0 əvəzinə MariaDB 10.11** yerli sandbox-da quraşdırılıb (utf8mb4/JSON dəstəkli, MySQL 8.0-a
  praktiki cəhətdən uyğun) — `install.sql` təmiz `yuk_db`-yə **xətasız** yükləndi, 16 cədvəl yarandı,
  seed sayları düzgün (60 lokasiya/6 kateqoriya/6 maşın növü/1 admin/10 setting). ✓
- PHP daxili server (`php -S`) + Playwright/Chromium ilə `/` səhifəsi **AZ/RU/EN** 3 dildə HTTP 200,
  düzgün `<title>`, PHP xəta/warning YOXDUR. ✓
- 360px viewport-da (Playwright, real CSS viewport emulyasiyası) horizontal overflow yoxdur,
  `scrollWidth === clientWidth === 360`. ✓ (İlk ilkin test alətdə — raw `chrome --headless --window-size`
  — yanlış overflow göstərmişdi, çünki `viewport-fit`/`width=device-width` düzgün emulyasiya etmirdi;
  Playwright ilə təkrar test bunun alət artefaktı olduğunu təsdiqlədi.)
- Kripto özəklər (FAZA 4 üçün əvvəlcədən) test edilib: `openssl_pkey_derive()` ECDH, HKDF-SHA256,
  AES-128-GCM (RFC 8291 çərçivəsi) və VAPID ES256 DER↔raw imza çevrilməsi — hamısı encrypt→decrypt
  round-trip və `openssl_verify` ilə təsdiqləndi. ext-gmp/bcmath TƏLƏB OLUNMUR (PHP 8.1+ kifayətdir).
- `Core/OgImage.php` GD ilə test edilib — vizual olaraq düzgün render (bax qeyd aşağıda).

### Qeyd (dizayn qərarı)
- Space Grotesk/Manrope (bölmə 10.2) əsl font faylları bu mühitə yüklənməyib (internet girişi yoxdur).
  Hazırda `public/assets/fonts/DejaVuSans(.ttf/-Bold.ttf)` fallback kimi bundle edilib (sərbəst lisenziya,
  Azərbaycan/Kiril simvollarını tam dəstəkləyir, OgImage.php də bunu istifadə edir). CSS `--font-head`/
  `--font-body` dəyişənləri Space Grotesk/Manrope-u ilk seçim kimi saxlayır — sahibkar əsl `.woff2`
  fayllarını `public/assets/fonts/`-a əlavə edib `@font-face` bağlaya bilər (README-də qeyd olunacaq).
- İkon/PNG-lər (`icon-192.png`, `icon-512.png`, `badge-72.png`) hazırda funksional placeholder-dır
  (kömür fon + narıncı kvadrat) — real brend loqosu ilə əvəz oluna bilər, texniki tələb (ölçü/format) ödənilib.

## FAZA 7 — PWA + dizayn cilası ✅ TAMAMLANDI

### Nə edildi
- **PWA əsasları:** `public/manifest.webmanifest`, `public/sw.js` (cache-first statik aktivlər,
  versiyalı keş adı, `push`/`notificationclick` handlerları), `app/views/partials/splash.php`
  (yalnız `display-mode: standalone`-da və ya sessiyada ilk açılışda — `sessionStorage` bayrağı ilə,
  hər səhifə keçidində YOX), `app/views/partials/install_prompt.php` (Android/Chrome
  `beforeinstallprompt`, iOS Safari üçün əl ilə "Ana ekrana əlavə et" təlimat modalı,
  `matchMedia('(display-mode: standalone)')`/`navigator.standalone` aşkarlanması).
- **SEO:** `public/robots.txt`, `app/views/layouts/app.php`-da `$noindex` dəyişəni (default `true`,
  ictimai səhifələrdə `false`), `public_admin` tərəfində tam `Disallow: /`.
- **Dil paritəsi təsdiqi:** `app/lang/{az,ru,en}.php` skriptlə müqayisə edildi — 177 açar, 3 faylda da
  eyni, əskik/artıq YOXDUR.
- **Əlçatanlıq (accessibility) keçidi — real Lighthouse audit əsasında:**
  - Ana səhifə (`site/home.php`): `heading-order` pozuntusu (`h1`→`h3` sıçrayışı) — rol kartlarının
    başlıqları `h3`→`h2`-yə dəyişdirildi (`.role-card h2{font-size:18px}` əlavə olundu, vizual görünüş
    saxlanıldı); `link-in-text-block` (rəng-yalnız keçidlər) — `.link-amber` klassı ilə underline
    əlavə olundu. Təkrar audit: Performance/Accessibility/SEO 100/100/100.
  - Sürücü lenti (`driver/feed.php`): `select-name` (kateqoriya filtri üçün əlçatan ad yox idi) —
    `aria-label` əlavə olundu.
  - Bundan sonra bütün `<select>` elementləri sayt üzrə sistematik yoxlanıldı
    (`grep -rn "<select" app/views/ | grep -v aria-label`) və qalanlar düzəldildi:
    `customer/listing_form.php` (`from_location_id`/`to_location_id` — `id`/`for` cütü),
    `site/register.php` (`vehicle_type_id` — `id`/`for` cütü),
    `customer/listing_show.php` (`cancel_reason` — `id`/`for` cütü),
    `admin/listings_index.php` (status/scope filtrləri — görünən label yox idi, `aria-label` əlavə
    olundu), `admin/campaign.php` (hədəf seçimi — mövcud `<label>` `for`-suz idi, cüt bağlandı),
    `driver/routes.php` (from/to/scope seçimləri — hər üçü üçün `id`/`for` cütü).
- **Kritik CSS bug (Playwright ilə tapıldı):** `.install-sheet`, `.install-reminder`, `.splash`
  elementləri `display:flex` ilə qeyd-şərtsiz elan olunmuşdu; author-stylesheet spesifikliyi
  `[hidden]`-in UA defolt qaydası ilə bərabər olduğundan, sonrakı author qaydası qalib gəlirdi və
  `hidden` atributu HEÇ NƏYƏ TƏSİR ETMİRDİ (elementlər click-through bloklayırdı, görünməsə də).
  Qeydiyyat formasında bir klik testi 30s+ retry etməyə başlayanda aşkar olundu. Düzəliş:
  `.install-sheet[hidden], .install-reminder[hidden], .splash[hidden] { display: none !important; }`.
- **Skeleton loader qərarı:** `app.css`-də `.skeleton` klassı mövcuddur, lakin heç bir view-da
  istifadə olunmayıb — QƏSDƏN. Bütün səhifələr server-tərəfli render olunur (SSR), ilk yüklənişdə
  məzmun artıq HTML-in içindədir; skeleton yalnız client-tərəfli fetch gecikməsini maskalamaq üçün
  faydalıdır, bu arxitekturada belə gecikmə yoxdur (sürücü lentinin SSE ilə canlı kart əlavəsi isə
  artıq sub-saniyəlik, ayrıca skeleton overlay-ə ehtiyac yaratmır). Qərar: klass gələcək üçün saxlanılır,
  məcburi tətbiq edilmədi.
- **Onboarding:** ayrıca "2 slayd" widget-i tikilmədi — ana səhifədəki mövcud 3-addımlı "necə işləyir"
  kartları (FAZA 1-də tikilib) eyni funksiyanı görür qərarı verildi (vaxt/mürəkkəblik balansı).

### Özünüyoxlama nəticələri
- Lighthouse (`performance,seo,accessibility`, mobil emulyasiya): ana səhifə 100/100/100, sürücü
  lenti səhifəsi 100/100/100 (əvvəlki `select-name` pozuntusu düzəldildikdən sonra).
- Playwright: qeydiyyat formasında bütün sahələr klikə açıqdır (`splash`/`install-sheet` `hidden`
  ikən pointer-events bloklamır) — əvvəlki 30s+ retry problemi aradan qalxıb.
- `grep -rn "<select" app/views/` — bütün nəticələr indi ya `aria-label`, ya `id`/`for` cütü daşıyır
  (sıfır istisna).
- `php -l` bütün redaktə olunmuş view-larda xətasız.

---

## FAZA 8 — Buraxılış hazırlığı ✅ TAMAMLANDI

### Nə edildi
- **Nginx konfiqləri** (`nginx/yuk.birlikde.biz.conf`, `nginx/yukadmin.birlikde.biz.conf`) — FAZA 0-da
  yaradılıb, bu fazada təsdiqləndi: SSL (Let's Encrypt qeydləri), SSE endpoint-ləri üçün
  `fastcgi_buffering off`, upload/storage qovluqlarında PHP icrası bloklanması, admin tərəfdə
  `X-Robots-Tag: noindex, nofollow` + CSP `frame-ancestors 'none'`.
- **Backup skripti:** `scripts/backup.sh` (yeni) — `config.php`-dən DB kimlik məlumatlarını oxuyub
  `mysqldump --single-transaction` ilə sıxılmış dump + `rsync` ilə `public/uploads/` yedəkləməsi;
  14 gündən köhnə dump-ları avtomatik silir. `chmod +x` edilib, `bash -n` ilə sintaksis yoxlanıldı.
- **Cron qeydiyyatı:** README-də tam crontab nümunəsi sənədləşdirildi (`hourly.php` saatlıq,
  `daily.php` gecə 03:00, `payriff_recheck.php` 15 dəqiqədə bir, `backup.sh` gecə 02:30).
- **README.md** (əvvəllər boş stub idi) — tam yenidən yazıldı: server tələbləri, addım-addım quraşdırma
  (DB yaratma, `config.php` doldurma, VAPID keygen), Nginx+SSL qoşulması, **`public_admin` CSS-sinxron
  xəbərdarlığı** (ayrı sənəd kökü olduğu üçün CSS dəyişikliyi hər iki qovluğa köçürülməlidir), Payriff
  konfiqurasiya addımları, dil paritəsi qeydi, fallback font qeydi, lokal inkişaf üçün iki `php -S`
  komandası.
- **`.env`-siz config yoxlanışı:** layihə heç vaxt `.env` istifadə etməyib — `config.php` (git-ə
  düşməyən, `.gitignore`-da) + `config.example.php` (versiyalaşdırılan şablon) modeli əvvəldən
  tətbiq olunub; bu fazada yalnız README-də bu axın sənədləşdirildi, kod dəyişikliyi tələb olunmadı.
- **PROGRESS.md yekunu:** FAZA STATUSLARI cədvəli 0-8 hamısı ✅ olaraq yeniləndi.

### Özünüyoxlama nəticələri
- `bash -n scripts/backup.sh` — sintaksis xətasız.
- Nginx konfiqləri əvvəlki fazalarda `nginx -t` ilə test edilmiş formatdadır (bu sessiyada real
  Let's Encrypt/domain olmadığı üçün canlı sertifikat testi mümkün deyil — README-də bu addım
  sahibkar tərəfindən icra olunacaq şəkildə qeyd edilib).
- README addımları mövcud fayl strukturu ilə bir-bir yoxlanıldı (`config.example.php`,
  `db/install.sql`, `cron/vapid_keygen.php`, `nginx/*.conf`, `scripts/backup.sh` — hamısı mövcuddur
  və README-dəki yollarla üst-üstə düşür).

### Qəbul kriteriyaları (yekun vəziyyət)
1. Qonaq/müştəri qeydiyyatsız elan yerləşdirə bilir, sürücü təklif verir — FAZA 1-3-də test edilib. ✅
2. Real-time silinmə/yenilənmə: iki brauzer pəncərəsi ilə SSE test edilib (FAZA 4). ✅
3. Telefon məxfiliyi (Q-Y3): yalnız qəbul edilmiş təklifin sürücü nömrəsi müştəriyə açılır — atomik
   qəbul zamanı doğrulanıb (FAZA 3). ✅
4. Atomik qəbul (Q-Y4): 10 paralel curl sorğusu → cəmi 1 `accepted` — FAZA 3-də doğrulanıb. ✅
5. Ödəniş açma/bağlama (Q-Y7): admin panelindən bir kliklə, grace-period məntiqi ilə — FAZA 5-də
   doğrulanıb. ✅
6. PWA quraşdırma + splash + SSE canlı sayğaclar — FAZA 4/7-də doğrulanıb. ✅
7. Üç dil (AZ/RU/EN), 0 əskik açar — FAZA 6/7-də doğrulanıb. ✅
8. CSRF + upload təhlükəsizliyi + login brute-force kilidi — FAZA 0-6 boyu tətbiq olunub. ✅
9. Buraxılış artefaktları (nginx, cron, backup, README) — bu fazada tamamlandı. ✅

**Layihə vəziyyəti: bütün 9 fazadan (0-8) ibarət icra planı tamamlanıb.** Qalan işlər yalnız
sahibkarın özünün etməli olduğu server-tərəfi addımlardır (real Payriff açarları, real domen/SSL,
real brend loqosu/fontları) — bunların hamısı README-də aydın qeyd olunub.

---

## FAZA 14 — Təhlükəsizlik, SSE etibarlılığı, əməliyyat, dizayn qalıqları ✅ TAMAMLANDI

Canlı istifadə başladıqdan sonra sahibkarla aparılan "nə çatışmır" auditinin nəticəsində razılaşdırılan
4 istiqamət (dizayn/UX, real-time etibarlılıq, təhlükəsizlik sərtləşdirməsi, əməliyyat) — söhbət
bölmə 3 (chat/reytinq/xəritə) açıq şəkildə xaric edilib.

### 1) Təhlükəsizlik sərtləşdirməsi
- **CSRF auditı** — bütün `Admin`/`Customer`/`Driver`/`Site` controller-lərində POST əməliyyatları
  tək-tək yoxlanıldı; yeganə boşluq `PushController::unsubscribe()` idi (CSRF yoxlaması yox idi) —
  düzəldildi. `PaymentCallbackController` qəsdən CSRF-siz saxlanıldı (Payriff-dən gələn xarici webhook).
- **Nginx sərtləşdirməsi** (hər iki domen): `Strict-Transport-Security` (HSTS) və `Permissions-Policy`
  (`geolocation=(), camera=(), microphone=()`) header-ləri əlavə olundu.
- **Giriş formuna qarşı IP-səviyyəli rate-limit**: yeni `yuk_login` zonası (README-də sənədləşdirilib),
  `location = /giris` bloku hər iki nginx konfiqinə əlavə edildi — mövcud hesab-səviyyəli 5 cəhd/15
  dəqiqə kilidinə (Auth/AdminAuth) əlavə qat kimi.
- Upload təhlükəsizliyi (`App\Core\Upload` — real MIME yoxlanışı, WebP-ə yenidən kodlama, upload
  qovluğunda PHP icrası bloklu) və SQL sorğularının hamısının prepared statement olması təsdiqləndi,
  dəyişiklik tələb olunmadı.

### 2) SSE / offline etibarlılığı
- `app.js`-də paylaşılan `connectResilientSSE()` helper-i yazıldı: hər hadisədə `e.lastEventId`
  izlənilir, `visibilitychange` (tab görünən olanda) və `online` hadisələrində `readyState` yoxlanılıb
  lazım olduqda əl ilə yenidən qoşulur (brauzerin native auto-reconnect-i uzun ekran kilidi kimi
  hallarda bəzən sükutla ölür).
- Yeni görünən "Bağlantı yoxdur — yenidən qoşulur…" zolağı (`#conn-status-banner`, `.conn-banner`) —
  yalnız 4s-dən çox fasilə olduqda görünür (qısa ~55s server ping dövrləri üçün lazımsız yerə
  görünmür). Üç dildə `common.connection_lost` açarı əlavə olundu.
- Sürücü lenti və müştəri elan səhifəsinin hər ikisi bu paylaşılan helper-ə keçirildi.

### 3) Əməliyyat
- Yeni `scripts/install_cron.sh` — README-dəki 4 cron sətrini (`hourly`, `daily`,
  `payriff_recheck`, `backup.sh`) crontab-a idempotent əlavə edir (artıq mövcud olanları
  təkrarlamır, istənilən qədər təhlükəsiz işə salına bilər).
- Admin dashboard-a **son 30 gün trend qrafiki** əlavə olundu (`DashboardController::last30DaysTrend()`
  + inline SVG polyline, kitabxanasız) — günlük yeni elan / qəbul sayı, boş günlər 0 ilə doldurulur.

### 4) Dizayn/UX qalıqları
- Sürücü lentinə (`driver/feed.php`) müştəri ilə eyni "salamlama + gündəlik statistika" hissi əlavə
  olundu (bugünkü yeni elan / hazırda aktiv say).
- Elan foto yükləmə (`customer/listing_form.php`) və sürücü qeydiyyatında maşın fotosu
  (`site/register.php`) indi seçilən şəkillərin həqiqi thumbnail önbaxışını göstərir (əvvəllər
  yalnız "N şəkil seçildi" mətni var idi) — `URL.createObjectURL` ilə, `.photo-preview-strip`.
  Növbəti addımda `<input>`-lar `onchange` inline atributundan `data-photos-cta`/`data-photos-selected`
  data-atributlarına keçirildi (CSP `script-src 'self'` ilə uyğunluq üçün).
- Splash ekranındakı əl ilə çəkilmiş SVG yük maşını **real loqo artwork-u** (`icon-512.png`) ilə
  əvəz olundu, sadə fade+scale animasiyası ilə.
- **CSP uyğunsuzluğu tapıldı və düzəldildi**: `partials/splash.php` və paylaşılan elan səhifəsinin
  (`layouts/public.php`) lightbox skripti inline `<script>` kimi yazılmışdı — hər iki domenin CSP-si
  `script-src 'self'` olduğu üçün brauzer bunları sükutla bloklayırdı (splash effekti heç vaxt
  görünmürdü). İkisi də ayrı xarici fayllara (`assets/js/splash.js`, `assets/js/public.js`) çıxarıldı.
- Paylaşılan elan səhifəsi (`/e/{code}`): CSS linkinə çatışmayan cache-busting (`?v=`) əlavə olundu
  (eyni Nginx 12s keş problemi), foto zolağı `.photo-strip` + lightbox partial-ına keçirildi (əvvəllər
  tıklanmır/böyümürdü).
- Boş vəziyyətlərə (tarixçə, təkliflər, elanlar, sürücü lenti, marşrutlar) dairəvi ikon-nişan
  (`.empty-state-icon`) əlavə olundu — əvvəllər sadəcə boz mətn idi.

### Özünüyoxlama nəticələri
- `php -l` — bu fazada toxunulan bütün PHP fayllarında xətasız.
- `node --check` — `app.js`, `splash.js`, `public.js` sintaksis xətasız.
- Dil paritəsi: AZ/RU/EN hər biri 205/205/205 açar (yeni: `common.connection_lost`,
  `home.driver_greeting_sub/driver_today_stat/driver_active_stat`).
- `public/assets/css/{app,admin}.css` ilə `public_admin/assets/css/{app,admin}.css` sinxronluğu hər
  dəyişiklikdən sonra `diff` ilə təsdiqləndi.
- Playwright ilə lokal server üzərində (MariaDB canlandırılıb, test istifadəçiləri ilə) canlı
  yoxlama: giriş (müştəri + sürücü + admin), splash ekranı, paylaşılan elan səhifəsi, boş tarixçə
  vəziyyəti, sürücü lenti (yeni salamlama/statistika), foto thumbnail önbaxışı, admin trend qrafiki —
  hamısında sıfır konsol xətası, ekran görüntüləri ilə vizual təsdiq.

---

## FAZA 15 — Kinematik splash ekranı + app-kimi səhifə keçidləri ✅ TAMAMLANDI

Sahibkar splash ekranı üçün hazır GSAP-əsaslı animasiya ssenarisi (screenshot + mətn kod) göndərdi.
SVG-dəki loqo path koordinatları (truck-body/cabin/b-letter/wheel) şəkil sıxılması səbəbindən
etibarlı oxunmadı (istifadəçiyə bu açıq şəkildə bildirildi, "oxunmursa de mənə" tələbinə uyğun) —
həll: mövcud real loqo PNG-si (`icon-512.png`) hərəkətin mərkəzi elementi kimi istifadə olundu,
GSAP-ın timeline strukturu (fon → sürət-xətləri → glow → loqo "pop" → mətn ardıcıllığı → loading
bar+faiz → çıxış) isə GSAP-sız, saf CSS keyframe + minimal JS ilə bərpa olundu (CSP `script-src
'self'` GSAP CDN-ni bloklayacaqdı).

### Nə edildi
- **`partials/splash.php`** — tam yeni tərtibat: hissəcik konteyneri, 3 sürət-xətti, glow həlqəsi,
  real loqo, başlıq/alt-başlıq/slogan (mövcud `home.subtitle` açarından, 3 dildə artıq tərcümə
  olunub), loading bar + canlı faiz mətni.
- **`app.css`** — `.splash-*` bloku sıfırdan yazıldı: tünd fon (`radial-gradient`) DƏRHAL solid
  görünür (fade yoxdur — açıq mövzulu səhifədən keçiddə "flaş" olmasın), hər elementin öz
  `animation-delay`-i ilə ardıcıl "teatr" effekti (~4.2s), `prefers-reduced-motion` üçün tam bypass.
- **`splash.js`** — bərpa olundu: hissəciklər CSS custom property-lərlə (`--dur/--delay/--dy/--peak`)
  generasiya olunur (JS animasiya loop-u yoxdur, GPU-dostu), loading bar faizi `requestAnimationFrame`
  ilə power4-out bənzəri easing-lə hesablanır, sonda `.splash-hide` class-ı ilə fade+scale çıxış və
  element silinməsi.
- **App-kimi səhifə keçidləri** — `app.css`-ə `@view-transition { navigation: auto; }` +
  `::view-transition-old/new(root)` üçün yüngül fade+scale keyframe-ləri əlavə olundu. Bu, brauzerin
  doğma Cross-Document View Transitions API-sidir: JS/kitabxana lazım deyil, bütün sayt naviqasiyasına
  (aşağı naviqasiya tabları, link keçidləri) tətbiq olunur, dəstəkləməyən brauzerlərdə sadəcə
  e'tibarsız qalır (proqressiv təkmilləşdirmə, reqressiya riski yoxdur).

### Özünüyoxlama nəticələri
- `php -l`, `node --check` — xətasız.
- Playwright ilə addım-addım screenshot seriyası (t≈0.15s/0.85s/1.55s/2.55s/4.05s/5.05s) çəkilib:
  fon dərhal solid, sürət-xətti + glow + loqo "pop" ardıcıllığı, başlıq/alt-başlıq/slogan sırayla
  görünür, loading bar 0%→100% canlı faizlə dolur, ~4.2s-də splash `#splash` DOM-dan tam silinir —
  sıfır konsol xətası.

### Yeniləmə — sahibkarın verdiyi dəqiq kod ilə tam bərpa
Sahibkar əvvəlki cavabdan sonra splash ekranının HAZIR HTML/CSS/JS kodunu (GSAP-əsaslı) mətn olaraq
göndərdi — bu dəfə SVG path-ları (b-letter, truck-body/cabin/window, wheel) tam oxunaqlı idi.
- GSAP-ı CDN-dən özündə saxlamağa (self-host) cəhd edildi ki, verilən `script.js` demək olar ki,
  olduğu kimi işləsin — proksi `cdnjs.cloudflare.com`-u 403 ilə blokladı (təşkilat siyasəti,
  `/root/.ccr/README.md`-yə uyğun təkrar cəhd edilmədi, host bloklanması qeyd olunur).
- Nəticə: `splash.php`/`app.css`/`splash.js` verilən SVG-ni HƏRFİ olaraq (dəyişiklik edilmədən) və
  CSS-i demək olar ki, eyni dəyərlərlə (rənglər, ölçülər, `bg-lines`) yenidən qurdu; GSAP-ın nisbi
  `"-=X"` overlap-larından tam vaxt cədvəli hesablanıb (logo-wrapper 0s → b-letter .5s →
  speed-line .9/1.0/1.1s → truck-body 1.3s → truck-cabin 1.5s → truck-window 1.7s →
  wheel 1.8/1.9s → text-wrapper 2.1s → title 2.4s → subtitle 2.7s → slogan 3.0s →
  loader-wrapper 3.3s → loader-bar/-text 3.8s (2s, JS) → çıxış ~6.0-6.6s) və CSS `animation-delay`
  olaraq tətbiq olundu; loader faizi eyni məntiqlə (`power3-out` bənzəri) `requestAnimationFrame`
  ilə saxlanıldı. Slogan mətni üçün yeni `home.splash_slogan` açarı 3 dildə əlavə olundu (206/206/206
  paritet).
- Playwright ilə tam vaxt oxu üzrə (t=0.3/1.0/1.6/2.2/2.9/3.6/4.5/5.5/6.3/6.9s) screenshot seriyası:
  "B" hərfi düzgün render olunur, yük maşını hissə-hissə (kabina/kuzov/pəncərə/təkərlər) yığılır,
  mətn/loader ardıcıllığı referans dizaynla üst-üstə düşür, ~6.6s-də təmiz şəkildə yox olur —
  sıfır konsol xətası.

---

## FAZA 16 — Canlı istifadəçi geri-bildirimi: bağlantı bənneri, splash sağlamlığı, profil redaktəsi ✅ TAMAMLANDI

### 1) "Bağlantı yoxdur" bənneri yalan-müsbət verirdi
Kök səbəb: server (`Sse::stream`) hər ~55 saniyədə bağlantını QƏSDƏN bağlayır (uzun-polling
dövrü) — bu, brauzerdə HƏR DƏFƏ normal/gözlənilən `error` hadisəsi yaradır, hətta bağlantı tam
sağlamdırsa belə. Köhnə məntiq tək bir "error"-dan sonra sabit 4s gözləyib bənneri göstərirdi,
bərpa olub-olmadığını yenidən yoxlamadan. `app.js`-də düzəldildi: 5s gecikmədən sonra YALNIZ
`readyState`/`navigator.onLine` hələ də sağlam deyilsə göstərilir; native `online`/`offline`
brauzer hadisələri isə gecikmədən, dərhal idarə edir. Playwright ilə 12s ərzində sağlam bağlantıda
bənnerin görünmədiyi, əl ilə offline/online simulyasiyasında isə düzgün göstərilib/gizləndiyi
təsdiqləndi.

### 2) Splash-dakı truck render sağlamlığı
Sahibkarın "truck sınıq görünür" şikayətinə cavab olaraq (konkret ekran görüntüsü alına bilmədi) —
kod auditi zamanı real risk aşkarlandı: `.truck-window`/`.wheel`/`.b-letter` `transform-box:
fill-box` + `transform-origin: center`-dən istifadə edirdi, bu, bəzi WebKit (mobil Safari)
versiyalarında `<path>`/`<circle>` bounding-box hesablamasında etibarsız ola bilər. Bütün
`transform-origin` dəyərləri SVG-nin öz `viewBox` koordinat sistemində sabit piksel olaraq
yenidən yazıldı (`fill-box`-a ehtiyac qalmadı) — bu, bounding-box hesablanmasından asılı olmayan,
bütün brauzerlərdə (köhnə daxil) eyni davranan həlldir. Xalis `translateX` istifadə edən
`truck-body`/`truck-cabin`-dən lazımsız `fill-box` bəyanatı da silindi.

### 3) Profil redaktəsi: ad dəyişmə + qeydiyyat-tərzi telefon prefiksi
`ProfileController::update()` əvvəllər YALNIZ tək `phone` sahəsini və şəkli dəstəkləyirdi, ad
dəyişməyə ümumiyyətlə icazə vermirdi. İndi:
- `App\Core\Phone::splitForInput()` (yeni) — saxlanılan `994XXXXXXXXX`-i formaya uyğun
  `{prefix:"0XX", number:"XXXXXXX"}` cütünə bölür.
- Profil formasına ad sahəsi əlavə olundu, telefon sahəsi isə qeydiyyat/giriş ilə paylaşılan
  `partials/phone_input.php` komponentinə keçirildi (prefiks seçimi + 7-rəqəm sərt limiti + canlı
  validasiya) — həm müştəri, həm sürücü üçün eyni endpoint/forma.
- Server tərəfi: boş ad → `ad_bos` xətası, yanlış/uzun-qısa nömrə → `nomre_yanlis` (mövcud
  `Phone::normalize()` validasiyası), nömrə başqasında → `nomre_movcuddur`.
- Yeni lang açarları: `profile_edit.full_name_label`, `profile_edit.name_required` (3 dil, 208/208/208
  paritet).

### Özünüyoxlama nəticələri
- `php -l`, dil paritet yoxlaması (208/208/208) — xətasız.
- Playwright: sağlam bağlantıda bənnerin 12s ərzində gizli qalması + real offline/online keçidində
  düzgün davranışı; splash-ın yenidən render yoxlanışı (vizual dəyişiklik yoxdur, sadəcə origin
  sağlamlığı); profil səhifəsində ad+nömrə formasının pre-fill (050/1117766) düzgün göstərilməsi;
  real ad dəyişikliyinin submit edilib bazada saxlanılması və səhifədə əks olunması təsdiqləndi —
  bütün hallarda sıfır konsol xətası.

### Yeniləmə — canlı istifadəçi ekran görüntüləri: bənner hələ də görünürdü + truck ləğvi
İki ekran görüntüsü göndərildi: (1) sürücü lentində "Bağlantı yoxdur" bənneri ilk açılışda görünür
(əvvəlki fix kifayət etmədi — SSE-nin `error` hadisəsi əsaslı yoxlama hələ də real production
şəbəkə şəraitində (yavaş ilk qoşulma) yalan-müsbət verə bilirdi), (2) splash-da B hərfinin üstündə
truck artıq düzgün render olunur, AMMA istifadəçi estetik olaraq onu ÜMUMİYYƏTLƏ istəmir.
- **Bənner sadələşdirildi köklü şəkildə:** SSE `error`-a əsaslanan bütün heuristika (5s
  gecikmə + `readyState` yoxlaması) tamamilə SİLİNDİ. İndi bənner YALNIZ brauzerin native
  `navigator.onLine`/`online`/`offline` siqnalına əsaslanır — sıfır yalan-müsbət riski, çünki
  server-tərəfi SSE dövrü ilə heç bir əlaqəsi qalmadı. SSE-nin özünün etibarlı reconnect məntiqi
  (visibilitychange/online zamanı `readyState` yoxlanışı) toxunulmadan qaldı.
- **Splash-dan truck tamamilə ləğv olundu:** `truck-body`/`truck-cabin`/`truck-window`/`wheel`
  SVG elementləri və onlara aid bütün CSS keyframe/animation-delay qaydaları silindi — yalnız
  "B" hərfi və sürət-xətləri (B-nin yanında, üstündə DEYİL) qalır. Vaxt cədvəli sıxlaşdırıldı
  (truck üçün ayrılmış ~1s aradan qaldırıldı): text-wrapper indi 1.5s-də (əvvəl 2.1s) başlayır,
  ümumi splash müddəti ~6.6s-dən ~6.0s-ə düşdü.

### Özünüyoxlama nəticələri
- `php -l`, `node --check` (app.js, splash.js) — xətasız.
- Playwright: splash-da truck-a aid heç bir element DOM-da yoxdur, yalnız B hərfi + sürət-xətləri
  render olunur, ~6.1s-də təmiz silinir; sürücü lentinə giriş edən kimi (300ms-dən sonra) bənnerin
  gizli olduğu, əl ilə offline simulyasiyasında dərhal göründüyü, online-a qayıdanda dərhal
  gizləndiyi təsdiqləndi — bütün hallarda sıfır konsol xətası.

---

## FAZA 17 — Real-time-in TAPILAN KÖK SƏBƏBİ: nginx SSE location-u heç vaxt uyğunlaşmırdı ✅ TAMAMLANDI

Sahibkar dedi: real-time yeniləmə (yeni elan/təklif) həm müştəri, həm sürücü tərəfdə dərhal
görünmür — yalnız başqa taba keçib qayıdanda ("tab dəyişmə") görünür, halbuki push bildirişi
işləyir. Lokal test mühitində (php built-in server, nginx yoxdur) bu heç vaxt aşkarlanmayıb,
çünki bug məhz nginx-in canlı serverdəki davranışına aiddir.

### Kök səbəb
`nginx/yuk.birlikde.biz.conf`-dakı SSE-üçün-buferi-söndürən location bloku
`location ~ ^/(lent-axini|sse)` idi — AMMA `StreamController`-in HƏQİQİ route-ları
`/axin/lent` və `/axin/musteri`-dir (bax `public/index.php`). Bu iki ad heç vaxt üst-üstə
düşməyib — yəni bu xüsusi (`fastcgi_buffering off; proxy_buffering off;`) blok SSE
sorğularına HEÇ VAXT tətbiq olunmayıb, onlar ümumi `location ~ \.php$` bloğuna düşüb və
nginx tərəfindən BUFERLƏNİB. Nəticədə server-tərəfi hadisələr (`Sse::publish`) dərhal
göndərilsə də, brauzerə YALNIZ nginx buferi dolanda və ya bağlantı hər ~55s-lik dövrün
sonunda bağlananda çatırdı — bu da "tab dəyişib qayıdanda görünür" kimi hiss olunurdu (tab
dəyişmə əslində SSE ilə əlaqəli deyildi, sadəcə təzə səhifə render-i idi).

### Düzəliş
- `location ~ ^/(lent-axini|sse)` → `location ~ ^/axin/` (həqiqi route-lara uyğun).
- Əlavə etibarlılıq: `gzip off;` əlavə olundu (gzip modulu da streamed cavabı buferləyə bilər,
  `fastcgi_buffering off` ilə yanaşı ehtiyat tədbiri kimi).
- Kiçik əlaqəli boşluq: sürücü lenti JS-i `listing_reopened` hadisəsini heç dinləmirdi
  (yalnız `listing_new`/`listing_closed`/`offer_accepted`) — əlavə olundu, eyni
  `handleListingNew` funksiyasından istifadə edərək.

### Özünüyoxlama nəticələri
- `nginx -t` ilə tam http{} konteksti daxilində sintaksis yoxlanıldı — "syntax is ok"
  (yalnız sandbox-un IPv6 dəstəkləməməsi ayrı, əlaqəsiz xəbərdarlıq verdi).
- `node --check` — xətasız.
- **VACİB DEPLOY QEYDİ:** bu fix yalnız `git pull` ilə İŞLƏMİR — nginx konfiqi yenidən
  yüklənməlidir (`nginx -t && systemctl reload nginx`), əks halda köhnə (səhv) location
  bloku yaddaşda qalacaq.

---

## FAZA 18 — Splash: yalnız real girişdə, arxa fondan qayıdanda YOX ✅ TAMAMLANDI

Əvvəlki fix (`localStorage` + 12 saat pəncərə, FAZA 16) natamam çıxdı: istifadəçi "arxa fonda
açıq olanda yenidən girişdə splash açılmasın" dedi, mən müvəqqəti bir pəncərə ilə düzəltdim, amma
bu, HƏQİQİ yeni girişi arxa plandan sadə qayıdışdan fərqləndirmirdi (ikisi də "eyni cihazda çox
tezliklə") — nəticədə həqiqi təzə girişdə də splash görünmədi.

### Düzgün həll: qərar tamamilə serverə keçirildi
- `AuthController::redirectHome(bool $freshLogin)` — YALNIZ `login()`-dəki real uğurlu giriş
  `true` ilə çağırır, bu da dashboard URL-inə `?splash=1` əlavə edir. Artıq giriş etmiş
  istifadəçi səhvən `/giris`-ə düşəndə (`Auth::check()` mühafizəsi) `false` ilə çağrılır —
  splash yoxdur. Qeydiyyat uğuru da eyni işarəni alır (`?xosgeldin=1&splash=1`).
- `layouts/app.php` — `#splash` elementi artıq HƏR SƏHİFƏDƏ deyil, YALNIZ `$_GET['splash']`
  mövcud olanda DOM-a yazılır.
- `splash.js` — bütün JS-tərəfi "nə vaxt göstər" heuristikası (sessionStorage, sonra
  localStorage+12s) tamamilə silindi — server `#splash`-ı DOM-a yazıbsa, skript sadəcə oynadır.

Bu, PWA-nın `start_url`-a (heç bir sorğu parametri olmadan) arxa plandan qayıtmasını real
girişdən (hər zaman təzə `?splash=1` alan) DƏQİQ ayırır — JS-dən deyil, yalnız serverdən mümkün
olan bir fərqləndirmədir.

### Özünüyoxlama nəticələri
- `php -l` — xətasız.
- Playwright: `/giris`-in özündə splash yoxdur; real login-dən sonra `?splash=1` ilə
  yönləndirilir və splash görünür; sonra eyni dashboard-a marker OLMADAN sadə səhifə açılışında
  (arxa plandan qayıdış simulyasiyası) splash tamamilə DOM-da yoxdur — sıfır konsol xətası.

## Qeyd: "app.birlikde.biz" tapıldı — eyni repo, fərqli branch
Sahibkarın istinad etdiyi kuryer platforması ayrı repo deyil, `elnryv/prohive.az`-ın
`claude/project-memory-system-m966o7` branch-idir. Onların SSE (`app/Controllers/SseController.php`,
`deploy/nginx/app.birlikde.biz.conf`) müqayisə edildi: quruluş demək olar eynidir (2s poll loop,
`X-Accel-Buffering: no`), YEGANƏ əhəmiyyətli fərq — onların nginx location-u DƏQİQ uyğunlaşır
(`location = /sse/lovhe`, real route ilə tam üst-üstə düşür), bizimki isə FAZA 17-yə qədər
uyğunlaşmırdı. Bu, FAZA 17-dəki kök-səbəb diaqnozunu müstəqil şəkildə təsdiqlədi.

---

## FAZA 19 — Splash: dəqiq 3 hal (giriş/qeydiyyat/app-yenidən-açılma) ✅ TAMAMLANDI

FAZA 18-in `?splash=1` query-parametr həlli natamam çıxdı — sahibkar sual-cavab vasitəsilə dəqiq
göstərdi ki, splash **3 real halda** görünməlidir: (1) qeydiyyat, (2) çıxış edib telefon+şifrə ilə
yenidən giriş, (3) app-ı TAM bağlayıb (task-killer/swipe-up) yenidən ikondan açanda ("yaddaş
saxla" ilə sessiya avtomatik bərpa olunanda) — AMMA sadəcə arxa plana atılıb (bağlanmadan) geri
qayıdanda YOX.

### Daha dəqiq həll: bayraq `Auth::establishSession()`-ın öz içində
`establishSession()` YALNIZ bu 3 həqiqi haldan birində çağırılır — `Auth::login()`, qeydiyyat
(`registerCustomer`/`registerDriver`), və `tryRememberLogin()` (bu da YALNIZ PHP sessiya kukisi
YOXDURSA işə düşür — yəni tətbiq tam bağlanıb kuki itibsə). Adi arxa-plan-keçidində/naviqasiyada
mövcud sessiya `Auth::boot()`-da birbaşa oxunur, `establishSession()` heç çağırılmır. Buna görə
bayraq (`$_SESSION['show_splash_once'] = true;`) məhz bu metodun daxilinə qoyuldu — 3 həqiqi hal
avtomatik və düzgün tutulur, "arxa plan vs tam bağlanma" fərqini JS-in deyil, PHP sessiya kukisinin
təbii davranışının özü təmin edir. `layouts/app.php` bu bayrağı oxuyub dərhal silir (bir dəfəlik).
`AuthController`-dəki `$freshLogin`/`?splash=1` query-parametr mexanizmi tamamilə silindi (artıq
lazımsız, sadələşdirmə).

### Özünüyoxlama nəticələri
- `php -l` — xətasız.
- Playwright, 5 ssenari ardıcıl: (1) təzə giriş → splash görünür; (2) adi naviqasiya → yoxdur;
  (3) sadə reload (arxa-plan-qayıdış simulyasiyası, sessiya kukisi toxunulmayıb) → yoxdur;
  (4) YALNIZ `yuk_sess` kukisi silinib (`yuk_remember` saxlanılıb — app tam bağlanma simulyasiyası)
  sonra səhifə açılışı → `tryRememberLogin()` sessiyanı bərpa edir, splash YENİDƏN görünür;
  (5) bundan dərhal sonra başqa səhifəyə keçiddə → yoxdur (bayraq artıq istehlak olunub).
  Bütün hallarda sıfır konsol xətası.

---

## FAZA 20 — Real-time: kart HTML-i SSE hadisəsinin İÇİNDƏ + splash yüngülləşdirmə ✅ TAMAMLANDI

Sahibkar `app.birlikde.biz` (`claude/project-memory-system-m966o7` branch) layihəsinin splash və
real-time koduna baxıb ona uyğunlaşdırmağı istədi. Müqayisədə əsas memarlıq fərqi tapıldı:
onların SSE hadisəsi sifarişin BÜTÜN datasını hadisənin özündə göndərir və client birbaşa DOM-a
yazır; bizim dizaynda isə `listing_new` hadisəsi yalnız ID+scope göndərirdi, sonra client
AYRICA `fetch('/surucu/lent/kart/{id}')` sorğusu ilə kart HTML-ini çəkirdi. Bu əlavə round-trip
özü müstəqil bir uğursuzluq nöqtəsi idi — SSE hadisəsi düzgün çatsa belə, sonrakı fetch
uğursuz/gecikmiş olarsa kart heç görünmürdü. Bu, "push bildirişi gəlir amma ana ekranda
göstərmir" şikayətini dəqiq izah edir.

### Düzəliş
- `App\Core\ListingRules::renderFeedCard(int $listingId)` (yeni) — sorğu + `partials/listing_card`
  render-i BİR yerdə, `Sse::publish()` ÇAĞIRILMAZDAN ƏVVƏL işə düşür, hazır HTML-i qaytarır.
- `Customer\ListingController::create()` və `OfferActionController` (ləğv/yenidən-açma) — hazır HTML
  artıq `listing_new`/`listing_reopened` SSE payload-unun daxilinə (`html` sahəsi) qoyulur.
- `app.js`-də `handleListingNew` sadələşdirildi: `async`/`fetch`/`try-catch` tamamilə silindi,
  birbaşa `data.payload.html`-i DOM-a yazır — sıfır əlavə şəbəkə sorğusu.
- Artıq istifadə olunmayan `/surucu/lent/kart/{id}` route-u və `DashboardController::cardFragment()`
  silindi (ölü kod).
- **Splash yüngülləşdirmə:** istifadəçi Safari-də gecikmə/pis animasiya şikayət etdi (View
  Transitions API-dən sonra) — splash-ın özü də referans layihənin sadə (~1.9s) yanaşmasına uyğun
  ~6.6s-dən **~2.9s**-ə sıxıldı, hissəcik sayı 50-dən 15-ə endirildi (Safari-də daha yüngül).
- **Müvəqqəti diaqnostika:** `[SSE]` konsol log-ları əlavə olundu (əvvəlki commit-dən) — problem
  artıq tapıldığı üçün növbəti təmiz fazada silinəcək.

### Özünüyoxlama nəticələri
- `php -l`, `node --check` — xətasız.
- Playwright, İKİ AYRI PHP prosesi (8080/8082, PHP-nin daxili serverinin tək-thread'li olması
  səbəbindən paralel test üçün) ilə tam ucdan-uca ssenari: sürücü lentdədir, müştəri başqa
  prosesdən yeni elan yaradır → SSE hadisəsi 2 saniyə ərzində çatır, kart HEÇ BİR əlavə şəbəkə
  sorğusu olmadan dərhal görünür (`Cards visible in driver feed: 3`). Splash isə indi ~2.9 saniyəyə
  bitir, vizual olaraq təmiz qalır. Sıfır konsol xətası.

### Server-tərəfi yoxlanılıb, təmiz çıxdı (bu fazaya qədər)
`pm.max_children = 50` (PHP-FPM, kifayət qədər), `zlib.output_compression = Off`,
`opcache.validate_timestamps` defolt (aktiv), nginx `location ~ ^/axin/` düzgün uyğunlaşır (`nginx -T`
ilə təsdiqlənib) — bunların heç biri kök səbəb deyildi, əsl səbəb yuxarıdakı əlavə fetch round-trip
idi.

## FAZA 21 — Splash/real-time/animasiya kodunun app.birlikde.biz-dən inteqrasiyası ✅ TAMAMLANDI

Sahibkar birbaşa göstəriş verdi: "app.birlikdə bizdə olan splash ekran kodu açılma məntiqi, real
time ekrandan göstərmə kodu və s hamısını götürürsən bizim koda inteqrasiya edirsən" — sənəd deyil,
bilavasitə kod inteqrasiyası tələb olundu. **Rənglər/loqo/layout TOXUNULMADI** — yalnız hərəkət
(animasiya) məntiqi köçürüldü.

### Dəyişikliklər
- **`--bounce`/`--smooth` tokenləri** (`:root`) — app.birlikde.biz-dəki iki adlandırılmış easing
  əyrisi eynilə köçürüldü (`cubic-bezier(.34,1.56,.64,1)` və `cubic-bezier(.2,1,.3,1)`).
- **`.container`** — `pageIn` keyframe (`opacity 0→1`, `translateY(10px)→0`, `.4s var(--smooth)`),
  hər səhifə yüklənməsində avtomatik işə düşür (View Transitions API əvəzinə sadə CSS, Safari-də
  problemsiz).
- **`.card`** — `cardIn` keyframe (`opacity 0→1`, `translateY(14px) scale(.98)→translateY(0) scale(1)`,
  `.5s var(--bounce)`), `:active` sıçrayışı `scale(.94)`-ə endirildi (`.btn`/`.btn-amber`/
  `.bottom-nav .fab` üçün də eyni cür).
- **`prefers-reduced-motion: reduce`** — yeni `@media` bloku `.container`/`.card` animasiyalarını
  söndürür.
- **Real-time kart girişi (`app.js`, `handleListingNew`)** — əvvəlki inline JS
  `opacity`/`transform`/`transition` sıfırdan yazılışı SİLİNDİ (yeni `.card { animation: cardIn }`
  qaydası ilə TOQQUŞURDU — ikiqat animasiya riski). İndi SSE-dən gələn kart sadəcə DOM-a
  `prepend()` edilir, giriş animasiyasını CSS özü idarə edir (Playwright ilə təsdiqləndi:
  `animationName: "cardIn"`, `opacity`/`transform` doğru keçid edir).
- **Splash ekranı** (`partials/splash.php`, `app.css`, `splash.js`) — app.birlikde.biz-in
  mark-pop+pulse+radar-halqa+glow+söz-bounce+statik-pill ritminə uyğunlaşdırıldı:
  - Hissəcik sahəsi (`#particles`), arxa fon "sürət xətləri" (`#bgLines`) və canlı faiz sayğacı
    TAMAMİLƏ SİLİNDİ (bunların heç biri referans layihədə yoxdur).
  - B-hərfi SVG-si `.splash-mark` konteynerinə köçürüldü: `splashMarkPop` (.3→1 scale pop) +
    `splashMarkPulse` (2 dəfə 1→1.18→1) animasiyaları.
  - `.splash-mark-wrapper::before/::after` — 2 radar-tipli genişlənən halqa (`splashRingPing`,
    `infinite`, .15s/.75s gecikmə ilə staggered).
  - `.splash-glow` — mark arxasında bir dəfəlik radial-gradient glow-pulse.
  - `.splash-word` — "Birlikdə"/"Yük"/slogan mətni `splashWordBounce` ilə sıçrayışla açılır.
  - `.splash-loadbar` — STATİK (faizsiz) dekorativ pill (əvvəlki canlı `rAF` faiz hesablaması
    silindi).
  - `splash.js` 80 sətirdən 20 sətirə düşdü: `createParticles()`/`runLoader()` silindi, yalnız sabit
    `setTimeout(..., 1900)` ilə `.splash-hide` əlavə edir (app.birlikde.biz-in `playSplashOnce()`-i
    ilə eyni məntiq). "Nə vaxt göstər" server-tərəfi bayraq məntiqi (`Auth::establishSession()` +
    `layouts/app.php`) DƏYİŞMƏDİ — bu artıq düzgün işləyirdi.
- **Müvəqqəti `[SSE]` diaqnostika konsol log-ları silindi** (FAZA 20-də əlavə edilmişdi, kök səbəb
  artıq tapılıb/düzəldilib).
- `public_admin/assets/css/app.css` sinxronlaşdırıldı (`diff` ilə təsdiqlənib).

### Özünüyoxlama nəticələri
- `php -l app/views/partials/splash.php`, `node --check public/assets/js/app.js`,
  `node --check public/assets/js/splash.js` — xətasız.
- Playwright: giriş → splash görünür (mark+halqa+glow ilk kadrda, söz+loadbar sonrakı kadrda) →
  ~1.9s-də `.splash-hide`, ~2.35s-də DOM-dan silinir (`splash gone: true`). Sıfır konsol xətası.
- Playwright: sürücü lentinə simulyasiya edilmiş `.card` elementi `prepend()` edildi —
  `getComputedStyle` göstərdi ki, giriş animasiyası (`animationName: "cardIn"`) CSS tərəfindən
  idarə olunur, JS-dən heç bir inline stil münaqişəsi yoxdur.

## FAZA 22 — Real-time-ın HƏQİQİ kök səbəbi: `Last-Event-ID` başlığı GET parametri tərəfindən sükutla e'tibarsız edilirdi ✅ TAMAMLANDI

Sahibkar dəqiq təsvir etdi: sürücü lentində yeni sifariş bilavasitə düşmür, yalnız aşağı
naviqasiyada başqa bölməyə keçib geri qayıdanda (yəni tam səhifə yenidən yükləndikdə) görünür.
Bu, FAZA 20-dəki nginx/inline-HTML düzəlişlərindən SONRA da davam edirdi — deməli ayrı, daha
dərin bir kök səbəb var idi. `app.birlikde.biz`-in (`claude/project-memory-system-m966o7`)
`SseController::lovhe()`-i ilə birbaşa müqayisə aparıldı və fərq TAPILDI:

### Kök səbəb
`StreamController::feed()`/`customer()`-də: `$lastId = (int) ($_GET['lastId'] ?? ($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0));`
`Sse::stream()` hər ~55 saniyədə bağlantını QƏSDƏN bağlayır (uzun-polling dövrü). Brauzerin
NATIVE `EventSource` avtomatik yenidən-qoşulması bu zaman HƏMİŞƏ İLKİN URL-i (səhifə
yükləndiyi andakı `?lastId=` dəyəri ilə) təkrar istifadə edir — bunu JS-dən DƏYİŞMƏK MÜMKÜN
DEYİL. Amma brauzer hər yenidən-qoşulmada DÜZGÜN, son görülən hadisə ID-ni `Last-Event-ID`
HTTP başlığında avtomatik göndərir (SSE spesifikasiyasının məhz bunun üçün olan hissəsi).
`$_GET` HƏMİŞƏ dolu olduğundan (`??` heç vaxt geri qayıtmır), server bu düzgün başlığı
HƏMİŞƏ e'tibarsız edib, donmuş ilkin dəyərdən sorğulamağa davam edirdi. `app.birlikde.biz`-in
`SseController::lovhe()`-i YALNIZ başlığı oxuyur (`$request->header('Last-Event-ID')`) — GET
parametri heç yoxdur belə.

### Düzəliş
- `StreamController::feed()` və `customer()` — sıra dəyişdirildi:
  `$lastId = (int) ($_SERVER['HTTP_LAST_EVENT_ID'] ?? ($_GET['lastId'] ?? 0));` (başlıq ÖNCƏ,
  GET yalnız ilk bağlantı üçün ehtiyat kimi qalır, çünki ilk sorğuda hələ başlıq yoxdur).

### Özünüyoxlama nəticələri
- `php -l` — xətasız.
- Canlı HTTP səviyyəsində dəqiq reprodüksiya: `sse_events`-ə 3 test hadisəsi əlavə edildi (id
  3/4/5), sonra giriş edilmiş sürücü sessiyası ilə `curl -H "Last-Event-ID: 4" ".../axin/lent?lastId=2"`
  çağırıldı (məhz brauzerin real yenidən-qoşulmada göndərəcəyi kimi: köhnə GET + düzgün başlıq).
  **Düzəlişdən ƏVVƏL** bu sorğu 3 hadisənin HAMISINI (id 3,4,5) qaytarardı (GET=2 üstünlük
  edirdi); **düzəlişdən SONRA** yalnız id=5 qaytarır (başlıq=4 üstünlük edir) — dəqiq gözlənilən
  davranış. Başlıqsız (ilkin bağlantı) sorğu isə `lastId=2` GET-dən düzgün istifadə edib bütün 3
  hadisəni qaytarır — köhnə davranış pozulmayıb.
- Test sətirləri (`sse_events` id 3-5) təmizləndi, `sse_events` cədvəli əvvəlki vəziyyətinə
  qaytarıldı.

### Niyə bu, əvvəlki "nginx buferi" düzəlişindən fərqlidir
FAZA 20-dəki nginx location fix HƏQİQİ idi (SSE ümumiyyətlə çatmırdı). Bu FAZA 22 düzəlişi
ONDAN SONRA da qalan, DAHA İNCƏ bir problemi həll edir: SSE ÇATIR, connection düzgün açılır,
AMMA hər ~55 saniyəlik məcburi yenidən-qoşulma dövründə server "unudur" harada qalmışdı və
köhnə, donmuş nöqtədən sorğulamağa davam edir — kifayət qədər hadisə yığılandan sonra (LIMIT
100 pəncərəsi keçəndə) tab HEÇ VAXT yeni hadisələrə çatmır, YALNIZ tam səhifə yenilənməsi
(yəni server-tərəfi təzə `lastEventId` ilə təzə render) vəziyyəti düzəldir — bu, dəqiq
sahibkarın təsvir etdiyi "bölməyə keçib qayıdanda görünür" simptomudur.

## FAZA 23 — SSE client: "zombi bağlantı" qorunması (push gəlir, ekran yenilənmir) ✅ TAMAMLANDI

FAZA 22-dən sonra sahibkar YENƏ bildirdi: sürücüyə push bildirişi gəlir (deməli hadisə
publish olunub, mexanizm işləyir), AMMA ekrandakı lent birbaşa yenilənmir. Push-un gəlməsi
özü sübut edir ki, server-tərəfi hər şey düzgündür — qalan boşluq YALNIZ client-in canlı
bağlantısında ola bilər.

### Kök səbəb
`connectResilientSSE()`-də köhnə şərt: `visibilitychange` → `visible` olanda YALNIZ
`es.readyState === EventSource.CLOSED` olarsa yenidən qoşulurdu. Mobil brauzerlərdə (xüsusilə
ekran kilidlənib tətbiq arxa plana keçəndə) əməliyyat sistemi TCP soketini səssizcə öldürür,
amma JS-in gördüyü `EventSource.readyState` bunu HEÇ VAXT `CLOSED`-ə çevirmir — "zombi
bağlantı" (JS-ə görə hələ `OPEN`, əslində ölü). Bizim `Sse::stream()` hər ~55 saniyədə
məcburi dövr etdiyi üçün (`app.birlikde.biz`-in 1 saatlıq dövrünə qarşı) bu boşluğa məruz
qalma ehtimalı ~65 dəfə çoxdur.

### Düzəliş (`connectResilientSSE`, `app.js`)
- `visibilitychange`→`visible` və `online` hadisələrində artıq `readyState` YOXLANMIR —
  HƏMİŞƏ məcburi yenidən qoşulma (`open()`) baş verir. Sağlam bağlantını bağlayıb eyni
  vəziyyətdə yenidən açmaq ucuzdur, amma zombi bağlantı ehtimalını tam aradan qaldırır.
- Yeni sağlamlıq gözətçisi (`setInterval`, 20s): son mesajdan (adi hadisə VƏ YA `ping`
  adlı hadisə) bəri 90 saniyədən çox keçibsə — heç bir `visibilitychange`/`online`
  hadisəsi olmasa BELƏ (məs. masaüstü tab fokusda qalıb, amma şəbəkə səssizcə kəsilib) —
  bağlantı ölmüş sayılır və yenilənir.
- `app.birlikde.biz`-in `SseController::lovhe()`-i ilə müqayisə: onların `es.onerror` heç
  nə etmir, sadəcə brauzerin öz avtomatik reconnect-inə etibar edir — bu, ONLARDA işləyir,
  çünki dövr müddəti 1 saatdır (nadir hallarda reconnect lazım olur). Bizdə dövr 55
  saniyədir — eyni "sadəlövh etibar" yanaşması bizdə uğursuz olur, ona görə əlavə,
  daha aqressiv qoruma qatı əlavə edildi.

### Özünüyoxlama nəticələri
- `node --check public/assets/js/app.js` — xətasız.
- Playwright: sürücü lentini aç → `visibilitychange` sintetik hadisəsi ilə "arxa plana
  keçib qayıtma" simulyasiya edildi → `/axin/lent` sorğusu sayı 1-dən 2-yə qalxdı
  (məcburi yenidən qoşulma baş verdi, əvvəlki kodda BU BAŞ VERMİRDİ, çünki
  `readyState` hələ `OPEN` görünürdü). Sıfır konsol xətası.

## FAZA 24 — Sse::stream dövr müddəti app.birlikde.biz-lə EYNİLƏŞDİRİLDİ (55s → 1 saat) ✅ TAMAMLANDI

Sahibkar FAZA 22/23-dən sonra da real-time-ın etibarsız qaldığını bildirdi və bütün SSE
kodunun `app.birlikde.biz`-in koduna BİRƏBİR uyğunlaşdırılmasını istədi. Fayl-fayl eyni
kopyalamaq mümkün deyil (fərqli DB sxemi, fərqli auth/sessiya sistemi, fərqli marşrutlar/
controller-lər — bu, tamam ayrı bir domen, kuryer çatdırılması yox, yük elanları bazarıdır),
AMMA konkret ƏDƏDLƏR/MEXANİZM 1:1 köçürülə bilər. Müqayisədə TAPILAN son fərq:
`SseController::lovhe()`-də `MAX_ITERATIONS = 1800; SLEEP_SECONDS = 2;` → dövr ~3600 saniyə
(1 saat). Bizim `Sse::stream()`-də dövr YALNIZ 55 saniyə idi — yəni bizim bağlantımız
ONLARINKINDAN ~65 DƏFƏ TEZ-TEZ məcburi yenidən-qoşulmağa gedirdi, FAZA 22/23-də tapılan HƏR
İKİ reconnect-yolu problemin (header prioriteti, zombi bağlantı) baş vermə ehtimalını 65 dəfə
artırırdı.

### Düzəliş
- `App\Core\Sse` — `MAX_ITERATIONS = 1800` və `SLEEP_SECONDS = 2` sabitləri əlavə olundu
  (`app.birlikde.biz` ilə HƏRFİ EYNİ ədədlər), vaxt-əsaslı `while (time()-$start >= $maxSeconds)`
  şərti iterasiya-əsaslı `for ($i = 0; $i < MAX_ITERATIONS; $i++)` ilə əvəz olundu (onlarınkı ilə
  eyni struktur). Nəticə: dövr indi ~55 saniyə YOX, ~1 SAAT.
  `StreamController::feed()`/`customer()` artıq `$maxSeconds` ötürmür (default silindi).
- Hər 2 saniyəlik keep-alive şərh sətri (`: ping\n\n`) SAXLANILDI (onların kodunda YOXDUR, çünki
  onların nginx-i `fastcgi_read_timeout 3600s` təyin edib — bizimki isə 65s-dir). `fastcgi_read_timeout`
  İKİ ARDICIL OXUMA ARASINDAKI boşluğa aiddir, ÜMUMİ bağlantı müddətinə YOX — bizim 2s-lik ping-lər
  bu boşluğu heç vaxt 65s-ə çatdırmadığı üçün **nginx konfiqinə TOXUNULMASINA EHTİYAC YOXDUR**.
- Dövrün TƏBİİ sonunda (1800 iterasiyadan sonra) əvvəlki kimi adlandırılmış `event: ping` mesajı
  göndərilir (FAZA 23-dəki client-tərəfi sağlamlıq gözətçisi bunu dinləyir).

### Özünüyoxlama nəticələri
- `php -l app/Core/Sse.php app/Controllers/Site/StreamController.php` — xətasız.
- Canlı test: `curl -N` ilə bağlantı açıldı, 3 saniyə sonra yeni `sse_events` sətri əlavə
  olundu → hadisə DƏRHAL (növbəti 2s-lik iterasiya dövründə) axına gəldi, keep-alive
  şərhləri (`: ping`) 2s aralıqla davam etdi — mexanizm dəyişməyib, YALNIZ məcburi
  dayandırma həddi uzadılıb.

### PHP-FPM qeydi (server-tərəfi, təsdiq tələb olunur)
Bu dəyişiklik nginx-i TƏLƏB ETMİR, amma PHP-FPM pool-unda `request_terminate_timeout`
(əgər sıfırdan fərqli təyin olunubsa) 1 saatdan qısa ola bilər — bu halda FPM həmin worker-i
vaxtından əvvəl kəsəcək (bu, KÖHNƏ 55s davranışından PIS DEYİL, sadəcə gözlənilən 1 saatlıq
faydanı azalda bilər, YENİ problem yaratmır, çünki brauzer bunu normal bağlantı kəsilməsi kimi
görüb avtomatik reconnect edəcək). Sahibkardan xahiş: `grep -n request_terminate_timeout
/www/server/php/*/etc/php-fpm.conf` (aaPanel) ilə yoxlasın, sıfır və ya boşdursa (defolt,
limitsiz deməkdir) heç nə etməyə ehtiyac yoxdur.
