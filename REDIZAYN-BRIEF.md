# Birlikdə Yük — Layihə Sənədi: Splash / Real-time / Animasiya (app.birlikde.biz-ə uyğun)

> **Əhatə dairəsi:** YALNIZ bu üç mövzu: (1) splash ekranı, (2) real-time (SSE) davranışı,
> (3) animasiya fəlsəfəsi (əyri/vaxt tokenləri, kart/düymə/naviqasiya hərəkəti).
> **Əhatə xaricində (TOXUNULMAYACAQ):** rəng palitrası, tipoqrafiya, komponent görünüşü,
> ümumi layout — bunlar hazırkı vəziyyətdə (`--primary: #2F6FED` mavi tema) qalır.
>
> Bu sənəd `elnryv/prohive.az` repo-sunun `claude/project-memory-system-m966o7` branch-indəki
> "Birlikdə" (`app.birlikde.biz`) layihəsinin real kodu ilə birbaşa müqayisə edilərək
> hazırlanıb. Məqsəd — icraçının (Claude və ya insan) bu sənəddən BİRBAŞA tətbiq edə bilməsi.

---

## 1. Splash ekranı

### Hədəf davranış (app.birlikde.biz-dən)
- Ümumi müddət: **~1.9 saniyə** (indiki ~2.9s-dən qısa).
- Ardıcıllıq: mark (loqo) `scale(.3)→scale(1)` ilə "pop" edir → **2 dəfə pulse** (`scale(1)→1.18→1`) →
  mark ətrafında **2 radar-tipli genişlənən halqa** (bir-birindən 0.6s gecikmə ilə, `scale(1)→2.4`,
  `opacity 0.65→0`, `infinite` təkrarlanır splash görünən müddətdə) → mark arxasında bir dəfəlik
  **glow pulse** (radial-gradient, `scale(.2)→1.35`, `opacity 0→1→.55`) → mətn (bizdə "Birlikdə"/"Yük"
  iki sətir) `scale(.6)→scale(1)` ilə sıçrayışla açılır → slogan sadə fade-in → aşağıda **statik**
  (faizsiz) yükləmə zolağı pill-i.
- **Silinməli/əlavə edilməməli elementlər** (sadələşdirmə üçün): hissəcik sahəsi (particle field),
  canlı faiz sayğacı (`0%→100%`), arxa fon "sürət xətləri" (bg-lines) — bunların heç biri
  app.birlikde.biz-də yoxdur, bizim tərəfimizdə silinməlidir.
- Rənglər DƏYİŞMİR — bizim mövcud mavi (`#2F6FED`) + tünd fon (`radial-gradient` qaralıq)
  saxlanılır, YALNIZ HƏRƏKƏT RİTMİ app.birlikde.biz-ə uyğunlaşdırılır.

### Konkret CSS keyframe dəyərləri (rəngsiz, sırf hərəkət — birbaşa köçürülə bilər)
```css
@keyframes splashMarkPop { to { opacity: 1; transform: scale(1); } }
@keyframes splashMarkPulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.18); } }
@keyframes splashRingPing { 0% { opacity: .65; transform: scale(1); } 100% { opacity: 0; transform: scale(2.4); } }
@keyframes splashGlowPulse {
  0% { opacity: 0; transform: scale(.2); }
  35% { opacity: 1; }
  100% { opacity: .55; transform: scale(1.35); }
}
@keyframes splashWordBounce { to { opacity: 1; transform: scale(1); } }
```
Mark: `animation: splashMarkPop .5s var(--bounce) forwards, splashMarkPulse .5s ease-in-out .5s 2;`
Halqalar (`::before`/`::after`): `animation: splashRingPing 1.8s ease-out .15s infinite;` (ikinci
halqada `animation-delay: .75s`).
Söz: `animation: splashWordBounce .6s var(--bounce) .7s forwards;` (`--bounce` üçün bax bölmə 3).

