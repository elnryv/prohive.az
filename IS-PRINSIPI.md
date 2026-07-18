# Birlikdə Yük — İş Prinsipi (necə işləyir, kim nə edə bilir)

> `yuk.birlikde.biz` — yük daşıma bazarı: müştəri yük elanı yerləşdirir, sürücülər elana
> baxıb qiymət təklifi verir, müştəri istədiyi təklifi qəbul edir. Üç rol: **Müştəri**,
> **Sürücü**, **Admin** — hər birinin öz giriş nöqtəsi və imkanları var.

---

## 1. MÜŞTƏRİ

### Qeydiyyat/giriş
- Telefon nömrəsi (prefiks + nömrə, məs. `050`+`1234567`) + şifrə ilə qeydiyyat.
- Giriş zamanı "yaddaş saxla" seçimi — 30 günlük HMAC-imzalı cookie, tətbiqi bağlayıb
  yenidən açanda sessiya avtomatik bərpa olunur.
- 5 səhv cəhddən sonra hesab 15 dəqiqəliyə kilidlənir.

### Elan yaratma (`/musteri/elan/yeni`)
Doldurulan sahələr:
- **Kateqoriya** (Ev köçürməsi, Mebel-məişət texnikası, Tikinti materialı, Bölgə yükü,
  Təmir tullantısı, Digər).
- **Haradan / Hara** — şəhər/rayon siyahısından seçim (+ könüllü dəqiq ünvan — bu, YALNIZ
  razılaşdığı sürücüyə deyilir, elanda göstərilmir).
- **Tarix** — "Razılaşma ilə" və ya konkret tarix.
- **Təcili (bu gün-sabah)** bayrağı — bu cür elanlar lentdə "Təcili" çipi ilə fərqlənir.
- **Təsvir** (məcburi) + maks 6 şəkil (könüllü).
- **Qiymət** — yazmasa, sürücülər öz təkliflərini verir.

Sistem bunu avtomatik təyin edir:
- **Scope**: "Haradan" VƏ "Hara" hər ikisi Bakı daxilindədirsə → `baku` ("Bakı daxili"),
  əks halda → `intercity` ("Bölgələrarası"). Sürücü lentində bu iki bölmə ayrı tab-dır.
- **Bitmə vaxtı**: konkret tarix seçilibsə, o tarixdən +1 gün sonu; yoxdursa, admin
  parametrindən gələn saat sayı (defolt 72 saat) sonra elan avtomatik bağlanır.
- Gündə maksimum **5 aktiv elan** limiti.

### Yaradıldıqdan sonra
- Elan dərhal `sse_events` vasitəsilə **bütün açıq sürücü lentlərinə canlı** düşür (bax
  bölmə 4 — real-time).
- **Bütün təsdiqlənmiş sürücülərə** (ödənişli abunəliyi olsun-olmasın, scope-dan asılı
  olmadan) push bildirişi paralel göndərilir.
- Müştəri öz "Elanlarım" siyahısında, elanın öz səhifəsində (`/musteri/elan/{id}`) təklifləri
  görür.

### Təkliflərə baxış və qəbul
- Hər sürücü təklifi: qiymət + qeyd + sürücünün adı/reytinqi göstərilir.
- Müştəri bir təklifi **qəbul edir** → bu, DB-səviyyəsində **atomik** əməliyyatdır (sətir
  kilidi ilə, `FOR UPDATE`) ki, eyni anda iki müştəri/prosess eyni elanı iki dəfə "qəbul
  edilmiş" vəziyyətinə keçirə bilməsin. Qəbul olunandan sonra: elanın statusu `accepted`
  olur, qalan bütün təkliflər `lost` statusuna keçir, seçilən sürücüyə bildiriş gedir.
- Müştəri elanı **ləğv edə** bilər (səbəb seçimi ilə, məs. "sürücü gəlmədi") — bu, elanı
  yenidən `active`-ə qaytarır (+72 saat), əvvəlki (uduzmuş) təkliflər yenidən `pending`-ə
  qayıdır, sürücülərə "elan yenidən açıldı" bildirişi gedir.
- Elanı tam **silmək** və ya müddətini **+72 saat uzatmaq** (yalnız bir dəfə) mümkündür.

### Tarixçə və yenidən sifariş
- `/musteri/tarixce` — keçmiş (bağlanmış/silinmiş) elanların siyahısı.
- "Yenidən sifariş" düyməsi — köhnə elanın bütün sahələrini (marşrut, kateqoriya) əvvəlcədən
  doldurulmuş formaya köçürür, yenidən təsdiqləyib göndərmək kifayətdir.

