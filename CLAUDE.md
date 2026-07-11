# BİRLİKDƏ — Layihə Yaddaşı (CLAUDE.md)

Bu fayl hər sessiyanın başında oxunur. Tam texniki detallar üçün `docs/PROJECT_SPEC.md`-ə bax (bölmə nömrələri ilə), amma onu yalnız lazım olanda, konkret bölməni axtararaq oxu — hamısını əzbərə yükləmə. İş vəziyyəti üçün `PROGRESS.md`-ə bax.

## Layihə Xülasəsi

**Birlikdə** (app.birlikde.biz) — kuryer/yükdaşımaçı ilə müştərini əlaqələndirən vasitəçi platforma. Platforma çatdırılmaya/ödənişə qarışmır; sifariş götürüləndə tərəflərə bir-birinin WhatsApp linki açılır, qalanı WhatsApp-da baş verir. Gəlir yalnız kuryer aylıq abunəsindən (müştəri üçün tamamilə pulsuz, komissiya yoxdur).

Üç rol: `musteri`, `kurye` (adi çatdırılma), `yukdasima` (ağır yük, XS–Mega ölçü kateqoriyaları). Admin ayrı subdomendə (appadmin.birlikde.biz), ayrı cədvəldə.

## Texnologiyalar

- Backend: Native PHP 8.3, framework YOX, hər faylda `declare(strict_types=1)`.
- DB: MySQL 8.0, InnoDB, utf8mb4_unicode_ci, PDO prepared statements (heç vaxt raw interpolation).
- Real-time: SSE (Server-Sent Events) — kuryer lövhəsi.
- Frontend: Vanilla JS + PWA (Service Worker, manifest, iOS+Android ana ekrana əlavə).
- Giriş: telefon+parol (`password_hash`/`password_verify`). **OTP/SMS YOXDUR.**
- Ödəniş: `PaymentProvider` interfeysi, Birbank/Payriff adapterləri (yarım-avtomatik, recurring yox).
- Server: Nginx + PHP-FPM 8.3, Hostinger VPS Ubuntu.
- Dizayn: OLED qara (#000000), glassmorphism, SF Pro. Çoxdilli: AZ (default)/RU/EN.

## ⚠️ Bilinən Ziddiyyət (Qərar Verilmiş)

Mənbə sənəddə köhnə OTP/SMS izləri qalıb (Faza 2 təsviri, `POST /otp/dogrula` endpoint-i, "nömrə dəyişmə OTP təsdiqi ilə"), amma bölmə 2.2/2.3/3.1 açıq şəkildə "OTP/SMS YOX" deyir. **Qərar: OTP/SMS tətbiq edilməyəcək** — bütün auth nömrə+parol ilədir. Bu barədə əlavə sual vermə, sadəcə tətbiq et.

## İş Qaydaları (Session Protokolu)

1. **Hər sessiyanın əvvəlində**: bu faylı və `PROGRESS.md`-i oxu ki, harada qaldığımızı bil. `docs/PROJECT_SPEC.md`-i tam oxuma — yalnız işlədiyin bölməni axtar (məs. "4.6" və ya "sifarisler" grep et).
2. **Kod yazarkən ekrana tökmə** — birbaşa fayl strukturuna uyğun yarat/redaktə et (Write/Edit alətləri ilə). Yalnız qısa hesabat ver: `Fayl: app/Models/Sifaris.php yaradıldı və kodu yazıldı.`
3. **`.env` üçün həmişə placeholder** istifadə et (real açar/parol heç vaxt kodda və ya commit-də olmasın). `.env.example` faylında dəyər yerinə `DB_PASS=your_db_password_here` kimi placeholder saxla.
4. **Hər faza bitəndə**:
   - `CLAUDE.md` (lazım olsa) və `PROGRESS.md`-i yenilə.
   - Bütün yeni/dəyişən PHP fayllarını `php -l` ilə sintaksis yoxlamasından keçir.
   - Yalnız bunu de: **"Faza X bitdi, yoxlamalar uğurludur, növbəti fazaya hazıram."** Kodu bura yazma, kodu faylda saxla.
5. **Növbəti fazaya keçmə** — istifadəçi açıq şəkildə "Növbəti fazaya keç" (və ya bənzər) deyənə qədər gözlə.
6. **Final Hesabat** (bütün fazalar — 0-dan 7-yə qədər, sənədin bölmə 11-ə əsasən — bitdikdən sonra):
   - Layihənin final directory tree-sini göstər.
   - Bütün kodları birləşdirilmiş, strukturlu, əksiksiz formada (kod blokları daxilində) təqdim et.
   - Qeyd: istifadəçi orijinal tapşırıqda "fazalar 0-8" yazıb, amma mənbə sənəddə (bölmə 11) yalnız Faza 0-7 var — final hesabatda bunu bir cümləylə aydınlaşdır.

## Məcburi Təhlükəsizlik Standartları (bütün kodda)

- **PDO prepared statements** hər sorğuda — heç vaxt dəyişəni sorğuya birbaşa yapışdırma.
- **CSRF qoruması** bütün yazma (POST/PUT/DELETE) sorğularında — token yaradılır, `hash_equals` ilə yoxlanılır.
- **`declare(strict_types=1);`** hər PHP faylının başında.
- Parol: `password_hash`/`password_verify` (bcrypt/argon2) — heç vaxt açıq mətn.
- XSS: çıxış `htmlspecialchars` ilə escape.
- Sessiya cookie: httponly+secure+samesite; login-də `session_regenerate_id()`.
- Rol middleware hər qorunan sorğuda yoxlanır (IDOR qoruması).
- Rate-limit: giriş, SSE, ödəniş endpoint-lərində.

## Faza Planı (Bölmə 11)

| Faza | Mövzu | Status |
|---|---|---|
| 0 | Təməl (qovluq strukturu, Core, config/.env, error handler) | Gözləyir |
| 1 | Verilənlər Bazası (bütün cədvəllər, seed) | Gözləyir |
| 2 | Autentifikasiya (qeydiyyat/giriş, CSRF, rate-limit) — OTP YOX | Gözləyir |
| 3 | Sifariş və SSE (yaratma, lövhə, atomic götürmə, WhatsApp) | Gözləyir |
| 4 | Admin Paneli (ayrı subdomen, idarə) | Gözləyir |
| 5 | Abunə və Ödəniş (adapter, webhook) | Gözləyir |
| 6 | PWA və Cilalama (manifest, SW, çoxdilli, dizayn) | Gözləyir |
| 7 | Hüquqi (sözləşmə mətnləri — hüquqşünasdan sonra) | Gözləyir |

Cari status və detallı iş jurnalı: bax `PROGRESS.md`.

## Fayl Xəritəsi

- `docs/PROJECT_SPEC.md` — tam texniki spesifikasiya (bənd nömrələri ilə, referans üçün).
- `CLAUDE.md` — bu fayl (xülasə + qaydalar).
- `PROGRESS.md` — tamamlanan işlərin jurnalı, cari faza vəziyyəti.
- `.env.example` — mühit dəyişənləri şablonu (Faza 0-da yaradılacaq).