### Fayllar
- `app/views/partials/splash.php` — SVG/loqo strukturu qalır, particle/bg-lines `<div>`-ləri silinir.
- `public/assets/css/app.css` — `.splash .particle`/`.splash .bg-lines` bloku silinir, yuxarıdakı
  yeni keyframe-lər əlavə olunur, loader-bar statik pill-ə sadələşir (JS-dən faiz hesablaması silinir).
- `public/assets/js/splash.js` — `createParticles()`/`runLoader()` funksiyaları silinir, yalnız
  sabit `setTimeout(..., 1900)` ilə `.splash-hide` əlavə edən 10 sətirlik məntiq qalır (bax
  app.birlikde.biz-in `playSplashOnce()` — bölmə 4-də tam kod nümunəsi).
- `public_admin/assets/css/app.css` — sinxron köçürülməlidir (mövcud qayda).

**Nə vaxt göstərilir — DƏYİŞMİR:** server-tərəfi bayraq məntiqi (`Auth::establishSession()` +
`layouts/app.php`-dəki `$_SESSION['show_splash_once']` yoxlaması) olduğu kimi qalır — bu, bizim
çox-səhifəli memarlığımıza görə artıq düzgün işləyir (yalnız giriş/qeydiyyat/"yaddaş saxla"
bərpasında görünür), app.birlikde.biz-in "hər tam SPA yüklənməsində" məntiqindən fərqlidir, amma
EYNİ NƏTİCƏYƏ aparır (bizdə server dəqiq bilir "bu, əsl yeni giriş", onlarda router bilir "bu,
tam səhifə yükləməsi").

---

## 2. Real-time (SSE)

### Hazırkı vəziyyət — artıq uyğundur
FAZA 20-də edilən dəyişiklik (`ListingRules::renderFeedCard()` — kartın hazır HTML-i SSE
hadisəsinin `payload.html`-i daxilində göndərilir, client əlavə `fetch()` etmir) artıq
app.birlikde.biz-in yanaşması ilə EYNİ prinsipdədir (onlar da xam data göndərib client-side
render edir, biz isə server-side render edib hazır HTML göndəririk — nəticə eynidir: TƏK
SSE mesajı kifayət edir, ikinci şəbəkə sorğusu yoxdur). **Bu hissədə əlavə iş tələb olunmur.**

### Fərq qalan yeganə məqam (icraçı qərar üçün qeyd olunur, MƏCBURİ deyil)
app.birlikde.biz-də SSE bağlantısı bir dəfə açılıb SPA router vasitəsilə səhifədən-səhifəyə
"daşınır" (bağlanmır). Bizdə hər tam səhifə yüklənməsi yeni bağlantı açır. Bu, bizim
çox-səhifəli (multi-page) memarlığımızın DOĞAL NƏTİCƏSİDİR — düzəldilməli "bug" deyil. Əgər
bu davranış da tam köçürülmək istənilirsə, bu, SPA router qurulmasını tələb edir (bax bölmə 3-ün
sonu) — İCRAÇI BUNU ÖZBAŞINA HƏYATA KEÇİRMƏMƏLİDİR, ayrıca təsdiq lazımdır.

---

## 3. Animasiya fəlsəfəsi (əyrilər/vaxt tokenləri)

app.birlikde.biz iki adlandırılmış "easing" tokeni istifadə edir — bunlar RƏNGDƏN ASILI DEYİL,
birbaşa köçürülə bilər:

```css
:root {
  --bounce: cubic-bezier(.34, 1.56, .64, 1); /* sıçrayışlı: kart/düymə/mark giriş */
  --smooth: cubic-bezier(.2, 1, .3, 1);      /* yumşaq: səhifə konteyneri giriş */
}
```

### Tətbiq ediləcək yerlər (bizim mövcud sinif adları saxlanılır, YALNIZ `transition`/`animation`
easing dəyəri dəyişir):
- `.card:active` / `.btn:active` → `transform:scale(.94)` + `transition: transform .12s var(--bounce)`
  (bizdə hazırda sadə `ease`/`scale(.98)` istifadə olunur, daha "yastı" hiss olunur).