### Profil
- Ad və telefon nömrəsi redaktəsi (nömrə dəyişəndə qeydiyyat/giriş kimi prefiks+nömrə
  formatı, 7 rəqəm limiti).
- Push bildirişlərini aktivləşdirmə düyməsi (iOS-da toxunma-tələbi ödəmək üçün).

### Paylaşım
- Hər elanın qısa linki var (`yuk.birlikde.biz/e/{kod}`) — sosial şəbəkədə paylaşmaq üçün,
  Open Graph şəkli avtomatik generasiya olunur (marşrut+kateqoriya göstərilir).

---

## 2. SÜRÜCÜ

### Qeydiyyat və təsdiq axını
- Qeydiyyatdan sonra sürücü statusu **`pending`** (gözləmədə) olur — admin təsdiqləməyənə
  qədər lentə giriş bloklanır, ekranda "gözləmədədir" banner-i göstərilir.
- Admin təsdiqləyəndə → **`approved`**, rədd edəndə → **`rejected`** (səbəblə birgə,
  ekranda göstərilir).
- Yalnız `approved` sürücü lentə çıxıb təklif verə bilər.

### Ödəniş/abunəlik (aktivlik statusu)
- `Auth::isActiveDriver()` sürücünün TƏKLİF VERƏ BİLMƏSİNİ müəyyən edir: `approved` +
  bloklanmayıb + (ödəniş sistemi söndürülübsə HƏMİŞƏ aktiv, YOXSA) `billing_status`
  belələrindən biri olmalıdır:
  - `free` — həmişə aktiv (admin tərəfindən pulsuz təyin oluna bilər).
  - `trial` — sınaq müddəti bitməyibsə (`trial_until`) aktiv.
  - `paid` — ödəniş müddəti bitməyibsə (`paid_until`) aktiv.
- Aktiv olmayan sürücü lentə baxa bilir, AMMA təklif verə bilmir — `/surucu/odenis`
  səhifəsindən Payriff ödəniş sistemi ilə abunəni yeniləyə bilər.
- **Push bildirişi bu statusdan ASILI DEYİL** — bütün təsdiqlənmiş sürücülər (aktiv
  olsun-olmasın) yeni elan bildirişini alır (sahibkarın qərarı), çünki bildirişi görüb
  abunə ala bilər.

### Lent ("Canlı lövhə")
- İki tab: **"Bakı daxili"** və **"Bölgələrarası"** (elanın `scope`-una görə ayrılır).
- Kateqoriya üzrə filtr.
- Yeni elan yaradılan kimi **canlı (SSE)** olaraq lentin başında görünür — səhifəni
  yeniləməyə/yenidən açmağa ehtiyac yoxdur (bax bölmə 4).
- Hər kartda: marşrut, kateqoriya, "Təcili" çipi (varsa), qısa təsvir, neçə vaxt əvvəl
  yaradıldığı, neçə təklif olduğu.
- Elan bağlananda (silinəndə/qəbul olunandan sonra) kart lentdən canlı silinir.

### Təklif vermə
- Elana daxil olub qiymət + qeyd yazır → təklif `pending` statusunda müştəriyə görünür.
- Eyni elana YENİDƏN təklif versə, köhnə təklifi YENİLƏNİR (təkrarlanmır).
- Təklifi **geri çəkə** bilər (`pending` olduğu müddətcə).
- Müştəri qəbul edərsə → sürücüyə bildiriş, "Təkliflərim"də statusu `accepted` olur.

### Marşrut abunəlikləri
- `/surucu/marsrutlar` — sürücü öz maraq bölgəsini (haradan/hara, ya da sadəcə
  Bakı-daxili/Bölgələrarası/hamısı) qeyd edə bilər, maks 5 marşrut.
- (Hazırda push bildirişinə TƏSİR ETMİR — sahibkarın qərarı ilə bütün təsdiqlənmiş
  sürücülərə hər elan bildirişi gedir, bu abunəlik sırf gələcək filtrasiya üçün
  infrastrukturdur.)

### Təkliflərim / Tarixçə / Profil
- "Təkliflərim" — özünün verdiyi bütün təklifləri statusuna görə görür (gözləmədə/qəbul
  olunub/uduzub).
- Tarixçə — keçmiş, artıq bağlanmış elanlarla bağlı fəaliyyəti.
- Profil — ad/nömrə redaktəsi, push aktivləşdirmə, ödəniş/abunəlik statusu.

