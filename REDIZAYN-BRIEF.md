# Birlikdə Yük — Redizayn Brifi (app.birlikde.biz-ə uyğunlaşma)

> Bu sənəd KOD DEYİL — sırf müqayisə və tövsiyə sənədidir. Məqsəd: "Birlikdə Yük"
> (`yuk.birlikde.biz`, bu repo) dizaynını "Birlikdə" (`app.birlikde.biz`, eyni repo-nun
> `claude/project-memory-system-m966o7` branch-i) layihəsinin dizayn sisteminə ("Bənövşəyi
> Şəhər") uyğunlaşdırmaq üçün konkret fərqləri və dəyişməli yerləri sadalamaq. Dizaynı
> sahibkarın özü quracaq — bu sənəd yalnız "haradan başlamalı, nə fərqlidir" sualına cavabdır.
>
> Xüsusi olaraq geniş işlənib: **splash ekranı**, **real-time (SSE) memarlığı** və
> **animasiya fəlsəfəsi** (sahibkarın tələbi ilə).

---

## 1. Ümumi dizayn sistemi müqayisəsi

| | **Birlikdə Yük (indiki)** | **Birlikdə / app.birlikde.biz ("Bənövşəyi Şəhər")** |
|---|---|---|
| Fon | `#F2F5FC` (açıq mavi-boz) | `#f5f5fc` (açıq lavanda) |
| Əsas marka rəngi | `#2F6FED` (mavi) | `#4f46e5` (indiqo) — keçidlər/mətn linkləri |
| Əsas əməliyyat düyməsi | eyni mavi (`--primary`) | `#4338ca` (bir ton tünd indiqo, `--accent-warm`) — **iki fərqli ton**: marka üçün açıq indiqo, düymələr üçün tünd indiqo |
| Təcili/xəbərdarlıq | `--danger` (qırmızı) | `--pink` (`#ff4d8f`, çəhrayı) — "təcili" bunun üçün ayrılıb, qırmızı yalnız həqiqi xəta üçün qalır |
| Uğur/onlayn | `--ok` (yaşıl) | `--success` (`#22c55e`, eyni məntiq) |
| Şrift | `Space Grotesk`/`BY Sans` + sistem sans | `-apple-system, "SF Pro Text", Segoe UI, Roboto` (sırf sistem şrifti, xüsusi web-font yox) |
| Başlıq çəkisi | 700 | **800** (daha qalın), `letter-spacing:-0.01em`, `text-wrap:balance` |
| Künc radiusu | qarışıq (8–16px, `--radius`/`--radius-sm`) | ardıcıl **iri**: kartlar 22px, düymələr 15px, bottom-nav/çip-lər 999px (tam dəyirmi) |
| Kölgə | `--shadow-card` (bir səviyyə) | iki səviyyə: `--shadow-sm` (0 2px 10px) və `--shadow-md` (0 8px 22px) — kontekstə görə seçilir |
| Hərəkət əyrisi | əsasən `ease`/`cubic-bezier(.22,1,.36,1)` | **iki adlandırılmış token**: `--bounce: cubic-bezier(.34,1.56,.64,1)` (sıçrayışlı, düymə/kart giriş üçün) və `--smooth: cubic-bezier(.2,1,.3,1)` (səhifə keçidi üçün) |
| Kart görünüşü | statik, animasiyasız peyda olur | `.card` hər dəfə `cardIn` ilə (opacity 0→1 + translateY(14px)→0 + scale(.98)→1, `--bounce`) TƏK-TƏK canlanır |
| Səhifə konteyneri | statik | `.container` hər yüklənmədə `pageIn` (opacity+translateY) ilə görünür |
| Bottom-nav | 4 sabit tab, aktiv olan rəng dəyişir | **"pill" morfinq**: passiv tab yalnız ikon, aktiv tab genişlənib label göstərir (`max-width` + `padding` keçidi, `--bounce`), aktiv fonu `linear-gradient(120deg, accent, pink)` |

### Konkret dəyər cədvəli (`:root` üçün, birbaşa köçürülə bilər)

```css
:root {
  --bg: #f5f5fc;
  --surface: #ffffff;
  --surface-2: #eeecfb;
  --text: #1a1a2e;
  --text-dim: rgba(26, 26, 46, .62);
  --text-faint: rgba(26, 26, 46, .42);
  --accent: #4f46e5;        /* marka, linklər */
  --accent-warm: #4338ca;   /* əsas əməliyyat düymələri */
  --pink: #ff4d8f;          /* təcili/vurğu */
  --danger: #ef4444;
  --success: #22c55e;
  --warning: #ffb020;
  --bounce: cubic-bezier(.34, 1.56, .64, 1);
  --smooth: cubic-bezier(.2, 1, .3, 1);
}
```

**Qeyd:** bizim `app.css`-də dəyişən adlar fərqlidir (`--primary`, `--ok`, `--danger` və s.) —
birbaşa dəyər əvəzləməsi kifayət edər, dəyişən ADLARINI dəyişməyə ehtiyac yoxdur (bütün view
fayllarında `var(--primary)` istinadları qalır, yalnız `:root`-dakı dəyər yenilənir).

---

## 2. Splash ekranı — ətraflı müqayisə

| | **Bizim indiki (FAZA 15-20)** | **app.birlikde.biz** |
|---|---|---|
| Ümumi müddət | ~2.9 saniyə (əvvəllər ~6.6s idi, sadələşdirilib) | **~1.9 saniyə** (sabit `setTimeout`, heç bir mürəkkəb hesablama yoxdur) |
| Loqo | Real SVG (B hərfi + sürət-xətləri), rise-in animasiyası | Tək dairəvi "mark" (ağ dairə + indiqo hərf), pop + 2 dəfə pulse |
| Fon animasiyası | Hissəciklər (15 ədəd, CSS custom property ilə) + 8 üfüqi "bg-line" zolağı | **Yoxdur** — yalnız mark ətrafında radar-tipli 2 genişlənən halqa (`::before`/`::after`, `border-radius:50%`, `scale(1)→scale(2.4)`) + bir dəfəlik glow pulse |
| Mətn animasiyası | Başlıq/alt-başlıq/slogan ayrı-ayrı `translateY` ilə sıra ilə | Tək söz ("Birlikdə") `scale(.6)→scale(1)` sıçrayışla açılır, alt "tag" mətni sadə fade |
| Loading göstəricisi | Canlı faiz (0%→100%, `requestAnimationFrame` ilə hesablanır) | **Statik pill** — faiz YOXDUR, sadəcə bir "yükləmə zolağı" görünüşü (dekorativ, funksional deyil) |
| Kod mürəkkəbliyi | ~110 sətir CSS + ~70 sətir JS (particle generator, loader hesablama) | ~90 sətir CSS, JS-i CƏMİ 10 sətir (`playSplashOnce`) |
| Nə vaxt göstərilir | Server-tərəfi bayraq (`Auth::establishSession()` — giriş/qeydiyyat/"yaddaş saxla" bərpası) | **HƏR tam səhifə yüklənməsində** (SPA router-in İLK yüklənməsi, bax bölmə 4) — daxili keçidlərdə YOX, çünki daxili keçid heç vaxt tam səhifə yükləməsi deyil |

### Tövsiyə
Bizim son (FAZA 20) sadələşdirmə artıq düzgün istiqamətdədir (uzun teatr effektindən imtina).
Əgər tam app.birlikde.biz səviyyəsinə enmək istənilirsə:
1. Hissəcikləri və faiz sayğacını tamamilə ləğv et — sadəcə mark pop+pulse+radar-halqa+söz
   bounce+statik pill kifayətdir.
2. `--bounce` cubic-bezier-i tətbiq et (bizdəki `cubic-bezier(.22,1,.36,1)` daha "yumşaq",
   onlarınki daha "sıçrayışlı" hiss olunur).
3. Ümumi müddəti ~1.9-2.0 saniyəyə endir (bizdə hazırda 2.9s).

---

## 3. Real-time (SSE) memarlıq fərqi

Bu, YALNIZ dizayn məsələsi deyil, amma sahibkarın xüsusi tələbi ilə buraya daxil edilib:

| | **Bizim (FAZA 20-dən sonra)** | **app.birlikde.biz** |
|---|---|---|
| Transport | SSE, 2s poll loop, DB-də `sse_events` cədvəli | SSE, 2s poll loop, oxşar DB-poll modeli |
| Event payload | Kartın HAZIR HTML-i hadisənin öz içində (`ListingRules::renderFeedCard()`) | Sifarişin xam datası (`s.id`, `s.*`) hadisənin içində, HTML client-side JS funksiyası (`sifarisKarti(s)`) ilə qurulur |
| Bağlantı həyat dövrü | Hər tam səhifə yüklənməsində YENİ `EventSource` (səhifə dəyişəndə əvvəlki bağlanır, yenisi açılır) | **Tək EventSource, SPA router boyu YAŞAYIR** — yalnız `Birlikde.onPageLeave()` ilə əl ilə bağlanır (məs. "onlayn" statusu söndürüləndə) |
| Nginx location | `location ~ ^/axin/` (FAZA 17-də düzəldilib) | `location = /sse/lovhe` (dəqiq uyğunluq, tək endpoint) |

### Tövsiyə
Kart-HTML-i hadisənin içinə qoymaq artıq bizdə də var (FAZA 20) — bu baxımdan artıq
uyğunuq. Əsl fərq **bağlantının həyat dövrüdür**: onlarda SPA router olduğu üçün SSE
bağlantısı səhifədən-səhifəyə "daşınır", bizdə isə hər tam yüklənmədə sıfırdan qurulur.
Bizim çoxsəhifəli (multi-page) memarlığımızda bu, FUNKSİONAL qüsur DEYİL (hər səhifənin öz
canlı yeniləməsi var), sadəcə "istifadəçi Lent səhifəsindən çıxıb-girmədən uzun müddət
qalanda" ssenarisi üçün fərqli davranış deməkdir. Əgər onlarınki kimi "bir dəfə qoşul, harda
olursansa ol davam et" istənilirsə, bu, bölmə 4-dəki SPA router qərarından asılıdır (aşağıya bax).

---

## 4. Səhifə keçidi animasiyaları — ƏSAS MEMARLIQ QƏRARI

Bu, ən böyük fərqdir və **sadəcə CSS/rəng dəyişikliyi ilə həll olunmur**.

### Onlarda: yüngül "SPA router" (client-side naviqasiya)
`app.birlikde.biz`-də `Birlikde.initRouter()` bütün daxili linklərə klikləri tutur, tam səhifə
yükləməsi ƏVƏZİNƏ `fetch()` ilə yeni səhifənin HTML-ini gətirir, DOM-un bir hissəsini
əvəzləyir, `history.pushState` ilə URL-i yeniləyir, köhnə `<script>`-ləri yenidən icra edir.
Nəticə: səhifələr arası keçid HEÇ VAXT ağ ekran/tam yenilənmə görünmür, JS vəziyyəti (məs.
açıq SSE bağlantısı) itmir, keçid öz CSS animasiyası ilə (fade/slide) idarə oluna bilir.

### Bizdə: ənənəvi çox-səhifəli (multi-page) tətbiq
Hər naviqasiya server-ə tam HTTP sorğusudur, brauzer səhifəni sıfırdan yükləyir. Bu, PHP-nin
öz native marşrutlaşdırma modelinə uyğundur (bu layihənin əvvəldən seçilmiş memarlığı) və daha
sadə/etibarlıdır, amma "SPA-hissi" keçid animasiyası (View Transitions API-nin niyə Safari-də
"bərbad" göründüyünü izah edən də elə budur — brauzerin native tam-səhifə-keçid mexanizmi ilə
əl-ələ vermir, texniki cəhətdən uyğun olsa da real cihazda hələ tam sabit deyil).

### Seçim (sahibkar qərar verməlidir)
1. **Sadə variant** (aşağı risk): heç bir SPA router qurma — sadəcə yuxarıdakı rəng/komponent
   dəyişikliklərini tətbiq et, keçid animasiyasından tamamilə imtina et (hazırkı vəziyyət,
   View Transitions artıq silinib). Native app kimi HİSS olunmaz, amma sürətli və etibarlıdır.
2. **Tam SPA router** (yüksək risk/effort): `Birlikde.initRouter()`-in bənzərini qurmaq —
   BÜTÜN səhifələrin fetch+DOM-swap+script-yenidən-icra məntiqinə uyğunlaşdırılmasını, hər
   səhifədəki inline event-listener-lərin təkrar-bağlana bilməsini, SSE bağlantısının
   səhifələr arası daşınmasını tələb edir. Bu, faktiki olaraq kiçik bir SPA framework
   yazmaq deməkdir — köklü, çox fayla toxunan, ayrıca planlaşdırma/təsdiq tələb edən bir işdir.

**Tövsiyə:** əvvəlcə (1)-i tətbiq et (indi buradayıq), sonra ayrıca, aydın razılaşma ilə (2)-yə
keç, çünki bu, "dizayn dəyişikliyi" deyil, yeni bir client-side memarlıq təbəqəsidir.

---

## 5. Prioritetləşdirilmiş addım siyahısı

1. `:root` rəng tokenlərini yuxarıdakı cədvələ uyğun dəyiş (`app.css` + `public_admin/assets/css/app.css`, sinxron saxlanmalıdır).
2. Künc radiuslarını ardıcıllaşdır (kartlar 22px, düymələr 15px, bottom-nav/çiplər 999px).
3. `--bounce`/`--smooth` cubic-bezier tokenlərini əlavə et, mövcud animasiyalara tətbiq et.
4. Bottom-nav-ı "pill morfinq" tərzinə keçir (aktiv tab genişlənir, label görünür).
5. Splash-ı bölmə 2-dəki tövsiyəyə uyğun sadələşdir (istəyə bağlı, hazırkı vəziyyət artıq qəbul edilə bilər).
6. SPA router qərarını AYRICA müzakirə et — bu sənədin əhatə etmədiyi, böyük bir işdir.
