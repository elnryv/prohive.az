# "Birlikdə" (app.birlikde.biz, kuryer-çatdırılma) — Real-time/Push/Splash Bug-Fix Sənədi

> Bu sənəd `elnryv/prohive.az` repo-sunun **`claude/birlikde-yuk-implementation-ekg3gv`**
> branch-indəki "Birlikdə Yük" (yük-daşıma bazarı) layihəsində real-time (SSE) bildirişi
> ilə bağlı APARILAN, DOĞRULANMIŞ debugging işinin nəticəsidir. Məqsəd — "Birlikdə" kuryer-
> çatdırılma layihəsinin (`claude/project-memory-system-m966o7` branch-i, `app.birlikde.biz`)
> **canlı lövhə (kurye/lovhe.php) "ana ekranda görsənməmə"** problemini HƏLL etmək üçün bu
> tapıntıları birbaşa tətbiq etməkdir.
>
> **VACİB:** Bu iki layihə TAMAMİLƏ ayrı kod bazasıdır (fərqli DB sxemi, fərqli sinif adları,
> fərqli marşrutlar). Aşağıda HƏR bug üçün: (1) bizdə necə tapıldı/düzəldi, (2) SİZİN konkret
> fayllarınızda (`SseController::lovhe()`, `kurye/lovhe.php`, `SifarisService.php`) bunun
> TƏTBİQ OLUNUB-OLUNMADIĞI (kodunuz artıq oxunub yoxlanılıb) göstərilir ki, lazımsız iş
> görülməsin.

---

## Xülasə cədvəli

| # | Bug | Bizdə (Birlikdə Yük) | Sizdə (Birlikdə/kuryer) — kodunuz yoxlanıldı |
|---|---|---|---|
| 1 | nginx SSE location regex heç uyğunlaşmırdı | ✅ TAPILDI, düzəldildi | ❌ SİZDƏ YOXDUR — `location = /sse/lovhe` DƏQİQ uyğunluqdur, artıq düzgündür |
| 2 | `Last-Event-ID` header GET parametri tərəfindən sükutla e'tibarsız edilirdi | ✅ TAPILDI, düzəldildi | ❌ SİZDƏ YOXDUR — `SseController::lovhe()` YALNIZ header oxuyur, GET parametri heç yoxdur |
| 3 | Safari/WebKit `EventSource` server-in TƏMİZ bağladığı bağlantını avtomatik yenidən açmır | ✅ TAPILDI, düzəldildi | ⚠️ **SİZDƏ VAR** — `es.onerror` boş şərh, brauzerə etibar edir. Bax bölmə 3 — BURA TƏTBİQ OLUNMALIDIR |
| 4 | EventSource-dan asılı olmayan polling ehtiyat mexanizmi | ✅ Əlavə edildi (əlavə etibarlılıq qatı) | 🟡 TÖVSİYƏ OLUNUR (məcburi deyil, bax bölmə 4) |
| 5 | Push bildirişi ilə canlı lentin scope/region filtri uyğunsuz idi | ✅ TAPILDI, düzəldildi | ❌ SİZDƏ YOXDUR — `yeniSifarisBildirisiGonder()` (push) VƏ `lovheYenileri()` (lövhə) HƏR İKİSİ eyni `rayonIds`-a görə filtrlənir, artıq uyğundur |
| 6 | Splash "tam bağlanıb-açılma"da göstərilmirdi (PWA-da sessiya kukisi itmir) | ✅ TAPILDI, düzəldildi | ➖ SİZDƏ FƏRQLİ MEXANİZM — `playSplashOnce()` login-vəziyyətindən ASILI DEYİL (hər tam səhifə yüklənməsində sadəcə vaxt-əsaslı işə düşür), ona görə bu KONKRET bug sizdə YARANMAZ. Amma gələcəkdə splash-ı login-vəziyyətinə bağlasanız, EYNİ tələ sizi gözləyir — bax bölmə 6 |