---

## 3. ADMİN (`admin.` alt-domeni, ayrı panel)

Ayrı giriş sistemi (AdminAuth, sayt istifadəçilərindən TAM ayrı sessiya/autentifikasiya).

### İdarəetmə bölmələri
- **Dashboard** — ümumi statistika/trend qrafiklər.
- **Sürücülər** (`/surucular`) — siyahı, hər sürücünün profili; **təsdiqləmə/rədd etmə**
  (qeydiyyatdan sonrakı `pending` statusunu həll edir); ödəniş tipini (free/trial/paid)
  və qiymətini əl ilə dəyişmə; **bloklama**.
- **Müştərilər** (`/musteriler`) — siyahı, profil, bloklama.
- **Elanlar** (`/elanlar`) — bütün elanların siyahısı, ətraflı baxış, silmə, şikayət
  bayraqlama.
- **Parametrlər** (`/parametrler`) — ödəniş sisteminin qlobal aç/bağla, ümumi ayarlar
  (məs. elanın avtomatik bağlanma saatı), texniki fasilə rejimi (bax aşağı), admin
  şifrəsini dəyişmə, lüğət cədvəllərinin (kateqoriya/lokasiya) idarəsi (aktiv/passiv).
- **Ödənişlər** (`/odenisler`) — Payriff vasitəsilə edilən bütün ödəniş tranzaksiyalarının
  siyahısı.
- **Kampaniya** (`/kampaniya`) — bütün (və ya seçilmiş) sürücülərə əl ilə kütləvi push
  bildirişi göndərmə.
- **Loglar** (`/loglar`) — admin əməliyyat tarixçəsi (kim, nə vaxt, nə etdi).
- **Bannerlər** (`/bannerler`) — əsas ekranda görünən carousel banner-lərin idarəsi
  (yükləmə, aktiv/passiv, silmə).

### Texniki fasilə rejimi
- Admin saytı müvəqqəti "texniki fasilədədir" vəziyyətinə sala bilər — bu zaman adi
  istifadəçilər üçün sayt bağlıdır, YALNIZ admin girişi işləyir (deploy/baxım zamanı).

---

## 4. Real-time (canlı yeniləmə) necə işləyir — bütün rollara ortaq mexanizm

- Hər əhəmiyyətli hadisə (yeni elan, yeni təklif, qəbul, ləğv/yenidən-açılma, bağlanma)
  DB-dəki `sse_events` cədvəlinə yazılır (`Sse::publish()`).
- Sürücü/müştəri ekranı açıq olduğu müddətcə brauzer bir **Server-Sent Events (SSE)**
  bağlantısı saxlayır (`/axin/lent` sürücü üçün, `/axin/musteri` müştəri üçün) — server bu
  bağlantı üzərindən uyğun kanaldakı (feed/driver_{id}/customer_{id}) yeni hadisələri
  DƏRHAL push edir, səhifə yenilənmədən.
- Bağlantı server tərəfindən hər ~1 saatda bir təbii şəkildə bağlanıb yenidən açılır
  (uzun-polling dövrü) — brauzer bunu avtomatik idarə edir.
- Bundan ƏLAVƏ: yeni elan/təklif zamanı **Web Push** bildirişi də göndərilir (tətbiq
  bağlı olsa belə telefon bildiriş mərkəzində görünür) — sürücülərə paralel (eyni anda
  hamıya) çatdırılır.

---

## Əsas fayl/nöqtələr (istinad üçün)

| Nə | Fayl |
|---|---|
| Müştəri elan/təklif | `app/Controllers/Customer/ListingController.php`, `OfferActionController.php` |
| Sürücü lent/təklif/ödəniş | `app/Controllers/Driver/DashboardController.php`, `OfferController.php`, `BillingController.php`, `RouteSubscriptionController.php` |
| Admin | `app/Controllers/Admin/*.php`, ayrı `public_admin/` qovluğu |
| Auth (rol, aktivlik) | `app/Core/Auth.php` |
| Elan qaydaları (scope, bitmə vaxtı) | `app/Core/ListingRules.php` |
| Real-time | `app/Core/Sse.php`, `app/Controllers/Site/StreamController.php`, `public/assets/js/app.js` |
| Push bildirişi | `app/Core/WebPush.php` |
| Marşrutlar (tam siyahı) | `public/index.php` (sayt), `public_admin/index.php` (admin) |
