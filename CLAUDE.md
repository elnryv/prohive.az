# Birlikdə — Layihə Yaddaşı

## Xülasə
«Birlikdə» (app.birlikde.biz) — kuryerlərlə çatdırılmaya ehtiyacı olan müştəriləri əlaqələndirən vasitəçilik platformasıdır. Platforma çatdırılmanı icra etmir, ödənişə/yükə qarışmır — sifariş götürüləndə tərəflərə bir-birinin WhatsApp linki (Click-to-Chat) açılır və razılaşma, ödəniş, çatdırılma birbaşa onların arasında gedir. Gəlir yalnız kuryerin aylıq abunə haqqından gəlir, müştəri üçün tamamilə pulsuzdur. Tam texniki mənbə sənəd: istifadəçi tərəfindən verilmiş "Birlikde_Layihe_v2.docx" (bölmə nömrələrinə görə istinad et, məs. "6.3 race qoruması").

## Texnologiya
- Backend: Native PHP 8.3 (framework YOX), `strict_types=1`
- DB: MySQL 8.0, InnoDB, utf8mb4_unicode_ci
- Server: Nginx + PHP-FPM, Hostinger VPS (Ubuntu), Let's Encrypt / wildcard TLS
- Real-time: SSE (Server-Sent Events) — server → kuryer bir-istiqamətli push
- Frontend: Vanilla JS + PWA (Service Worker, manifest, offline)
- Cron: sifaris_temizle (saatlıq), abunelik_yoxla (gündəlik)

## Əsas Qərarlar
- **Real-time**: SSE (WebSocket yox) — canlı lövhə üçün, `session_write_close()` mütləq.
- **Əlaqə**: WhatsApp Click-to-Chat (`wa.me` linkləri, ön-doldurulmuş mətnlə). Daxili çat/nömrə maskalama YOXDUR.
- **Ödəniş**: Yarım-avtomatik — recurring (avtomatik kart çəkmə) YOXDUR, kuryer hər ay 1 kliklə özü ödəyir.
- **SMS və Ödəniş adapter modeli**: `SmsProvider` və `PaymentProvider` interfeysləri; konkret provayder (sms.az, Birbank/Payriff) sonra qoşulur. Əsas kod toxunulmaz qalır.
- **Ərazi**: Bakı + Sumqayıt, admin panelindən idarə olunur (hardcode yox, DB-dən).
- **Abunə**: Yalnız aylıq. Fərdi + qlobal (bütün platforma) toggle rejimləri var.
- **Sessiya**: Müştəri/kuryer — uzunmüddətli (remember-me token, avtomatik atılmır, yalnız "Çıxış" bitirir). Admin — qısa idle-timeout (kalıcı DEYİL).
- **Bildiriş**: Web Push (SMS yox, pulsuz). iOS-da yalnız ana ekrana əlavə olunandan sonra işləyir.
- **PWA**: Güclü təklif (qeydiyyatdan sonra tam ekran, keçmək çətinləşdirilir).
- **Dizayn**: OLED qara (#000000) + glassmorphism + SF Pro.
- **Dillər**: AZ (default) / RU / EN.

## Subdomenlər
- `app.birlikde.biz` — müştəri + kuryer (eyni giriş, rola görə yönləndirmə)
- `appadmin.birlikde.biz` — admin (ayrı, izolyasiya olunmuş)

## Verilənlər Bazası Cədvəlləri
- `users` — müştəri/kuryer ümumi hesab
- `sessiyalar` — remember-me tokenlər
- `kuryeler` — kuryer əlavə məlumatı (FİN, nəqliyyat, onlayn status)
- `sehirler` — şəhərlər (Bakı, Sumqayıt)
- `rayonlar` — rayon/mikrorayon/qəsəbə (N—1 sehirler)
- `kurye_bolgeler` — kuryer ↔ rayon (N—N əlaqə)
- `sifarisler` — sifarişlər (nüvə cədvəl, `version` sahəsi ilə optimistic lock)
- `abunelikler` — kuryer abunə dövrləri
- `odenisler` — ödəniş əməliyyatları (idempotent, `order_id` unikal)
- `bannerler` — reklam bannerləri (rotation)
- `ayarlar` — global ayarlar (məs. `abune_rejimi`)
- `legal_logs` — audit jurnalı (append-only)
- `push_abuneler` — Web Push abunəlikləri (VAPID)
- `adminler` — admin hesabları (users-dən tam ayrı)

## Faza Planı
| Faza | Məzmun | Status |
|---|---|---|
| 0 | Təməl (VPS, Nginx, PHP-FPM, MySQL, Core/Router/DB/Session, config/.env) | ⬜ Başlanmayıb |
| 1 | Verilənlər bazası (bütün cədvəllər, seed, legal_logs, ayarlar) | ⬜ Başlanmayıb |
| 2 | Autentifikasiya (qeydiyyat/giriş, OTP+SMS adapter, sessiya, CSRF, rate-limit) | ⬜ Başlanmayıb |
| 3 | Sifariş və SSE (yaratma/ləğv/tarixçə, canlı lövhə, atomic götürmə, WhatsApp link) | ⬜ Başlanmayıb |
| 4 | Admin Paneli (auth, tablar, pop-up, sifariş idarəsi, abunə+banner) | ⬜ Başlanmayıb |
| 5 | Abunə və Ödəniş (payment adapter, webhook, cron) | ⬜ Başlanmayıb |
| 6 | PWA və Cilalama (manifest, SW, çoxdilli, dizayn, təhlükəsizlik testi) | ⬜ Başlanmayıb |
| 7 | Hüquqi (İstifadəçi Müqaviləsi/Məxfilik — hüquqşünasdan sonra) | ⬜ Başlanmayıb |

**Tamamlanan fazalar:** heç biri.

## İş Qaydaları
1. **Bir dəfəyə YALNIZ bir faza.** Növbəti fazaya istifadəçi "başla" (və ya aydın təsdiq) deməmiş keçmə.
2. **Hər fazadan sonra yoxlama**: `php -l` ilə syntax yoxla, DB dəyişikliyi varsa import test et, funksional işlədiyini yoxla, xətaları düzəlt. Xəta qalıbsa növbəti fazaya keçmə.
3. **Hər faza bitəndə** bu fayldakı "Faza Planı" cədvəlini və "Tamamlanan fazalar" sətrini yenilə, PROGRESS.md-də tarixli qeyd yaz.
4. **Sənəd bənd nömrələrinə istinad et** (məs. "6.3 race qoruması", "4.6 sifarisler DDL", "9.1 ödəniş adapteri").
5. **Real API açarları** (SMS, ödəniş provayderi) `.env`-ə yazılmır — yalnız placeholder qoyulur, istifadəçi özü əlavə edəcək.
6. **Təhlükəsizlik məcburidir**: PDO prepared statement (heç bir raw interpolation), `htmlspecialchars` ilə XSS qorunması, CSRF token (`hash_equals`), `password_hash` (bcrypt/argon2), rol-əsaslı middleware (IDOR qoruması), rate-limit (OTP/giriş/SSE/ödəniş).