- Kartların səhifəyə ilk gəlişi (`.card`) → yeni `cardIn` keyframe (`opacity 0→1`,
  `translateY(14px)→0`, `scale(.98)→1`, `.5s var(--bounce)`), TƏK-TƏK sıra ilə (mövcud statik
  görünüşün əvəzinə).
- `.container` səhifə girişi → yeni `pageIn` keyframe (`opacity 0→1`, `translateY(10px)→0`,
  `.4s var(--smooth)`) — HƏR səhifə yüklənməsində, tam səhifə keçidi olsa belə (View Transitions
  API əvəzinə sadə CSS `animation`, əlavə brauzer API-si lazım deyil, Safari-də sınanmış problem
  yaratmır).
- Bottom-nav aktiv tab keçidi → `padding`/`max-width` keçidi `.35s var(--bounce)` ilə (bax
  app.birlikde.biz `.bottom-nav-item.active` — "pill morfinq": passiv tab yalnız ikon, aktiv tab
  genişlənib label göstərir). Bizim bottom-nav-ın rəngi/forması DƏYİŞMİR, yalnız bu keçid
  davranışı əlavə oluna bilər (istəyə bağlı, əhatə dairəsində "animasiya" kimi sayılır).

### Bunun SƏBƏBİ (əvvəlki problemin izahı)
Sahibkarın "bölmələr arası keçidlərdə olan animasiya yoxa çıxıb, hər şey bir-birinə qarışıb"
şikayəti View Transitions API-nin (əvvəllər əlavə edilmiş, artıq silinmiş) Safari-də uyğun
işləməməsindən qaynaqlanırdı. Yuxarıdakı `pageIn`/`cardIn` yanaşması BRAUZER API-sinə əsaslanmır
(sırf CSS `@keyframes`, hər səhifə öz HTML-i daxilində DOM yükləndiyi anda avtomatik işə düşür) —
bu, Safari daxil BÜTÜN brauzerlərdə eyni, proqnozlaşdırıla bilən şəkildə işləyir.

---

## 4. `splash.js` üçün tam yeni kod (referans, birbaşa uyğunlaşdırılıb)

```js
(function () {
  var splash = document.getElementById('splash');
  if (!splash) return;

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  setTimeout(function () {
    splash.classList.add('splash-hide');
    setTimeout(function () { splash.remove(); }, 450);
  }, reduceMotion ? 0 : 1900);
})();
```
(Bizim `#splash`-ın DOM-a nə vaxt yazılacağını serverin (`layouts/app.php`) həll etdiyini
xatırlat — bu fayl yalnız artıq DOM-da olan elementi 1.9s sonra gizlədir.)

---

## 5. Fayl-üzrə dəyişiklik siyahısı (xülasə)

| Fayl | Dəyişiklik |
|---|---|
| `public/assets/js/splash.js` | Bölmə 4-dəki sadə versiya ilə əvəz olunur (particle/loader kodu silinir) |
| `app/views/partials/splash.php` | `#particles`/`.bg-lines` markup-u silinir, `.loader-text` (faiz) silinir, statik pill saxlanılır |
| `public/assets/css/app.css` | `.splash .particle`/`.bg-lines` qaydaları silinir; bölmə 1-dəki yeni keyframe-lər + bölmə 3-dəki `--bounce`/`--smooth` tokenləri və tətbiqləri əlavə olunur |
| `public_admin/assets/css/app.css` | yuxarıdakı CSS ilə sinxronlaşdırılır (mövcud tələb) |
| SSE/`ListingRules.php`/`app.js` (real-time) | **dəyişiklik lazım deyil** — artıq uyğundur (bölmə 2) |
| Rəng dəyişənləri (`:root` — `--primary`, `--bg` və s.) | **TOXUNULMUR** |
