# BİRLİKDƏ — Tam Layihə Sənədi (Referans)

> Mənbə: `Birlikde_Layihe_v2.docx` (Versiya 2.0). Bu fayl layihənin tam texniki spesifikasiyasıdır.
> Kodlaşdırma zamanı bənd nömrələrinə istinad et (məs. "6.3 bəndinə uyğun kod yaz").
> Qısa xülasə üçün kök qovluqdakı `CLAUDE.md`-ə bax — bu fayl yalnız detallı istinad üçündür, hər sessiyada tam oxunmasın.

app.birlikde.biz — Kuryer & Müştəri Vasitəçilik Platforması
Native PHP 8.3 · MySQL 8.0 · SSE · PWA (iOS + Android)

## Sənəd Haqqında

Platforma yalnız **VASİTƏÇİDİR** — çatdırılmaya, yükə və ödənişə qarışmır, tərəfləri WhatsApp üzərindən əlaqələndirir. Gəlir yalnız kuryer abunəsindəndir.

Hüquqi bölmələr (İstifadəçi Müqaviləsi, Məxfilik Siyasəti mətnləri) HƏLƏ daxil edilməyib — hüquqşünasla dəqiqləşdirmə gözlənilir (Faza 7). Ödəniş və hüquqi funksiyalar kodda hazır olacaq, aktivləşdirmə sonraya saxlanılır.

**⚠️ Sənəd daxili ziddiyyət:** Bölmə 2.2/2.3/3.1 açıq şəkildə "OTP/SMS YOX, yalnız nömrə+parol" deyir, lakin Bölmə 11 (Faza 2) və Əlavə A hələ köhnə OTP axınına istinad edir (`POST /otp/dogrula` və s.). **Qərar: OTP/SMS istifadə OLUNMUR** (2.3-ə əsasən, bu daha yeni və dəfələrlə vurğulanan qayda). Faza 2-də OTP xidməti qurulmayacaq, `POST /otp/dogrula` endpoint-i tətbiq edilməyəcək.

---

## 1. Layihənin Ümumi Baxışı

### 1.1 Platforma nədir
«Birlikdə» — kuryerlərlə çatdırılmaya ehtiyacı olan müştəriləri bir araya gətirən rəqəmsal vasitəçilik platformasıdır. Çatdırılma xidmətini icra etmir, ödənişə qarışmır, kuryerlərin hərəkətlərinə görə məsuliyyət daşımır.

Əlaqə WhatsApp üzərindən qurulur: sifariş götürüləndə hər iki tərəfə bir-birinin WhatsApp linki (Click-to-Chat) açılır; razılaşma/ödəniş/çatdırılma birbaşa tərəflər arasında.

### 1.2 Gəlir Modeli
- Yalnız kuryerdən aylıq abunə haqqı.
- Müştəri üçün tamamilə pulsuz.
- Çatdırılma pulundan komissiya YOX.
- Əlavə gəlir: ana səhifə reklam bannerləri (8.4).

### 1.3 Subdomenlər
| Ünvan | Kim üçün | Təyinat |
|---|---|---|
| app.birlikde.biz | Müştəri + Kuryer | Əsas PWA; girişdən sonra rola görə yönləndirmə |
| appadmin.birlikde.biz | Admin | Ayrı, izolyasiya olunmuş idarə paneli |