**Nəticə: sizin real problem üçün ƏSAS düzəliş bölmə 3-dür (Safari EventSource reconnect).**
Bölmə 4 (polling) əlavə təhlükəsizlik qatıdır, məcburi deyil, amma güclü tövsiyə olunur —
çünki EventSource-un HƏR bir brauzer-spesifik kənar halını qabaqcadan bilmək mümkün deyil.

---

## Bölmə 1: nginx SSE location — SİZDƏ PROBLEM YOXDUR

Bizim bug: `location ~ ^/(lent-axini|sse)` regex-i heç vaxt bizim real route-larımıza
(`/axin/lent`, `/axin/musteri`) uyğunlaşmırdı — SSE cavabları ümumi PHP location-a düşüb
nginx tərəfindən buferlənirdi. Düzəliş: `location ~ ^/axin/`.

**Sizin `deploy/nginx/app.birlikde.biz.conf`-da:**
```nginx
location = /sse/lovhe {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root/index.php;
    include fastcgi_params;
    fastcgi_buffering off;
    proxy_buffering off;
    fastcgi_read_timeout 3600s;
    add_header X-Accel-Buffering no;
}
```
Bu, `=` (dəqiq uyğunluq) istifadə edir və REAL route (`/sse/lovhe`) ilə tam uyğundur —
bizim bugumuz sizdə YOXDUR. **Heç bir dəyişiklik lazım deyil.**

---

## Bölmə 2: `Last-Event-ID` header prioriteti — SİZDƏ PROBLEM YOXDUR

Bizim bug: server kodu `$_GET['lastId'] ?? $_SERVER['HTTP_LAST_EVENT_ID']` sırası ilə
oxuyurdu — GET parametri HƏMİŞƏ dolu olduğundan (səhifə yüklənəndə bir dəfə URL-ə yazılır),
brauzerin hər avtomatik reconnect-də göndərdiyi DÜZGÜN `Last-Event-ID` header-i sükutla
e'tibarsız edirdi. Nəticə: uzun açıq qalan tab köhnə, donmuş nöqtədən sorğulamağa davam
edirdi, yeni hadisələrə HEÇ VAXT çatmırdı.

**Sizin `SseController::lovhe()`-də:**
```php
$lastId = (int) ($request->header('Last-Event-ID') ?? 0);
```
Sizin JS-də (`kurye/lovhe.php`) EventSource belə açılır: `es = new EventSource('/sse/lovhe');`
— HEÇ BİR `?lastId=` GET parametri əlavə edilmir. Yəni server YALNIZ header-i oxuyur, GET-dən
override riski MÜMKÜN DEYİL. **Bu bug sizdə struktur olaraq mövcud ola bilməz. Heç bir
dəyişiklik lazım deyil.**

---

## Bölmə 3: Safari/WebKit `EventSource` reconnect etmir — ⚠️ SİZDƏ VAR, BURANI TƏTBİQ EDİN

### Kök səbəb (bizdə tapılan, universal brauzer davranışı)
Chrome/Firefox öz `EventSource`-larını server bağlantını bağlayanda (istər xəta, istərsə
TƏMİZ/normal şəkildə — sizin `SifarisService::lovheYenileri()`-nin MAX_ITERATIONS dövrü
bitəndə etdiyi kimi) AVTOMATİK yenidən qoşulmağa çalışır. **Safari/WebKit İSƏ YOX** — bu,
sənədləşdirilmiş, brauzerlər-arası fərqdir. Bağlantı `readyState = 2` (`CLOSED`) vəziyyətində
ƏBƏDİ qalır, heç nə onu xilas etmir NƏ VAXTA KİMİ istifadəçi tam səhifə keçidi etsin (bu da
SSE-nin bərpası DEYİL, sadəcə yeni səhifə yüklənməsinin cari DB vəziyyətini adi render ilə
göstərməsidir — məhz "tab dəyişəndə görünür" simptomunun əsl izahı budur).

