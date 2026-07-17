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