### 1.4 Əsas Prinsiplər
- PWA (iOS + Android), ana ekrana əlavə olunur.
- Real-time bildiriş SSE ilə.
- Native PHP 8.3 (framework yox), MySQL 8.0, Hostinger VPS.
- Çoxdilli: AZ, RU, EN.
- Dizayn: OLED qara (#000000), glassmorphism, SF Pro.
- Bütün əlaqə/dəstək WhatsApp Click-to-Chat.

---

## 2. Aktyorlar və Qeydiyyat

### 2.1 Aktyorlar
| Aktyor | Təsvir | Əsas səlahiyyətlər |
|---|---|---|
| Müştəri | Çatdırılmaya ehtiyacı olan şəxs | Sifariş yaratmaq, ləğv etmək, tarixçəyə baxmaq |
| Kuryer | Abunəli çatdırılma icraçısı | Lövhəni görmək, sifariş götürmək, onlayn/offline |
| Admin | Platforma operatoru | İstifadəçi/sifariş idarəsi, abunə, banner, bloklama |
| Sistem (Cron) | Avtomatik proseslər | Sifariş təmizləmə, abunə yoxlaması, xəbərdarlıq |

### 2.2 Qeydiyyat Sistemi
Bütün rollar telefon nömrəsi + parol ilə qeydiyyatdan keçir. **OTP/SMS istifadə olunmur.** Qeydiyyat ekranı rola görə fərqlidir: müştəri, kuryer (adi), yükdaşıma (ağır yük).

**Sözləşmə (REG-2.2):** Hər üç qeydiyyatda İstifadəçi Sözləşməsi razılıq checkbox-u MƏCBURİDİR. Qəbul faktı/tarixi `users.sozlesme_qebul`, `users.sozlesme_tarix`-də saxlanır. Sözləşmə mətni asan dəyişilən yerdə (`config/sozlesme.php` və ya dil faylı).

#### 2.2.1 Müştəri Qeydiyyatı
Telefon+parol, ad/soyad, WhatsApp nömrəsi, sözləşmə qəbulu.

#### 2.2.2 Kuryer Qeydiyyatı (adi çatdırılma)
Telefon+parol, ad/soyad, WhatsApp nömrəsi, nəqliyyat növü (avtomobil/moto/skuter/velosiped/piyada), sözləşmə. Ərazi seçimi qeydiyyatdan SONRA profildən (7.2.2).

#### 2.2.3 Yükdaşıma Qeydiyyatı (ağır yük)
Telefon+parol, ad/soyad, WhatsApp, yükdaşıma ölçüləri (çoxlu seçim, məs. S+M+L, bax 4.3.2), sözləşmə. Ərazi seçimi sonra profildən.

**Kompakt göstərmə (REG-2.2.3):** Hər ölçü "XS · 1.5–2.0m · 500kq" formatında bir sətir, checkbox ilə çoxlu seçim.

#### 2.2.4 WhatsApp Nömrəsi Sahəsi
Link formatı: `https://wa.me/994XXXXXXXXX`. Nömrə beynəlxalq formatda saxlanılır (994501234567).

### 2.3 Giriş (Login)
- Nömrə + parol; `password_hash` (bcrypt/argon2), `password_verify`.
- Uğurlu girişdən sonra uzunmüddətli sessiya (7.3.1) — özü çıxmasa atılmır.
- Giriş cəhdlərinə rate-limit.
- **(LOGIN-2.3):** OTP/SMS çıxarıldı — SMS provayderi lazım deyil, sadə nömrə+parol.

---

## 3. Texniki Arxitektura

### 3.1 Texnologiya Yığını
| Qat | Texnologiya | Qeyd |
|---|---|---|
| Server/OS | Ubuntu (Hostinger VPS) | Nginx + PHP-FPM 8.3 |
| Backend | Native PHP 8.3 | Framework yox, `strict_types=1` |
| DB | MySQL 8.0 | InnoDB, utf8mb4, tranzaksiya |
| Real-time | SSE | Server → kuryer bir-istiqamətli push |
| Frontend | Vanilla JS + PWA | Service Worker, manifest, offline |
| Ödəniş | Adapter (Birbank/Payriff) | Provayder-neytral |
| Giriş | Nömrə+parol (password_hash) | OTP/SMS YOX |
| Əlaqə | WhatsApp Click-to-Chat | wa.me linkləri |

### 3.2 Qovluq Strukturu
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
│   ├── Providers/         # PaymentProvider (adapterlər)
│   └── Views/             # Şablonlar + dil (az/ru/en)
├── config/               # .env oxuyan konfiqurasiya
├── storage/              # loglar, cache, sessiya, banner şəkilləri
├── database/             # migrations, seed (şəhər/rayon)
└── cron/                 # sifaris_temizle.php, abunelik_yoxla.php
```
(Qeyd: sənəddə `Providers/SmsProvider` və `Services/OtpService` var idi, amma OTP/SMS çıxarıldığı üçün bunlar tətbiq edilməyəcək.)

### 3.3 Subdomen və Yönləndirmə
- `app.birlikde.biz` — müştəri+kuryer eyni giriş; `users.rol`-a görə panelə yönləndirmə.
- `appadmin.birlikde.biz` — ayrı Nginx server bloku, izolyasiya olunmuş admin panel.
- Hər subdomen üçün ayrı SSL (Let's Encrypt) və ya wildcard `*.birlikde.biz`.

### 3.4 Sorğu Axını
Nginx → index.php (try_files) → .env/error handler/sessiya → Router (Controller@method) → Middleware zənciri (Auth → RoleGuard → CsrfGuard → RateLimit) → Controller → Service → Model (PDO) → Cavab (HTML/JSON).

### 3.5 Çoxdilli Struktur
- Dil faylları: `assets/lang/az.json`, `ru.json`, `en.json`.
- Dil seçimi profil/sessiyada. Default: AZ.
- Şəhər/rayon adları DB-də `ad_az`, `ad_ru`, `ad_en` sütunları ilə.

---

## 4. Verilənlər Bazası Strukturu

Bütün cədvəllər InnoDB, `utf8mb4_unicode_ci`.

### 4.1 Cədvəllər İcmalı
| Cədvəl | Təyinat | Əsas əlaqələr |
|---|---|---|
| users | Müştəri/kuryer/yükdaşıma (ümumi hesab) | 1—N sifarisler; 1—1 kuryeler |
| kuryeler | Kuryer/yükdaşıma əlavə məlumatı | 1—1 users; 1—N abunelikler |
| yukdasima_olculeri | Yük ölçü kateqoriyaları (XS–Mega) | 1—N dasiyici_olculeri |
| dasiyici_olculeri | Daşıyıcının daşıdığı ölçülər | N—1 kuryeler; N—1 ölçü |
| kurye_bolgeler | Kuryerin xidmət etdiyi rayonlar | N—1 kuryeler; N—1 rayonlar |
| sehirler | Şəhərlər | 1—N rayonlar |
| rayonlar | Rayon/qəsəbə/mikrorayon | N—1 sehirler |
| sifarisler | Sifarişlər | N—1 users (müştəri); N—1 kuryeler |
| abunelikler | Kuryer abunə dövrləri | N—1 kuryeler |
| odenisler | Ödəniş əməliyyatları | N—1 abunelikler |
| bannerler | Reklam bannerləri | — |
| ayarlar | Sistem ayarları (global toggle) | — |
| legal_logs | Audit jurnalı (append-only) | N—1 users/sifarisler (nullable) |
| push_abuneler | Web Push abunəlikləri | N—1 users |
| sessiyalar | Remember-me tokenlər | N—1 users |

### 4.2 `users`
```sql
CREATE TABLE users (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad           VARCHAR(80)  NOT NULL,
  soyad        VARCHAR(80)  NOT NULL,
  telefon      VARCHAR(20)  NOT NULL UNIQUE,   -- giriş nömrəsi
  parol_hash   VARCHAR(255) NOT NULL,          -- password_hash
  whatsapp     VARCHAR(20)  NOT NULL,          -- wa.me üçün (994...)
  whatsapp_tip ENUM('sexsi','business') DEFAULT 'sexsi',
  rol          ENUM('musteri','kurye','yukdasima') NOT NULL DEFAULT 'musteri',
  dil          ENUM('az','ru','en') NOT NULL DEFAULT 'az',
  status       ENUM('aktiv','bloklu') NOT NULL DEFAULT 'aktiv',
  sozlesme_qebul TINYINT(1) NOT NULL DEFAULT 0,
  sozlesme_tarix TIMESTAMP NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_rol (rol), INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
Admin ayrıca cədvəldə (`adminler`) saxlanılır. Rol üç növdür.

### 4.2.1 `sessiyalar` (Remember-me)
```sql
CREATE TABLE sessiyalar (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     BIGINT UNSIGNED NOT NULL,
  token_hash  VARCHAR(255) NOT NULL,
  cihaz       VARCHAR(160),
  son_giris   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Çıxış: sətir silinir. Avtomatik logout YOXDUR (müştəri/kuryer).
```

### 4.3 `kuryeler` və Yükdaşıma
"kuryer" və "yukdasima" hər ikisi `users.rol` ilə fərqlənir; `kuryeler` cədvəli ortaqdır.
```sql
CREATE TABLE kuryeler (
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       BIGINT UNSIGNED NOT NULL UNIQUE,
  neqliyyat     ENUM('avtomobil','moto','skuter','velosiped','piyada') NULL,
  onlayn        TINYINT(1) NOT NULL DEFAULT 0,
  tamamlanan    INT UNSIGNED NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_kurye_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- FİN kod ARTIQ TOPLANMIR
```

### 4.3.1 `yukdasima_olculeri` / `dasiyici_olculeri`
```sql
CREATE TABLE yukdasima_olculeri (
  id        TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kod       VARCHAR(6) NOT NULL UNIQUE,   -- 'XS','S','M','L','XL','XXL','Mega'
  uzunluq   VARCHAR(40),
  tutum     VARCHAR(40),
  tesvir    VARCHAR(80),
  sira      TINYINT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dasiyici_olculeri (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurye_id     BIGINT UNSIGNED NOT NULL,
  olcu_id      TINYINT UNSIGNED NOT NULL,
  CONSTRAINT fk_do_kurye FOREIGN KEY (kurye_id) REFERENCES kuryeler(id),
  CONSTRAINT fk_do_olcu  FOREIGN KEY (olcu_id)  REFERENCES yukdasima_olculeri(id),
  UNIQUE KEY uq_kurye_olcu (kurye_id, olcu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.3.2 Yükdaşıma Kateqoriyaları (seed)
| Kod | Uzunluq | Yük tutumu |
|---|---|---|
| XS (Mikro) | 1.5–2.0 m | 500 kq-a qədər |
| S (Kiçik) | 2.5–2.8 m | 1.0–1.2 ton |
| M (Orta) | 3.0–3.4 m | 1.3–1.5 ton |
| L (Böyük) | 3.5–4.2 m | 1.5–2.0 ton |
| XL (Ekstra) | 4.2 m-dən çox | 2.0–3.5 ton |
| XXL (Ağır) | Platforma/tentli | 5.0–10.0 ton |
| Mega (Tır) | Yarımqoşqu/konteyner | 20+ ton |

### 4.4 `sehirler` / `rayonlar`
```sql
CREATE TABLE sehirler (
  id     SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ad_az  VARCHAR(60) NOT NULL,
  ad_ru  VARCHAR(60), ad_en VARCHAR(60),
  aktiv  TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- seed: Bakı, Sumqayıt

CREATE TABLE rayonlar (
  id        SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sehir_id  SMALLINT UNSIGNED NOT NULL,
  ad_az     VARCHAR(80) NOT NULL,
  ad_ru     VARCHAR(80), ad_en VARCHAR(80),
  aktiv     TINYINT(1) DEFAULT 1,
  CONSTRAINT fk_rayon_sehir FOREIGN KEY (sehir_id) REFERENCES sehirler(id),
  INDEX idx_sehir (sehir_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.5 `kurye_bolgeler`
```sql
CREATE TABLE kurye_bolgeler (
  id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurye_id  BIGINT UNSIGNED NOT NULL,
  rayon_id  SMALLINT UNSIGNED NOT NULL,
  CONSTRAINT fk_kb_kurye FOREIGN KEY (kurye_id) REFERENCES kuryeler(id),
  CONSTRAINT fk_kb_rayon FOREIGN KEY (rayon_id) REFERENCES rayonlar(id),
  UNIQUE KEY uq_kurye_rayon (kurye_id, rayon_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
N—N əlaqə: bir kuryer bir neçə rayon seçə bilər.

### 4.6 `sifarisler` (nüvə)
```sql
CREATE TABLE sifarisler (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  musteri_id     BIGINT UNSIGNED NOT NULL,
  kurye_id       BIGINT UNSIGNED NULL,
  goturulme_sehir_id  SMALLINT UNSIGNED NOT NULL,
  goturulme_rayon_id  SMALLINT UNSIGNED NOT NULL,
  goturulme_unvan     VARCHAR(255) NOT NULL,
  catdirilma_sehir_id SMALLINT UNSIGNED NOT NULL,
  catdirilma_rayon_id SMALLINT UNSIGNED NOT NULL,
  catdirilma_unvan    VARCHAR(255) NOT NULL,
  yuk_tesviri    VARCHAR(500),
  teklif_qiymet  DECIMAL(10,2),
  tecili         TINYINT(1) NOT NULL DEFAULT 0,
  tip            ENUM('kurye','yukdasima') NOT NULL DEFAULT 'kurye',
  yukdasima_olcu_id TINYINT UNSIGNED NULL,
  status         ENUM('axtarisda','goturulub','tamamlandi','legv','passiv')
                   NOT NULL DEFAULT 'axtarisda',
  version        INT UNSIGNED NOT NULL DEFAULT 0, -- optimistic lock
  goturulme_vaxti TIMESTAMP NULL,
  created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sif_musteri FOREIGN KEY (musteri_id) REFERENCES users(id),
  CONSTRAINT fk_sif_kurye   FOREIGN KEY (kurye_id)   REFERENCES kuryeler(id),
  INDEX idx_status_rayon (status, goturulme_rayon_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```
`version` — race-condition qorumasının təməli (6.3). `idx_status_rayon` lövhə sorğusunu sürətləndirir. Status axını: `axtarisda → goturulub → tamamlandi` (və ya `legv`/`passiv`).

### 4.7 `abunelikler` / `odenisler`
```sql
CREATE TABLE abunelikler (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kurye_id    BIGINT UNSIGNED NOT NULL,
  tip         ENUM('pulsuz','pullu') NOT NULL DEFAULT 'pullu',
  baslama     DATE NOT NULL,
  bitme       DATE NOT NULL,
  aktiv       TINYINT(1) NOT NULL DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ab_kurye FOREIGN KEY (kurye_id) REFERENCES kuryeler(id),
  INDEX idx_kurye_bitme (kurye_id, bitme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE odenisler (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  abunelik_id BIGINT UNSIGNED NULL,
  kurye_id    BIGINT UNSIGNED NOT NULL,
  order_id    VARCHAR(64) NOT NULL UNIQUE,     -- idempotency
  mebleg      DECIMAL(10,2) NOT NULL,
  status      ENUM('gozlemede','ugurlu','ugursuz') DEFAULT 'gozlemede',
  provayder   VARCHAR(30),                     -- 'birbank' | 'payriff'
  callback_json JSON NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.8 `bannerler` / `ayarlar`
```sql
CREATE TABLE bannerler (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  baslik      VARCHAR(120),
  sekil_yol   VARCHAR(255) NOT NULL,
  link        VARCHAR(500),
  hedef       ENUM('hamisi','musteri','kurye') DEFAULT 'hamisi',
  baslama     DATE NOT NULL,
  bitme       DATE NOT NULL,
  sira        SMALLINT DEFAULT 0,
  aktiv       TINYINT(1) DEFAULT 1,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_aktiv_tarix (aktiv, baslama, bitme)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ayarlar (
  ad     VARCHAR(60) PRIMARY KEY,   -- 'abune_rejimi'
  deyer  VARCHAR(255) NOT NULL      -- 'aktiv' | 'dayandirilib'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4.9 `legal_logs` (Audit)
```sql
CREATE TABLE legal_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id    BIGINT UNSIGNED NULL,
  sifaris_id  BIGINT UNSIGNED NULL,
  hadise      VARCHAR(60) NOT NULL,   -- 'sifaris_goturuldu','blok', ...
  detal_json  JSON NULL,
  ip_adres    VARBINARY(16),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  -- append-only: UPDATE/DELETE tətbiq səviyyəsində qadağandır
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### `push_abuneler` (bax 9.3.3)
```sql
CREATE TABLE push_abuneler (
  id       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id  BIGINT UNSIGNED NOT NULL,
  endpoint TEXT NOT NULL,
  p256dh   VARCHAR(255) NOT NULL,
  auth     VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_endpoint (endpoint(191)),
  CONSTRAINT fk_push_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5. Ərazi və Filtr Sistemi

### 5.1 Şəhərlər
Başlanğıcda: Bakı və Sumqayıt. Yeni şəhər yalnız DB-yə sətir əlavə etməklə qoşulur (hardcode yox).

**Seed siyahısı:**
- Bakı: Binəqədi, Xətai, Xəzər, Nərimanov, Nəsimi, Nizami, Pirallahı, Sabunçu, Səbail, Suraxanı, Yasamal, Qaradağ (12 rayon).
- Sumqayıt: 1-ci–18-ci mikrorayonlar; qəsəbələr: Corat, H.Z.Tağıyev, Kotec, İnşaatçılar; massivlər: Yaşıldərə, Qurd dərəsi.
- **(SEED-5.1.1):** Sumqayıtın rəsmi bölgüsü canlıya keçməzdən əvvəl İcra Hakimiyyəti siyahısı ilə yoxlanmalıdır (admin idarəsi 8.7 ilə asanlıqla dəyişdirilə bilər).

### 5.2 Ünvan Daxiletmə
Şəhər dropdown → rayon dropdown (seçilən şəhərə uyğun) → küçə/ünvan əl ilə mətn. Götürülmə və çatdırılma üçün ayrı-ayrı.

### 5.3 Kuryerin Rayon Filtri
Kuryer profilində fəaliyyət rayonlarını seçir (`kurye_bolgeler`). Yalnız seçdiyi rayonlardan gələn sifarişləri görür. SSE sorğusu `WHERE goturulme_rayon_id IN (...)` tətbiq edir.
**(TERR-5.3):** "hamısını göstər" toggle YOXDUR.

---

## 6. Sifariş Axını və Real-Time Məntiq

### 6.1 Tam Axın
1. Müştəri sifariş yaradır (ünvanlar, yük təsviri, qiymət, təcili/adi).
2. `status='axtarisda'` ilə yaradılır, SSE ilə uyğun rayon kuryerlərinin lövhəsinə düşür.
3. Kuryerlər səhifə yenilənmədən görür (SSE push).
4. İlk "Götür" basan qazanır (race qoruması, 6.3).
5. Digərləri "artıq götürülüb" görür, elan yox olur.
6. Hər iki tərəfə WhatsApp linki açılır (ön-doldurulmuş mətnlə, 6.4).
7. Razılaşma/ödəniş/çatdırılma WhatsApp-da — platforma qarışmır.
8. Kuryer "tamamlandı" işarələyir; sayı artır.
9. Götürülməyən sifariş 1 saat sonra avtomatik `passiv` olur (cron).

### 6.2 SSE Canlı Lövhə

**6.2.1 Server tərəfi:**
```php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');   // Nginx buferini söndür
session_write_close();             // sessiya kilidini burax (VACİB)

$rayonlar = $kurye->getRayonIds();
$lastId   = (int)($_SERVER['HTTP_LAST_EVENT_ID'] ?? 0);

while (!connection_aborted()) {
  $yeni = $repo->axtarisdaSifarisler($rayonlar, $lastId);
  foreach ($yeni as $s) {
    echo "id: {$s['id']}\n";
    echo "event: yeni_sifaris\n";
    echo 'data: ' . json_encode($s) . "\n\n";
    $lastId = $s['id'];
  }
  @ob_flush(); @flush();
  sleep(2);
}
```
**Kritik (SSE-6.2):** `session_write_close()` mütləqdir. Nginx-də `proxy_buffering off` + `fastcgi_read_timeout` artırılmalı.

**6.2.2 Client tərəfi:**
```js
const es = new EventSource('/sse/lovhe');
es.addEventListener('yeni_sifaris', (ev) => {
  const s = JSON.parse(ev.data);
  lovheyeKartElaveEt(s);
});
es.onerror = () => offlineGuard.markDisconnected();
```

### 6.3 Race Condition Qoruması (Atomic Götürmə)
```sql
UPDATE sifarisler
   SET kurye_id = :kurye, status = 'goturulub',
       goturulme_vaxti = NOW(), version = version + 1
 WHERE id = :id
   AND status = 'axtarisda'
   AND kurye_id IS NULL;
-- affected_rows === 1 → bu kuryer qazandı
-- affected_rows === 0 → artıq başqası götürüb
```
PHP: `$stmt->rowCount()` — 1 → uğur (WhatsApp linkləri açılır), 0 → "artıq götürülüb". Güclü zəmanət üçün tranzaksiya + `SELECT ... FOR UPDATE` əlavə oluna bilər.
**(RACE-6.3):** Uğurlu götürmə `legal_logs`-a `'sifaris_goturuldu'` kimi yazılır (kurye_id, sifaris_id, IP).

### 6.4 WhatsApp Əlaqə (Click-to-Chat)
```
// Kuryerə (müştəri ilə əlaqə):
https://wa.me/994XXXXXXXXX?text=Birlikdə%20sifarişi%20%23123%20barədə...
// Müştəriyə (kuryer ilə əlaqə):
https://wa.me/994YYYYYYYYY?text=Birlikdə%20sifarişi%20%23123%20barədə...
```
Mətn URL-encoded, sifariş nömrəsi avtomatik daxil. Simmetrik əlaqə. Platforma söhbətə qarışmır/saxlamır.
**(WA-6.4):** Nömrə maskalama və ya daxili çat qurulmur.

### 6.5 Sifariş Statusları (State Machine)
| Status | Məna | Keçid |
|---|---|---|
| axtarisda | Yaradılıb, lövhədə | → goturulub / legv / passiv |
| goturulub | Kuryer götürüb, WA açıq | → tamamlandi / legv |
| tamamlandi | İş bitib | son |
| legv | Müştəri ləğv edib | son |
| passiv | 1 saat götürülməyib (cron) | son |

---

## 7. İstifadəçi Panelləri

### 7.1 Müştəri Paneli

**7.1.1 Sifariş Yaratma**
- Addım 1: "Kuryer" (adi) yoxsa "Yükdaşıma" (ağır yük) seçimi. Yükdaşımada ölçü kateqoriyası da seçilir (4.3.2).
- Forma: götürülmə (şəhər/rayon+küçə), çatdırılma (şəhər/rayon+küçə), yük təsviri, təklif qiymət, təcili/adi.
- Filtr: sifariş yalnız uyğun daşıyıcıların lövhəsinə düşür (kuryer→kuryerlərə, yükdaşıma→uyğun ölçü daşıyan yükdaşımaçılara).
- Client+server validasiya; CSRF token ilə POST.
- SSE ilə kuryer lövhəsinə düşür; müştəriyə canlı status kartı.

**7.1.2 Ləğv və İzləmə:** "Ləğv et" yalnız `axtarisda`/`goturulub`-da. Götürüləndə kuryer adı, nəqliyyat növü, WhatsApp linki görünür.

**7.1.3 Sifariş Tarixçəsi:** ayrı tabda (status, tarix, kuryer).

**7.1.4 Şikayət:** "Şikayət et" → dəstək WhatsApp Business (ön-doldurulmuş, məs. "Şikayət — sifariş #123").

### 7.2 Kuryer Paneli

**7.2.1 Giriş Şərtləri:** aktiv abunə olmalı (bitibsə lövhə bağlı); status bloklu olmamalı; onlayn toggle açıq; ən azı bir ərazi seçilmiş (yoxdursa lövhə boş).

**7.2.2 Ərazilərim:** profildə "Ərazilərim" bölməsindən seçim (N—N, `kurye_bolgeler`). Qeydiyyatda deyil, sonra profildən. Heç ərazi seçməyibsə lövhə boş.
Xülasə: "3 ərazi: Nəsimi, Yasamal, Nizami +2".
**(KURYE-7.2.2):** Seçim modal-da: şəhər seçimi (Bakı/Sumqayıt) + axtarış qutusu (yazdıqca filtrlənir), 36+ ərazinin ekranı doldurmasının qarşısını almaq üçün.

**7.2.3 Canlı Lövhə:** SSE ilə öz rayonlarındakı sifarişlər. Kart: ünvanlar, yük, qiymət, təcili nişanı, "Götür" düyməsi.

**7.2.4 "Götür" Düyməsi:** offline guard yoxlanır; düymə dərhal deaktiv (double-click qorunması); atomic götürmə (6.3); uğurlu→WhatsApp linki+"Mənim işim"ə keçid; uğursuz→"artıq götürülüb".

**7.2.5 Onlayn/Offline və Tamamlama:** offline-da yeni sifariş/bildiriş yox. "Tamamlandı"→`status='tamamlandi'`, sayı artır. Tarixçə+Şikayət mövcud.

**7.2.6 Abunə Xəbərdarlığı:** bitməyə az qalanda (məs. 3 gün) profildə banner + SSE bildiriş.

### 7.3 Ümumi Funksiyalar (hər iki tərəf)
Profil: ad/soyad, WhatsApp, dil seçimi. Nömrə dəyişmə OTP təsdiqi ilə *(qeyd: sənəddə qalıb, amma OTP ümumilikdə çıxarılıb — bax yuxarı ziddiyyət qeydi; tətbiqdə admin/dəstək vasitəsilə və ya sadə təsdiq axını ilə əvəz edilə bilər, qərar Faza 2-də verilir)*. PWA ana ekrana əlavə (bölmə 9). Çıxış düyməsi — yalnız bu düymə ilə sessiya bitir.

**7.3.1 Uzunmüddətli Sessiya (Kalıcı Giriş):**
- Müştəri/kuryer bir dəfə girəndən sonra avtomatik atılmır.
- "Məni xatırla" kalıcı cookie + remember-me token.
- Sessiya YALNIZ "Çıxış" basanda bitir (və ya parol/nömrə dəyişəndə).
- Cookie: httponly+secure+samesite; token DB-də, çıxışda ləğv olunur.
- **(SESSION-7.3.1):** Yalnız müştəri/kuryerə aiddir. ADMIN qısa idle-timeout ilə (8.8) — kalıcı DEYİL. Token cihaza bağlıdır.

---

## 8. Admin Paneli (appadmin.birlikde.biz)

### 8.1 Naviqasiya (Sol Menyu)
| Bölmə | Məzmun | Filtr/Axtarış |
|---|---|---|
| Dashboard | Canlı sayğaclar, son hadisələr | — |
| Müştərilər | Müştəri siyahısı (tab) | Ad/telefon, tarix |
| Kuryerlər | Kuryer siyahısı (tab) | Status, ərazi, ad/telefon |
| Sifarişlər | Bütün sifarişlər | Status, rayon, tarix |
| Abunəliklər | Abunə/ödəniş idarəsi | Status, tip |
| Bannerlər | Reklam banner idarəsi | Aktiv/passiv, hədəf |
| Ərazilər | Şəhər/rayon əlavə-sil (8.7) | Şəhər üzrə |
| Ayarlar | Global abunə toggle, şifrə | — |

### 8.2 İstifadəçi Siyahıları və Pop-up
Müştəri/kuryer ayrı tablarda. Klik→pop-up: ad/soyad, telefon, rol, nəqliyyat növü/yükdaşıma ölçüləri, tamamlanan sifariş sayı, qeydiyyat tarixi, sözləşmə qəbul tarixi. Düymələr: Blokla/Blokdan çıxar, WhatsApp yaz.
Bloklama səbəb tələb edir, `legal_logs`-a yazılır. Bloklanan giriş edə bilir, lövhə/sifariş bağlıdır.

### 8.3 Sifariş İdarəsi
Cədvəl: kim yaratdı, kim götürdü, status, ünvanlar, tarix. Klik→tam detal+tarixçə. Filtr: status/rayon/tarix aralığı, pagination.

### 8.4 Reklam Banner Sistemi

**8.4.1 Yaratma:** şəkil yükləmə (tövsiyə 1080×300), başlıq, keçid linki (opsional), hədəf (hamısı/müştəri/kuryer), aktiv günlər (başlama/bitmə), sıra (rotation).

**8.4.2 Göstərilmə Məntiqi:** logodan aşağıda, sifarişlərdən yuxarıda. Bir neçə aktiv banner rotation (sıraya görə). Yalnız tarixi aktiv olanlar (`baslama ≤ bugün ≤ bitme`). Responsiv.
**(BANNER-8.4):** Şəkillər storage-da, DB-də yalnız yol. Yükləmədə ölçü/format yoxlanır.

### 8.5 Abunə İdarəsi
Kuryerə klik→pop-up-da abunə bölməsi:
| Element | Təsvir |
|---|---|
| Vəziyyət | Aktiv/Pulsuz/Bitib/Bloklu/Pulsuz (global) — rəngli etiket |
| Tip | Aylıq (pullu) və ya Pulsuz |
| Qalan gün | 3-dən az→qırmızı, 7-dən az→sarı |
| +30 gün | Abunəni bir ay uzadır |
| Tip dəyiş | Pulsuz↔pullu (fərdi) |
| Dayandır/Aktiv et | Fərdi kuryer abunəsi |
| Səbəb | Hər dəyişiklik üçün (audit) |

**8.5.1 Global Abunə Rejimi:** "Bütün platformada abunəni dayandır/aktiv et" — `ayarlar` cədvəlində. Dayandırılıbsa hamı pulsuz işləyir. Təsdiq dialoqu ilə. Hər dəyişiklik (fərdi+qlobal) səbəblə `legal_logs`-a yazılır.
**(ABUNE-8.5):** Fərdi və qlobal fərqli səviyyələr. Qlobal dayandırma aktivkən fərdi vəziyyətdən asılı olmadan heç kim ödəmir.

### 8.7 Ərazi İdarəsi
Admin şəhər seçir, əraziləri idarə edir: Əlavə et (yeni ad, şəhərə bağlı), Deaktiv/Aktiv et (silmədən gizlət, `aktiv=0`), Sil (yalnız heç bir sifarişdə istifadə olunmayan, referential integrity). Yeni şəhər əlavə oluna bilər (hardcode yox).
**(TERR-8.7):** Deaktiv ediləndə yeni sifarişlərdə görünmür, köhnə tarixçə qorunur.

### 8.8 Admin Təhlükəsizliyi
Ayrı subdomen, ayrı `adminler` cədvəli. Şifrə dəyişmə: cari şifrə təsdiqi+yeni (password_hash). Qısa idle-timeout, rate-limit, brute-force qoruması. Çıxış düyməsi + avtomatik idle-timeout (sessiya kalıcı DEYİL). Bütün admin əməliyyatları `legal_logs`-a yazılır.

---

## 9. Ödəniş, PWA və Yerləşdirmə

### 9.1 Ödəniş Sistemi (Adapter)
Kuryer aylıq abunə ödəyir. Yarım-avtomatik: sistem bitməzdən əvvəl bildiriş göndərir, kuryer bir kliklə ödəyir, abunə uzanır. **Avtomatik kart çəkmə (recurring) YOXDUR.**
Provayder-neytral: `PaymentProvider` interfeysi, konkret provayder (Birbank/Payriff) tətbiq edir.
```php
interface PaymentProvider {
  public function baslat(int $kuryeId, float $mebleg): PaymentSession;
  public function callbackDogrula(array $data): PaymentResult;
}
class BirbankProvider implements PaymentProvider { /* ... */ }
class PayriffProvider implements PaymentProvider { /* ... */ }
```
**(PAY-9.1):** Yarım-avtomatik seçildi: bankda "avtomatik pul çəkmə razılığı" mürəkkəbliyi yox; kuryer şüurlu ödəyir; sadə inteqrasiya kifayətdir. `recurringCek` metodu YOXDUR.

**9.1.1 Ödəniş Axını:**
1. Bitməyə az qalanda (məs. 3 gün) bildiriş (Web Push+profil banner).
2. Kuryer "Ödə" basır.
3. Server `odenisler`-də `status='gozlemede'`, unikal `order_id`.
4. Provayder ödəniş səhifəsinə yönləndirmə.
5. Provayder callback/webhook; server imza/hash doğrulayır.
6. Uğurlu: `status='ugurlu'`, abunə +1 ay, `legal_logs`. Kart məlumatı SAXLANMIR.
**(PAY-9.1.1):** Status yalnız təsdiqlənmiş webhook ilə qapanır (idempotent, eyni `order_id` iki dəfə emal olunmaz). Kart emalı provayderdə (tokenization), PCI-DSS provayderdə.

**9.1.2 Abunə Qaydaları:** yalnız aylıq. Vaxtında ödənməsə→profil bağlanır, lövhə işləmir, bildiriş. Ödəniş edilən kimi dərhal bərpa. Cron (`abunelik_yoxla.php`) gündəlik: bitənləri bağlayır, yaxınlaşanlara xəbərdarlıq.

**9.1.3 Admin Əl ilə Müdaxilə:** normalda lazım deyil. "+30 gün"/"tip dəyiş"/"dayandır" (8.5) yalnız istisna hallar üçün (güzəşt, promo, problem həlli, kompensasiya). Hər müdaxilə səbəblə `legal_logs`-a.

### 9.2 PWA və Ana Ekrana Əlavə
`manifest.json`: `display=standalone`, `theme_color=#000000`, ikonlar (192,512). Service Worker: app shell cache-first, SSE network-only, offline guard.

**9.2.1 Güclü Təklif Ekranı:** Qeydiyyatdan sonra tam ekran təklif ("bildiriş almaq üçün vacibdir"). Keçmək mümkün amma çətinləşdirilib (2-ci addımda təsdiq). Quraşdırılmayıbsa periodik xatırlatma.

**9.2.2 Android (Chrome):** `beforeinstallprompt` tutulur/saxlanır; "Əlavə et" basılanda işə düşür.

**9.2.3 iOS (Safari):**
**Vacib (PWA-9.2.3):** iOS Safari `beforeinstallprompt`-u DƏSTƏKLƏMİR. Vizual təlimat: "Paylaş → Ana ekrana əlavə et". Cihaz aşkarlanır (iOS+Safari), yalnız onlara göstərilir. iOS-da Web Push YALNIZ ana ekrana əlavədən sonra işləyir.

### 9.3 Web Push Bildiriş Sistemi
Service Worker vasitəsilə (app bağlı olsa belə). SMS istifadə olunmur.

**9.3.1 İcazə Axını:** qeydiyyatdan sonra icazə pəncərəsi. Verilirsə→`PushManager.subscribe`, saxlanır. Verilməzsə→lövhənin başında davamlı qırmızı xəbərdarlıq (icazə verilənə qədər itmir).
**Texniki reallıq (PUSH-9.3.1):** brauzer icazəni proqram səviyyəsində məcbur etməyə imkan vermir. Davamlı xəbərdarlıq+izah — praktik təşviq.

**9.3.2 Göndərilmə Şərtləri:** yeni sifariş→yalnız kuryer onlayn+rayon uyğun+icazə verilib (app bağlı olsa da gedir). Offline kuryerə getmir.

**9.3.3 Rola Görə Nümunələr:**
| Hadisə | Kimə | Mətn nümunəsi |
|---|---|---|
| Yeni sifariş (rayonunda) | Kuryer/Yükdaşıma | «Yeni sifariş — Nəsimi, 6.50 ₼» |
| Sifariş qəbul edildi | Müştəri | «Daşıyıcı tapıldı! Rəşad K. qəbul etdi» |
| Sifariş ləğv edildi | Daşıyıcı | «Götürdüyünüz sifariş ləğv olundu» |
| Abunə bitir | Kuryer | «Abunəniz 3 gün sonra bitir — ödəyin» |
| Ödəniş uğurlu | Kuryer | «Abunəniz yeniləndi (+30 gün)» |

**(PUSH-9.3.3):** VAPID açar cütü tələb olunur (bir dəfə generasiya). iOS-da app ana ekrana əlavə olunmayıbsa push işləməz.

### 9.4 Deployment (Hostinger VPS)
| Komponent | Konfiqurasiya |
|---|---|
| Web server | Nginx — hər subdomen üçün ayrı server bloku |
| PHP | PHP-FPM 8.3; SSE üçün ayrı pool tövsiyə |
| DB | MySQL 8.0 (lokal socket) |
| TLS | Let's Encrypt / wildcard `*.birlikde.biz` |
| SSE | `proxy_buffering off`, `fastcgi_read_timeout` artırılır |
| Firewall | UFW — 80/443/SSH |
| Cron | `sifaris_temizle` (saatlıq), `abunelik_yoxla` (gündəlik) |

---

## 10. Dizayn və Təhlükəsizlik

### 10.1 Dizayn Standartı
Fon: `#000000` (OLED qara); səthlər: glassmorphism (blur+yarımşəffaf). Şrift: SF Pro (fallback: -apple-system, Segoe UI, Roboto). Düymələr: 14–16px radius, basılanda scale animasiya. PWA: standalone, qara status bar, safe-area (notch).
```css
.glass {
  background: rgba(255,255,255,0.06);
  backdrop-filter: blur(18px) saturate(140%);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 20px;
}
```

### 10.2 Təhlükəsizlik
- SQL injection: bütün sorğular PDO prepared statement; raw interpolation YOX.
- XSS: çıxış `htmlspecialchars` ilə escape; CSP başlığı.
- Sessiya: httponly+secure+samesite cookie; login-də `session_regenerate_id`.
- CSRF: bütün yazma sorğularında token (`hash_equals`).
- Parol: `password_hash` (bcrypt/argon2).
- Rol qoruması: middleware hər sorğuda rolu yoxlayır (IDOR qoruması).
- Həssas məlumat (parol hash, sessiya token) təhlükəsiz saxlanır.
- Rate-limit: giriş, SSE, ödəniş üçün.

---

## 11. Texniki Yol Xəritəsi (Fazalar)

> Hər faza öz içində tamamlanıb yoxlanılmalıdır (syntax `php -l`, DB import testi, funksional yoxlama) — sonra növbətiyə keçilir. İstifadəçi açıq şəkildə "Növbəti fazaya keç" deməsə, keçilmir.

- **Faza 0 — Təməl:** VPS qurulumu (Nginx, PHP-FPM 8.3, MySQL 8.0, TLS — konfiqurasiya təsviri, canlı server yoxdur); qovluq strukturu; Core (Router, PDO DB, Session, Request/Response); config/.env sistemi; error handler.
- **Faza 1 — Verilənlər Bazası:** bütün cədvəllərin migration-ları (bölmə 4); seed (şəhərlər, rayonlar); `legal_logs`+`ayarlar`.
- **Faza 2 — Autentifikasiya:** qeydiyyat/giriş (müştəri+kuryer+yükdaşıma), rol yönləndirmə; **OTP/SMS YOXDUR** (bax ziddiyyət qeydi); sessiya, CSRF, rate-limit middleware.
- **Faza 3 — Sifariş və SSE:** müştəri sifariş yaratma/ləğv/tarixçə; SSE lövhə+rayon filtri; atomic götürmə (race qoruması)+WhatsApp link generasiyası; onlayn/offline, tamamlama, cron (1 saat passivləşmə).
- **Faza 4 — Admin Paneli:** ayrı subdomen, admin auth; müştəri/kuryer tablar+pop-up+bloklama; sifariş idarəsi, filtr, pagination; abunə idarəsi (fərdi+qlobal toggle), banner sistemi.
- **Faza 5 — Abunə və Ödəniş:** abunə məntiqi, xəbərdarlıq, cron; payment adapter (Birbank/Payriff — interfeys hazır, konkret inteqrasiya açar/sirlər gələndə); webhook doğrulama.
- **Faza 6 — PWA və Cilalama:** manifest, Service Worker, ana ekrana əlavə (Android+iOS); çoxdilli (AZ/RU/EN); dizayn cilası; test, təhlükəsizlik yoxlaması, canlıya keçid hazırlığı.
- **Faza 7 — Hüquqi (sonra):** İstifadəçi Müqaviləsi+Məxfilik Siyasəti (hüquqşünasdan sonra); qeydiyyatda razılıq checkbox+`legal_logs` (checkbox infrastrukturu Faza 2-də hazırlanır, mətnlər Faza 7-də doldurulur).

---

## Əlavə A — Endpoint Xəritəsi

| Metod & Yol | Rol | Təyinat |
|---|---|---|
| `POST /qeydiyyat` | Hər ikisi | Qeydiyyat (OTP YOX) |
| `POST /giris` | Hər ikisi | Giriş (nömrə+parol) |
| `POST /cixis` | Hər ikisi | Çıxış — sessiya ləğvi (7.3.1) |
| `POST /sifaris/yarat` | Müştəri | Sifariş yaratma (7.1.1) |
| `POST /sifaris/{id}/legv` | Müştəri | Ləğv (7.1.2) |
| `GET /sse/lovhe` | Kuryer | Canlı lövhə, rayon filtri (6.2) |
| `POST /sifaris/{id}/gotur` | Kuryer | Atomic götürmə (6.3) |
| `POST /sifaris/{id}/tamamla` | Kuryer | Tamamlama (7.2.4) |
| `POST /kurye/onlayn` | Kuryer | Onlayn/offline toggle |
| `POST /odenis/basla` | Kuryer | Abunə ödənişi (9.1) |
| `POST /webhook/odenis` | Sistem | Ödəniş callback (9.1) |
| `GET /admin/...` | Admin | Panel bölmələri (8) |
| `POST /admin/banner` | Admin | Banner idarəsi (8.4) |

> Qeyd: mənbə sənəddə `POST /otp/dogrula` var idi — OTP çıxarıldığı üçün tətbiq edilməyəcək.

## Əlavə B — İstinad Konvensiyası
Kodlaşdırma zamanı bəndlərə birbaşa istinad et:
- «6.3 bəndinə uyğun atomic götürmə kodunu yaz.»
- «4.6-dakı sifarisler DDL-inə əsasən Sifaris modelini qur.»
- «8.4 banner sistemini qur.»