Sizin `kurye/lovhe.php`-də bu KONKRET nöqtə TAM eyni bugı daşıyır:
```js
es.onerror = function () {
  // Bağlantı kəsilərsə brauzer avtomatik yenidən qoşulmağa cəhd edir.
};
```
Bu şərh YANLIŞ fərziyyəyə əsaslanır — Safari-də DOĞRU DEYİL. Sizin dövrünüz 1 saatdır
(`MAX_ITERATIONS`-a bağlıdır), ona görə bu, bizdəki qədər tez-tez (bizdə 55s idi) üzə
çıxmır, AMMA:
- Hər hansı ANİ şəbəkə kəsilməsi (WiFi-dən mobilə keçid, tunel, s.) EYNİ effekti yaradır.
- 1 saat ərzində açıq qalan HƏR kuryer ekranı gec-tez bu vəziyyətə düşür və BİR DƏ HEÇ VAXT
  yeni sifariş görməyəcək (tam səhifə yeniləməyənə qədər).

### Düzəliş (BİRBAŞA tətbiq edilə bilər)
`kurye/lovhe.php`-dəki `connectSse()` funksiyasını belə dəyişin:

```js
var es = null;
var reconnectTimer = null;

function connectSse() {
  if (es) return;
  es = new EventSource('/sse/lovhe');
  es.addEventListener('yeni_sifaris', function (event) {
    var s = JSON.parse(event.data);
    var div = sifarisKarti(s);
    kartlarEl.insertBefore(div, kartlarEl.firstChild);
    baglaGotDuymesi(div, s.id);
    yenileBos();
  });
  es.onopen = function () {
    if (reconnectTimer) { clearTimeout(reconnectTimer); reconnectTimer = null; }
  };
  // DÜZƏLİŞ: Safari/WebKit server-in TƏMİZ bağladığı bağlantını (dövrün təbii
  // sonu) Chrome-dan fərqli olaraq avtomatik yenidən açmır (readyState CLOSED-də
  // əbədi qalır). Əvvəlki kod bunu YALNIZ şərh edirdi, HEÇ VAXT özü yenidən
  // qoşulmurdu. İndi bağlantı bağlananda (1s gecikmə ilə, server müvəqqəti
  // problemlidirsə sürətli dövrün qarşısını almaq üçün) MƏCBURİ yenidən açılır.
  es.onerror = function () {
    if (es.readyState === EventSource.CLOSED) {
      es = null;
      reconnectTimer = setTimeout(connectSse, 1000);
    }
  };
}
```

**Əsas fərq:** `es = null` edib `connectSse()`-i yenidən çağırmaq (funksiyanın özündəki
`if (es) return;` mühafizəsini keçmək üçün lazımdır), 1 saniyəlik gecikmə ilə. Bu, HEÇ bir
server/nginx dəyişikliyi tələb etmir — sırf client JS düzəlişidir.

### Necə doğrulanır (bizim istifadə etdiyimiz metod)
Playwright (və ya əl ilə) ilə: `/sse/lovhe` sorğusunu tutub QISA, dərhal bağlanan bir cavab
qaytarın (server-in təmiz bağlamasını simulyasiya edir), sonra ekranın **HEÇ bir tab-dəyişmə/
naviqasiya OLMADAN** bir neçə saniyə ərzində yenidən qoşulduğunu (yeni `/sse/lovhe` sorğusu
avtomatik getdiyini) təsdiqləyin. Bizdə bu, dəqiq bu simptomu göstərdi və düzəlişdən sonra
~1-2 saniyəyə özünü bərpa etdi.

---

## Bölmə 4: EventSource-dan asılı olmayan polling ehtiyat mexanizmi (TÖVSİYƏ OLUNUR)

Bölmə 3-ün düzəlişindən sonra BELƏ, EventSource-un naməlum, qabaqcadan görünə bilməyən başqa
kənar halları (OS-səviyyəli şəbəkə davranışı, brauzer versiyası, s.) qala bilər. Bizdə bunun
üçün EventSource-dan TAM ASILI OLMAYAN, sadə bir HTTP polling ehtiyat qatı əlavə etdik —
bu, HEÇ bir brauzer-spesifik keyfiyyətdən asılı deyil, sadəcə adi `fetch()` sorğusudur.

### Sizin kodunuza uyğunlaşdırılmış tətbiq
1. Backend-də tək-dəfəlik (loop/sleep OLMADAN) bir sorğu funksiyası:
```php
// SifarisService.php-yə əlavə (lovheYenileri()-nin tək-dəfəlik versiyası artıq elə budur!)
// Sizdə lovheYenileri($kuryeId, $rol, $lastId) onsuz da TƏK sorğu edir (loop YOXDUR) —
// deməli sizə YENİ metod belə lazım deyil, MÖVCUD lovheYenileri()-i JSON kimi qaytaran
// yeni bir route kifayətdir:
```
```php
// SseController.php-yə əlavə:
public function lovheSorgu(Request $request): void
{
    $userId = (int) Session::get('user_id');
    $kurye = (new Kurye())->findByUserId($userId);
    if ($kurye === null) {
        Response::json(['error' => 'Kuryer profili tapılmadı'], 404);
    }
    $lastId = (int) ($request->query('lastId') ?? 0);
    $service = new SifarisService();
    $yeni = $service->lovheYenileri((int) $kurye['id'], (string) Session::get('rol'), $lastId);
    Response::json(['sifarisler' => $yeni]);
}
```
Route: `GET /sifaris/lovhe-sorgu` (və ya oxşar, öz router konvensiyanıza uyğun).

2. Frontend-də (`kurye/lovhe.php`), `connectSse()`-in yanına:
```js
var pollLastId = 0; // ilk yüklənmədə mövcud olan son ID-dən başlamalıdır
setInterval(function () {
  if (document.visibilityState !== 'visible') return;
  fetch('/sifaris/lovhe-sorgu?lastId=' + pollLastId)
    .then(function (r) { return r.ok ? r.json() : null; })
    .then(function (body) {
      var list = (body && body.sifarisler) || [];
      list.forEach(function (s) {
        pollLastId = s.id;
        if (!document.getElementById('sifaris-' + s.id)) {
          var div = sifarisKarti(s);
          kartlarEl.insertBefore(div, kartlarEl.firstChild);
          baglaGotDuymesi(div, s.id);
          yenileBos();
        }
      });
    })
    .catch(function () { /* səssizcə keç, növbəti dövr yenə cəhd edəcək */ });
}, 5000);
```
`pollLastId`-i EventSource-un öz `lastId`-i ilə paylaşmaq istəsəniz (dublikat sorğuların
qarşısını daha səliqəli almaq üçün), hər ikisini eyni closure dəyişənində saxlaya bilərsiniz
— bax bizim `app.js`-dəki `connectResilientSSE(url, initialLastId, handlers, pollUrl)`
funksiyası (real nümunə kodu istəsəniz, ayrıca göndərə bilərəm).

**Xərc:** ən pis halda ~5 saniyəlik gecikmə (yeni sifarişin göründüyü an) — kuryer axtarışı
üçün tamamilə qəbul edilə bilər, EventSource-un HƏR HANSI bir problemi olsa BELƏ iş
DAYANMIR.

---

## Bölmə 5: Push bildirişi ilə canlı lövhənin region filtri — SİZDƏ PROBLEM YOXDUR

Bizim bug: yeni elan haqqında bildiriş BÜTÜN təsdiqlənmiş sürücülərə (scope-dan asılı
olmayaraq) göndərilirdi, amma canlı lent kartı YALNIZ uyğun scope-da göstərirdi — sürücü
bildiriş alır, ekranda heç nə görmür.

**Sizdə:** `yeniSifarisBildirisiGonder()` push üçün namizədləri
`$this->kuryeler->rayonaVeTipeUygunlar($rayonId, $tip, $yukdasimaOlcuId)` ilə seçir (rayon+tip
uyğunluğu), `SifarisService::lovheYenileri()` isə lövhə üçün EYNİ kuryerin
`$this->kuryeBolgeler->getRayonIds($kuryeId)`-dən gələn rayonlara görə filtrlənir. **Hər ikisi
eyni "rayon" konsepsiyasına əsaslanır — uyğunsuzluq YOXDUR.** Heç bir dəyişiklik lazım deyil.

---

## Bölmə 6: Splash "tam bağlanıb-açılma"da göstərilməməsi — SİZDƏ FƏRQLİ MEXANİZM (indi problem deyil, gələcək qeyd)

Bizim bug: splash YALNIZ server-tərəfi sessiya bayrağı (`$_SESSION['show_splash_once']`,
`Auth::establishSession()`-da qoyulur) ilə göstərilirdi, bu bayraq isə YALNIZ PHP sessiya
kukisi YOX olanda (`tryRememberLogin()`) yenidən qoyulurdu. iOS-da (PWA-da) sessiya kukiləri
tətbiqin öz prosesindən ASILI OLMAYAN ayrı bir OS-səviyyəli anbarda saxlanılır — tətbiq
öldürülüb yenidən açılanda kuki demək olar HEÇ VAXT itmir, server "əsl yenidən açılma" halını
HEÇ VAXT görmür.

**Sizin `Birlikde.playSplashOnce()`-də:** bu, login/sessiya vəziyyətindən TAMAMİLƏ ASILI
DEYİL — sadəcə `setTimeout(..., 1900)` ilə hər tam səhifə yüklənməsində (sizin SPA
router-inizin İLK yüklənməsində) işə düşür. Yəni bu KONKRET kuki-əsaslı tələ sizdə STRUKTUR
OLARAQ MÖVCUD DEYİL. **Hazırda heç bir dəyişiklik lazım deyil.**

**AMMA gələcək qeyd:** əgər gələcəkdə splash-ı "yalnız əsl yeni girişdə göstər" məntiqinə
bağlamaq istəsəniz (bizim etdiyimiz kimi), EYNİ tələyə düşəcəksiniz. Həll: server bayrağına
ƏLAVƏ olaraq client-tərəfi `sessionStorage` yoxlaması (kukidən fərqli olaraq, bu, JS icra
konteksti HƏQİQƏTƏN yenidən yaradılanda sıfırlanır):
```js
var forceShow = /* server bayrağı, məs. login-dən sonrakı bir DOM atributu */;
var alreadySeen = false;
try { alreadySeen = sessionStorage.getItem('splash_seen') === '1'; } catch (e) {}
if (!forceShow && alreadySeen) { /* göstərmə */ }
else { try { sessionStorage.setItem('splash_seen', '1'); } catch (e) {} /* göstər */ }
```

---

## Tətbiq sırası (tövsiyə)

1. **Bölmə 3-ü tətbiq edin** (`kurye/lovhe.php`-dəki `connectSse()`) — bu, sizin real
   problemin ƏSAS həllidir, TƏK BAŞINA kifayət edə bilər.
2. Tətbiqdən sonra CANLI test edin: kuryer lövhəni açıq saxlasın (heç yerə keçmədən),
   başqa bir müştəri hesabından sifariş yaratsın — kartın bir neçə saniyə ərzində, tab
   dəyişmədən görünməsini yoxlayın.
3. Əgər bölmə 3-dən sonra BELƏ arabir "görünmür" halları qalırsa (nadir brauzer/şəbəkə
   kənar halları), Bölmə 4-ü (polling ehtiyat mexanizmi) əlavə edin — bu, YÜZ FAİZ
   etibarlılıq təmin edir, EventSource-un HEÇ bir problemi işə qarışmır.
4. Bölmə 1, 2, 5, 6 üzrə HEÇ bir dəyişiklik lazım deyil — kodunuz artıq bu məsələlərdə
   düzgündür (yoxlanılıb).

---

## İstinad — bizim tam düzəliş tarixçəmiz (əlavə kontekst üçün)

Bu bug-ların tam, addım-addım necə tapıldığı və doğrulandığı `elnryv/prohive.az` repo-sunun
`claude/birlikde-yuk-implementation-ekg3gv` branch-indəki `PROGRESS.md` faylının "FAZA 20"
ilə "FAZA 29" arasındakı bölmələrindədir (nginx bugı, header prioriteti bugı, Safari
reconnect bugı, polling mexanizmi, push scope filtri, splash sessionStorage) — hər biri
konkret kod diff-i, test metodologiyası və nəticə ilə sənədləşdirilib.
