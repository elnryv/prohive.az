# BİRLİKDƏ — Saytı Canlıya Çıxarma Sənədi (Addım-Addım)

> Bu sənəd sıfırdan başlayaraq, heç bir texniki təcrübəniz olmasa belə, saytı
> internetdə tam işlək vəziyyətə gətirməyiniz üçün yazılıb. Kod artıq hazırdır
> (GitHub reposunda) — burada YALNIZ "bu kodu haraya qoyum, necə işə salım"
> sualının cavabı var, yeni kod yazmaq YOXDUR.
>
> Server seçimi (Hetzner və ya Hostinger) hələ qəti deyilsə də narahat olmayın —
> 1-ci addımda hər ikisi üçün ayrıca izah var, 2-ci addımdan sonra HƏR İKİSİ
> ÜÇÜN eyni təlimat keçərlidir (çünki hər ikisi sadəcə Ubuntu server verir).

---

## Məzmun

0. [Əvvəlcədən nəyə ehtiyacınız var](#0-əvvəlcədən-nəyə-ehtiyacınız-var)
1. [Server seçimi: Hetzner yoxsa Hostinger](#1-server-seçimi-hetzner-yoxsa-hostinger)
2. [Domeni serverə bağlamaq (DNS)](#2-domeni-serverə-bağlamaq-dns)
3. [Serverə ilk qoşulma (SSH)](#3-serverə-ilk-qoşulma-ssh)
4. [Serveri hazırlamaq (paketlərin qurulması)](#4-serveri-hazırlamaq-paketlərin-qurulması)
5. [Verilənlər bazasını (MySQL) qurmaq](#5-verilənlər-bazasını-mysql-qurmaq)
6. [Layihə qovluq strukturu — nə haraya gedir](#6-layihə-qovluq-strukturu--nə-haraya-gedir)
7. [Kodu serverə köçürmək](#7-kodu-serverə-köçürmək)
8. [`.env` faylını hazırlamaq (bütün dəyişənlər izahla)](#8-env-faylını-hazırlamaq-bütün-dəyişənlər-izahla)
9. [Verilənlər bazası cədvəllərini yaratmaq (migration)](#9-verilənlər-bazası-cədvəllərini-yaratmaq-migration)
10. [Başlanğıc məlumatları yükləmək (seed)](#10-başlanğıc-məlumatları-yükləmək-seed)
11. [Admin hesabı yaratmaq](#11-admin-hesabı-yaratmaq)
12. [Fayl icazələri](#12-fayl-icazələri)
13. [Nginx quraşdırılması (iki subdomen)](#13-nginx-quraşdırılması-iki-subdomen)
14. [PHP-FPM tənzimləməsi](#14-php-fpm-tənzimləməsi)
15. [SSL sertifikatı (https)](#15-ssl-sertifikatı-https)
16. [Cron tapşırıqları (avtomatik fon prosesləri)](#16-cron-tapşırıqları-avtomatik-fon-prosesləri)
17. [Web Push açarları (VAPID)](#17-web-push-açarları-vapid)
18. [Ödəniş provayderi məlumatları (Birbank/Payriff)](#18-ödəniş-provayderi-məlumatları-birbankpayriff)
19. [WhatsApp dəstək nömrəsi](#19-whatsapp-dəstək-nömrəsi)
20. [PWA ikonları və marka dizaynı](#20-pwa-ikonları-və-marka-dizaynı)
21. [Firewall və təhlükəsizlik](#21-firewall-və-təhlükəsizlik)
22. [Sınaq (test) — sayt həqiqətən işləyirmi](#22-sınaq-test--sayt-həqiqətən-işləyirmi)
23. [Ehtiyat nüsxə (backup)](#23-ehtiyat-nüsxə-backup)
24. [Hüquqi sənədlər — MÜTLƏQ oxuyun](#24-hüquqi-sənədlər--mütləq-oxuyun)
25. [Yekun yoxlama siyahısı (Go-Live Checklist)](#25-yekun-yoxlama-siyahısı-go-live-checklist)

---

## 0. Əvvəlcədən nəyə ehtiyacınız var

Başlamazdan əvvəl bunlar əlinizdə olmalıdır:

| # | Nə | Haradan alınır |
|---|---|---|
| 1 | Domen adı (məs. `birlikde.biz`) | İstənilən domen qeydiyyat şirkəti (məs. Az.NIC, Namecheap, GoDaddy) |
| 2 | VPS server (Hetzner **və ya** Hostinger) | Bax bölmə 1 |
| 3 | SSH terminal proqramı | Windows: PuTTY və ya Windows Terminal; Mac/Linux: daxili Terminal |
| 4 | Kodun olduğu GitHub repo-suna giriş | Artıq var — `elnryv/prohive.az` |
| 5 | Bir e-poçt ünvanı | SSL sertifikatı və server qeydiyyatı üçün |

**Vacib termin izahı (bilməyənlər üçün):**
- **VPS** = internetdə daim işləyən sizin öz kompüteriniz (server). Sayt orada "yaşayır".
- **SSH** = VPS-ə uzaqdan qoşulub içində əmr yazmaq üsulu (parol və ya açarla).
- **DNS** = domen adını (`birlikde.biz`) serverin IP ünvanına (məs. `95.216.x.x`) bağlayan sistem.
- **Nginx** = server üzərində "qapıçı" proqram — gələn sorğuları qəbul edib PHP-yə ötürür.
- **PHP-FPM** = PHP kodunu icra edən proses meneceri.
- **MySQL** = verilənlər bazası — bütün istifadəçi/sifariş/s. məlumatı burada saxlanılır.

---

## 1. Server seçimi: Hetzner yoxsa Hostinger

Hər ikisi də uyğundur, kod HƏR İKİSİNDƏ eyni işləyir (çünki hər ikisi sadəcə boş
Ubuntu server verir). Fərq yalnız qiymət/panel interfeysindədir.

| Meyar | Hetzner Cloud | Hostinger VPS |
|---|---|---|
| Qiymət (təxmini, ən ucuz plan) | ~4-5 €/ay (CX22, 2 vCPU/4GB RAM) | ~5-8 $/ay (KVM 2) |
| Panel | Ayrı, sadə "Hetzner Cloud Console" | Hostinger hPanel içində "VPS" bölməsi |
| Server məkanı | Almaniya/Finlandiya (Avropa) | ABŞ/Avropa (seçim var) |
| Ubuntu şablonu | Var (22.04/24.04) | Var (22.04/24.04) |
| Tövsiyə | Texniki cəhətdən bir az daha çevik, qiyməti şəffaf | Artıq Hostinger-dən domen/hosting varsa rahatdır |

**Tövsiyə: minimum 2 vCPU + 4GB RAM** seçin (SSE canlı-lövhə eyni anda çoxlu
açıq bağlantı saxlaya bilər, ucuz 1GB RAM planlar kifayət etməyə bilər).

### 1A. Hetzner Cloud ilə server yaratma

1. https://console.hetzner.cloud ünvanında hesab açın, kart əlavə edin.
2. "New Project" → layihə adı verin (məs. `birlikde`).
3. "Add Server" düyməsi:
   - **Location:** Nuremberg və ya Falkenstein (Avropa, Azərbaycana ən yaxın).
   - **Image:** Ubuntu 24.04.
   - **Type:** ən azı CX22 (2 vCPU, 4GB RAM).
   - **SSH Key:** öz kompüterinizdə açar yoxdursa, "Generate new" seçib
     təlimata əməl edin (və ya aşağıdakı 3-cü bölümdə göstərilən üsulla parol
     ilə də qoşula bilərsiniz).
   - **Name:** `birlikde-prod`.
4. "Create & Buy Now" — bir neçə saniyəyə server hazır olur, IP ünvanı
   (məs. `95.216.123.45`) konsolda görünür — **bu IP-ni yazıb saxlayın**,
   2-ci bölmədə lazım olacaq.

### 1B. Hostinger VPS ilə server yaratma

1. https://hpanel.hostinger.com — hesabınıza daxil olun (və ya yeni açın).
2. Sol menyudan **VPS** → **Yeni VPS al** (ya da mövcud planı seçin).
3. Plan seçimi: ən azı **KVM 2** (2 vCPU, 8GB RAM tövsiyə, minimum 4GB).
4. Əməliyyat sistemi: **Ubuntu 24.04 (təmiz, panel olmadan)** seçin —
   "with panel" (cPanel və s.) seçməyin, bizim öz Nginx-imiz olacaq.
5. Server adı: `birlikde-prod`, məkan: ən yaxın (Avropa).
6. Ödəniş edib serveri aktivləşdirin. hPanel-də **VPS → İdarə et** bölməsində
   IP ünvanı görünəcək — **bu IP-ni yazıb saxlayın**.
7. Root parolunu Hostinger paneldən təyin edin (ilk qoşulmada lazımdır).

---

## 2. Domeni serverə bağlamaq (DNS)

Domeninizin qeydiyyatdan keçdiyi yerdə (və ya Hostinger-də domen də oradadırsa
hPanel → Domenlər → DNS Zone) aşağıdakı iki qeydi (A record) əlavə edin:

| Tip | Ad (Host) | Dəyər (IP) | TTL |
|---|---|---|---|
| A | `app` | Server IP-niz (məs. `95.216.123.45`) | 3600 (və ya default) |
| A | `appadmin` | Eyni server IP-niz | 3600 |

Nəticədə `app.birlikde.biz` və `appadmin.birlikde.biz` hər ikisi eyni serverə
işarə edəcək (Nginx daxildə hansının hansı olduğunu domen adına görə ayıracaq —
bax bölmə 13).

**Qeyd:** DNS dəyişikliyinin bütün internetə yayılması adətən 10-30 dəqiqə,
bəzən bir neçə saat çəkə bilər. `nslookup app.birlikde.biz` əmri ilə (öz
kompüterinizdə) yayılıb-yayılmadığını yoxlaya bilərsiniz.

---

## 3. Serverə ilk qoşulma (SSH)

Kompüterinizdə terminal açın (Mac/Linux: Terminal; Windows: PowerShell və ya
PuTTY) və yazın:

```bash
ssh root@SERVER_IP_UNVANINIZ
```

Məsələn: `ssh root@95.216.123.45`

- Hetzner: SSH key qoyubsunuzsa avtomatik girəcəksiniz. Yoxdursa, konsoldan
  "Reset root password" edib parolla girin.
- Hostinger: root parolunu hPanel-dən götürüb daxil edin (ilk girişdə parolu
  dəyişməyi təklif edə bilər — yeni güclü parol qoyun).

İlk qoşulmada "Are you sure you want to continue connecting?" sualına `yes`
yazın.

**Təhlükəsizlik tövsiyəsi (vacib, amma tələsmə olmaz):** ilk girişdən sonra
`root` ilə birbaşa işləmək əvəzinə adi istifadəçi yaratmaq və SSH açarı ilə
girişə keçmək tövsiyə olunur — bunu bölmə 21-də (Firewall və təhlükəsizlik)
izah edirik, əvvəlcə saytı işlək hala gətirək.

---

## 4. Serveri hazırlamaq (paketlərin qurulması)

Serverə SSH ilə daxil olduqdan sonra, sırayla bu əmrləri işə salın (kopyala-yapışdır):

```bash
apt update && apt upgrade -y
```

```bash
apt install -y nginx mysql-server \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl php8.3-xml php8.3-gd php8.3-zip \
  git curl unzip ufw
```

Bəzi Ubuntu versiyalarında `php8.3` deyil, sistemin default PHP versiyası fərqli
ola bilər. Yoxlamaq üçün:

```bash
php -v
```

Əgər PHP 8.3-dən aşağıdırsa (məs. 8.1), aşağıdakı əmrlərlə PHP 8.3 repo-sunu
əlavə edin:

```bash
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl php8.3-xml php8.3-gd php8.3-zip
```

Quraşdırma bitdikdən sonra yoxlayın:

```bash
php -v          # PHP 8.3.x görünməlidir
nginx -v        # nginx versiyası görünməlidir
mysql --version # MySQL 8.0.x görünməlidir
```

---

## 5. Verilənlər bazasını (MySQL) qurmaq

1. Təhlükəsizlik skriptini işə salın (root parolu qoyacaq, test istifadəçilərini
   siləcək):

```bash
mysql_secure_installation
```

Suallara: güclü root parolu qoyun, qalan hamısına (`Y`/bəli) cavab verin.

2. MySQL-ə daxil olun:

```bash
mysql -u root -p
```

3. Layihə üçün ayrıca verilənlər bazası və istifadəçi yaradın (root-u birbaşa
   tətbiqdə İSTİFADƏ ETMİRİK — təhlükəsizlik üçün):

```sql
CREATE DATABASE birlikde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'birlikde_app'@'localhost' IDENTIFIED BY 'BURAYA_GUCLU_PAROL_YAZIN';
GRANT ALL PRIVILEGES ON birlikde.* TO 'birlikde_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> **Vacib:** `BURAYA_GUCLU_PAROL_YAZIN` yerinə real, güclü, təsadüfi bir parol
> yazın (məs. 20+ simvol, hərf+rəqəm+xüsusi işarə) və onu təhlükəsiz yerdə
> (parol meneceri) saxlayın — bu, bir az sonra `.env` faylına yazılacaq (bölmə 8).

---

## 6. Layihə qovluq strukturu — nə haraya gedir

Kod serverdə **`/var/www/birlikde/`** qovluğunda yerləşəcək (Nginx konfiqurasiyası
da elə buna görə hazırlanıb — bax bölmə 13). Struktur belədir (heç nəyi əl ilə
yaratmırsınız, kodu köçürəndə (bölmə 7) hamısı öz-özünə gəlir):

```
/var/www/birlikde/
├── public/                    ← Nginx "qapı"sı, YALNIZ bura internetdən görünür
│   ├── index.php              ← app.birlikde.biz giriş nöqtəsi (müştəri+kuryer)
│   ├── admin.php              ← appadmin.birlikde.biz giriş nöqtəsi
│   ├── sw.js                  ← Service Worker (PWA offline dəstəyi)
│   ├── manifest.json          ← PWA quraşdırma faylı
│   └── assets/                ← css, js, dil faylları (az/ru/en), ikonlar
├── app/
│   ├── Core/                  ← Router, DB bağlantısı, Session və s. (toxunulmur)
│   ├── Controllers/           ← hər səhifə/əməliyyatın məntiqi
│   ├── Models/                ← verilənlər bazası cədvəlləri ilə iş
│   ├── Middleware/             ← giriş/rol/CSRF yoxlamaları
│   ├── Services/               ← SSE, ödəniş və s. xidmətlər
│   ├── Providers/              ← Birbank/Payriff ödəniş adapterləri
│   └── Views/                  ← görünən HTML səhifələr (müştəri/kuryer/admin/hüquqi)
├── config/                     ← `.env`-i oxuyan tənzimləmə faylları + hüquqi mətnlər
│   └── legal/                  ← 10 hüquqi sənədin mətni (bax bölmə 24)
├── storage/                    ← loglar, keş, sessiyalar, banner şəkilləri (YAZILA BİLƏN olmalı)
├── database/                   ← DB quruluşu (migration) + başlanğıc data (seed) + köməkçi skriptlər
├── cron/                       ← fon prosesləri (sifariş təmizləmə, abunə yoxlama)
├── routes/                     ← hansı URL hansı Controller-ə gedir
├── deploy/nginx/               ← hazır Nginx konfiqurasiya faylları (bölmə 13-də istifadə olunur)
├── bootstrap.php               ← hər şeyin başladığı fayl (toxunulmur)
├── .env.example                ← `.env`-in şablonu (bölmə 8)
└── .env                        ← ƏSL SİRLƏR (parollar) — SİZ YARADACAQSINIZ, git-də YOXDUR
```

**Ən vacib qayda:** Nginx-in "document root"-u (internetdən görünən yeganə
qovluq) `public/`-dur. `app/`, `config/`, `database/`, `storage/`, `routes/`
İNTERNETDƏN BAĞLIDIR (bölmə 13-dəki konfiqurasiya bunu təmin edir) — sadəcə
yanlışlıqla başqa cür Nginx quraşdırmayın.

---

## 7. Kodu serverə köçürmək

```bash
mkdir -p /var/www
cd /var/www
git clone https://github.com/elnryv/prohive.az.git birlikde
cd birlikde
```

Bu, bütün kodu `/var/www/birlikde/` qovluğuna endirəcək (yuxarıdakı struktur
avtomatik yaranacaq).

**Qeyd — repo özəldirsə:** `git clone` zamanı istifadəçi adı/token soruşa bilər.
GitHub-da Settings → Developer settings → Personal access tokens bölməsindən
bir token yaradıb parol yerinə onu daxil edin.

Növbəti dəfələr (kod yenilənəndə) sadəcə bu qovluqda:

```bash
cd /var/www/birlikde
git pull origin main
```

---

## 8. `.env` faylını hazırlamaq (bütün dəyişənlər izahla)

`.env` — bütün sirləri (DB parolu, ödəniş açarları və s.) saxlayan fayldır,
git-ə HEÇ VAXT yüklənmir. Şablonu köçürüb doldurun:

```bash
cd /var/www/birlikde
cp .env.example .env
nano .env
```

(`nano` — sadə mətn redaktoru; yazıb qurtaranda `Ctrl+O` → `Enter` (saxla),
`Ctrl+X` (çıx).)

Aşağıdakı cədvəldə HƏR dəyişənin nə olduğu və haradan alınacağı var:

| Dəyişən | Nə üçündür | Nə yazmalısınız |
|---|---|---|
| `APP_ENV` | Mühit tipi | `production` (dəyişməyin) |
| `APP_DEBUG` | Xəta detalları göstərilsin? | `false` (canlıda HEÇ VAXT `true` olmasın — sirr sızdıra bilər) |
| `APP_URL` | Müştəri/kuryer domeni | `https://app.birlikde.biz` |
| `ADMIN_URL` | Admin domeni | `https://appadmin.birlikde.biz` |
| `APP_TIMEZONE` | Vaxt qurşağı | `Asia/Baku` (dəyişməyin) |
| `DB_HOST` | DB serveri | `127.0.0.1` (eyni serverdədirsə dəyişməyin) |
| `DB_PORT` | DB portu | `3306` (dəyişməyin) |
| `DB_DATABASE` | DB adı | `birlikde` (bölmə 5-də yaratdığınız ad) |
| `DB_USERNAME` | DB istifadəçisi | `birlikde_app` (bölmə 5-də yaratdığınız) |
| `DB_PASSWORD` | DB parolu | Bölmə 5-də qoyduğunuz güclü parol |
| `DB_CHARSET` | Kodlaşdırma | `utf8mb4` (dəyişməyin) |
| `SESSION_NAME` | Sessiya cookie adı | Dəyişməyin |
| `SESSION_LIFETIME` | Sessiya müddəti | `0` (brauzer bağlananda bitir, remember-me ayrıdır — dəyişməyin) |
| `SESSION_SECURE` | Yalnız HTTPS-də cookie | `true` (SSL quraşdırdıqdan sonra MÜTLƏQ `true` qalsın) |
| `SESSION_SAMESITE` | Cookie təhlükəsizlik siyasəti | `Lax` (dəyişməyin) |
| `ADMIN_SESSION_NAME` | Admin sessiya cookie adı | Dəyişməyin |
| `CSRF_TOKEN_NAME` | CSRF token adı | Dəyişməyin |
| `REMEMBER_ME_COOKIE` | "Məni xatırla" cookie adı | Dəyişməyin |
| `REMEMBER_ME_DAYS` | Neçə gün yadda saxlansın | `365` (istəsəniz dəyişin) |
| `ADMIN_SESSION_IDLE_MINUTES` | Admin neçə dəqiqə hərəkətsizlikdən sonra çıxarılsın | `15` (istəsəniz dəyişin) |
| `PAYMENT_PROVIDER` | Hansı bank istifadə olunur | `birbank` və ya `payriff` — bax bölmə 18 |
| `BIRBANK_MERCHANT_ID` / `BIRBANK_SECRET_KEY` / `BIRBANK_PAYMENT_URL` | Birbank hesab məlumatları | Bax bölmə 18 |
| `PAYRIFF_MERCHANT_ID` / `PAYRIFF_SECRET_KEY` / `PAYRIFF_PAYMENT_URL` | Payriff hesab məlumatları | Bax bölmə 18 |
| `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` / `VAPID_SUBJECT` | Web Push bildiriş açarları | Bax bölmə 17 |
| `WHATSAPP_SUPPORT_NUMBER` | Dəstək WhatsApp nömrəsi | Bax bölmə 19 |
| `LOG_LEVEL` | Log detallılığı | `error` (dəyişməyin) |

**Bu mərhələdə** DB sətirlərini (5 dənə) dolduraraq davam edə bilərsiniz —
VAPID/ödəniş/WhatsApp sətirlərini bölmə 17-19-da dolduracaqsınız (o vaxta kimi
yer tutucu qalsalar da sayt işləyəcək, sadəcə həmin funksiyalar — bildiriş,
ödəniş — aktiv olmayacaq).

---

## 9. Verilənlər bazası cədvəllərini yaratmaq (migration)

Aşağıdakı əmr bütün 16 cədvəli avtomatik yaradacaq (heç bir SQL yazmağınıza
ehtiyac yoxdur):

```bash
cd /var/www/birlikde
php database/migrate.php
```

Uğurlu olarsa, hər cədvəl üçün `OK   NNN_...sql` sətri görəcəksiniz. Yaranan
cədvəllər:

| Cədvəl | Nə saxlayır |
|---|---|
| `sehirler` | Şəhərlər (Bakı, Sumqayıt) |
| `rayonlar` | Rayonlar/əraziler |
| `users` | Bütün istifadəçilər (müştəri/kuryer/yükdaşıma) |
| `sessiyalar` | "Məni xatırla" tokenləri |
| `kuryeler` | Kuryer/yükdaşıma əlavə məlumatları |
| `yukdasima_olculeri` | Yük ölçü kateqoriyaları (XS-Mega) |
| `dasiyici_olculeri` | Daşıyıcının seçdiyi ölçülər |
| `kurye_bolgeler` | Kuryerin xidmət etdiyi rayonlar |
| `sifarisler` | Bütün sifarişlər |
| `abunelikler` | Kuryer abunəlik dövrləri |
| `odenisler` | Ödəniş əməliyyatları |
| `bannerler` | Reklam bannerləri |
| `ayarlar` | Qlobal tənzimləmələr (abunə qiyməti, rejim) |
| `legal_logs` | Hüquqi razılıq audit jurnalı |
| `adminler` | Admin hesabları |
| `push_abuneler` | Web Push bildiriş abunəlikləri |

Bu əmri bir daha işə salsanız (məs. yeni versiyaya keçəndə), artıq tətbiq
olunmuş migration-ları TƏKRAR işlətmir (avtomatik izləyir) — təhlükəsizdir.

---

## 10. Başlanğıc məlumatları yükləmək (seed)

```bash
php database/seed.php
```

Bu, boş cədvəllərə başlanğıc məlumatları yükləyir: Bakı+Sumqayıt şəhərləri,
36 rayon, yük ölçü kateqoriyaları (XS-Mega), qlobal ayarlar (abunə qiyməti
default 15 AZN/ay — dəyişmək istəsəniz bölmə 25-ə baxın).

**Qeyd:** Sumqayıt rayon siyahısı canlıya keçmədən əvvəl rəsmi mənbədən (məs.
rayon icra hakimiyyəti) yoxlanmalıdır — sənəddə YER TUTUCU kimi qeyd olunub.

---

## 11. Admin hesabı yaratmaq

Admin panelə **web-dən qeydiyyat forması QƏSDƏN yoxdur** (təhlükəsizlik üçün) —
yalnız server konsolundan bu əmrlə yaradılır:

```bash
php database/create_admin.php 994501234567 Ad Soyad
```

(`994501234567` yerinə öz telefon nömrənizi, `Ad Soyad` yerinə öz adınızı yazın.)

Əmr sizdən parol soruşacaq (ekranda görünməyəcək, bu normaldır) — ən azı 8
simvoldan ibarət güclü parol yazın. Bundan sonra bu nömrə+parolla
`https://appadmin.birlikde.biz` ünvanına daxil ola biləcəksiniz.

İstədiyiniz qədər admin hesabı yarada bilərsiniz (əmri təkrar işlədin).

---

## 12. Fayl icazələri

Sayt şəkil yükləmə (banner), log yazma, sessiya saxlama üçün `storage/`
qovluğuna yaza bilməlidir:

```bash
cd /var/www/birlikde
chown -R www-data:www-data /var/www/birlikde
chmod -R 755 /var/www/birlikde
chmod -R 775 storage
```

---

## 13. Nginx quraşdırılması (iki subdomen)

Layihədə hazır konfiqurasiya faylları var — sadəcə düzgün yerə köçürmək kifayətdir:

```bash
cp /var/www/birlikde/deploy/nginx/app.birlikde.biz.conf /etc/nginx/sites-available/
cp /var/www/birlikde/deploy/nginx/appadmin.birlikde.biz.conf /etc/nginx/sites-available/
ln -s /etc/nginx/sites-available/app.birlikde.biz.conf /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/appadmin.birlikde.biz.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
```

**Diqqət:** Bu fayllar domen adınızın `birlikde.biz` olduğunu güman edir. Əgər
sizin domeniniz FƏRQLİDİRSƏ, köçürmədən əvvəl faylların içindəki bütün
`birlikde.biz` sözünü öz domeninizlə əvəz edin:

```bash
nano /etc/nginx/sites-available/app.birlikde.biz.conf
nano /etc/nginx/sites-available/appadmin.birlikde.biz.conf
```

(`server_name` və `ssl_certificate` sətirlərindəki domen adlarına baxın.)

Konfiqurasiyanı yoxlayın (xəta yoxdursa "syntax is ok" yazacaq):

```bash
nginx -t
```

**Qeyd:** Bu mərhələdə `nginx -t` SSL sertifikatı hələ olmadığı üçün xəta verə
bilər — bu normaldır, bölmə 15-i tamamlayandan sonra düzələcək. İndilik davam edin.

---

## 14. PHP-FPM tənzimləməsi

Standart PHP-FPM ayarları kiçik/orta trafik üçün kifayətdir, amma canlı
lövhənin (SSE) uzun açıq bağlantılar saxlaması səbəbindən bir neçə dəyəri
artırmaq tövsiyə olunur:

```bash
nano /etc/php/8.3/fpm/pool.d/www.conf
```

Bu sətirləri tapıb (və ya olmadıqda əlavə edib) belə edin:

```ini
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
request_terminate_timeout = 3600
```

Saxlayıb PHP-FPM-i yenidən başladın:

```bash
systemctl restart php8.3-fpm
```

---

## 15. SSL sertifikatı (https)

Pulsuz Let's Encrypt sertifikatı üçün Certbot quraşdırın:

```bash
apt install -y certbot python3-certbot-nginx
```

DNS artıq yayılıbsa (bölmə 2-dən sonra bir az gözləmiş olmalısınız), sertifikatı yaradın:

```bash
certbot --nginx -d app.birlikde.biz -d appadmin.birlikde.biz
```

Suallara: e-poçt ünvanınızı yazın, şərtləri qəbul edin, HTTP→HTTPS
yönləndirməni (redirect) TƏKLİF edərsə "Yes/2" seçin.

Uğurlu olduqdan sonra:

```bash
nginx -t
systemctl reload nginx
```

Certbot sertifikatları avtomatik yeniləyir (90 gündə bir), əlavə iş lazım
deyil, amma yoxlamaq istəsəniz:

```bash
certbot renew --dry-run
```

---

## 16. Cron tapşırıqları (avtomatik fon prosesləri)

İki fon prosesi var: bitməmiş sifarişləri passivləşdirmə (saatlıq) və abunə
müddətini yoxlama (gündəlik).

```bash
crontab -u www-data -e
```

(İlk dəfədirsə redaktor seçimi soruşa bilər — `nano` seçin.) Aşağıdakı iki
sətri əlavə edin:

```cron
0 * * * * php /var/www/birlikde/cron/sifaris_temizle.php >> /var/www/birlikde/storage/logs/cron.log 2>&1
0 3 * * * php /var/www/birlikde/cron/abunelik_yoxla.php >> /var/www/birlikde/storage/logs/cron.log 2>&1
```

Saxlayıb çıxın. Bu, hər saat başı köhnə "axtarışda" sifarişləri passivləşdirir,
hər gün saat 03:00-da abunəsi bitənləri bağlayır və bitməyə 3 gün qalanlara
xəbərdarlıq yazır.

---

## 17. Web Push açarları (VAPID)

Kuryerlərə brauzer bildirişi göndərmək üçün bir dəfəlik açar cütü yaradılmalıdır:

```bash
cd /var/www/birlikde
php database/generate_pwa_icons.php   # bunu bölmə 20-də edəcəksiniz, qarışdırmayın
php database/generate_vapid_keys.php
```

Nəticədə ekranda `VAPID_PUBLIC_KEY=...` və `VAPID_PRIVATE_KEY=...` görəcəksiniz —
bu iki sətri kopyalayıb `.env` faylına yapışdırın (`nano .env`), həmçinin
`VAPID_SUBJECT` sətrinə öz e-poçtunuzu yazın: `mailto:info@birlikde.biz`.

**Vacib:** bu açarları YALNIZ BİR DƏFƏ yaradın və heç vaxt dəyişməyin —
dəyişsəniz, o ana qədər qeydə alınmış bütün bildiriş abunəlikləri etibarsız olar.

> **Qeyd:** hazırkı kodda faktiki şifrələnmiş bildiriş GÖNDƏRMƏ hələ
> tamamlanmayıb (yalnız qeydə alma/loqlama var) — bu, gələcək bir yeniləmədə
> əlavə olunacaq. Açarları indi hazırlamağınız hazırlıq üçündür, funksiya
> özü sonra aktivləşəcək.

---

## 18. Ödəniş provayderi məlumatları (Birbank/Payriff)

Kuryerlər aylıq abunə haqqını buradan ödəyəcək. Hər hansı BİRİNİ seçin:

- **Birbank Business** — Kapital Bank-ın ödəniş həlli, `https://birbank.az`
  (Business bölməsindən müraciət, VÖEN/biznes sənədləri tələb olunur).
- **Payriff** — Pasha Bank-a bağlı ödəniş provayderi, `https://payriff.com`
  (onlayn müraciət, biznes sənədləri tələb olunur).

Hər ikisi ilə müqavilə bağlayanda sizə **Merchant ID** və **Secret Key**
veriləcək (bank tərəfindən), habelə API sənədləşməsində real ödəniş URL-i
göstəriləcək. Bunları `.env`-də uyğun sətirlərə yazın:

```
PAYMENT_PROVIDER=birbank
BIRBANK_MERCHANT_ID=bankın_sizə_verdiyi_ID
BIRBANK_SECRET_KEY=bankın_sizə_verdiyi_gizli_açar
BIRBANK_PAYMENT_URL=bankın_sənədləşməsindəki_real_URL
```

**⚠ Vacib texniki qeyd:** hazırkı kod bu adapterləri "sahə adları YER
TUTUCUDUR" statusunda hazırlamışdı (bank sənədləşməsi əvvəlcədən yox idi).
Bank sizə real API sənədləşməsini verəndə, ehtimal ki `app/Providers/
BirbankProvider.php` (və ya `PayriffProvider.php`) faylındakı sahə adları/imza
üsulu bankın həqiqi tələbinə uyğun YENİLƏNMƏLİDİR — bu, kodda dəyişiklik
tələb edir, sadəcə `.env` doldurmaqla bitmir. Bu mərhələyə çatanda ayrıca
bildirin, real bank sənədləşməsinə uyğun kodu yeniləyərik.

Bank hələ seçilməyibsə, bu sətirləri yer tutucu vəziyyətdə saxlaya bilərsiniz —
sayt işləyəcək, sadəcə "Ödə" düyməsi işə düşməyəcək.

---

## 19. WhatsApp dəstək nömrəsi

Platformanın rəsmi dəstək/şikayət WhatsApp nömrəsini `.env`-də yazın (yalnız
rəqəmlər, ölkə kodu ilə, `+` işarəsiz):

```
WHATSAPP_SUPPORT_NUMBER=994501234567
```

---

## 20. PWA ikonları və marka dizaynı

Hazırda tətbiqdə **proqramla çəkilmiş sadə "B" hərfi** işlədilir (real loqo
deyil, funksional yer tutucudur). Real loqonuz hazır olanda iki seçim var:

**A) Ən sadə üsul:** hazır loqonuzu 192×192 və 512×512 ölçülərində, tam kvadrat,
şəffaf ARXA FONSUZ (tam rəngli/qara arxa fon, guşələrə qədər) PNG kimi
hazırlayın və bu adlarla köçürün:

```bash
# öz kompüterinizdən serverə (yerli terminaldan işə salın, serverdə yox):
scp icon-192.png root@SERVER_IP:/var/www/birlikde/public/assets/icons/icon-192.png
scp icon-512.png root@SERVER_IP:/var/www/birlikde/public/assets/icons/icon-512.png
```

**B) Skriptlə yenidən yaratmaq** (yalnız mətn/rəng dəyişmək istəsəniz):
`database/generate_pwa_icons.php` faylını açıb rəng/hərf kodunu redaktə edib
yenidən işə salın:

```bash
php database/generate_pwa_icons.php
```

**Vacib qayda (maskable ikon):** loqonun əsas hissəsi kvadratın mərkəzi
~80%-lik dairəsi daxilində qalmalıdır (kənarlara çox yaxın olmasın), əks
halda telefon ekranına əlavə edildikdə künclər kəsilə bilər. Arxa fon HƏMİŞƏ
tam rənglə guşəyə qədər dolu olmalıdır (şəffaf guşə olmamalıdır).

---

## 21. Firewall və təhlükəsizlik

```bash
ufw allow OpenSSH
ufw allow 80
ufw allow 443
ufw enable
```

(`y` yazıb təsdiqləyin.) Bundan sonra yalnız SSH (22), HTTP (80) və HTTPS (443)
portları açıq qalacaq, qalan hər şey bağlıdır.

**Əlavə tövsiyələr (məcburi deyil, amma güclü tövsiyə olunur):**
- Root ilə birbaşa SSH girişini söndürüb, adi istifadəçi + `sudo` + SSH açarı
  ilə keçin.
- `fail2ban` quraşdırın (`apt install fail2ban`) — dəfələrlə səhv parol
  cəhdlərini avtomatik bloklayır.
- MySQL-in yalnız `127.0.0.1`-dən (özündən) əlçatan olduğunu təsdiqləyin
  (default belədir, xarici IP-dən bağlı olmamalıdır).

---

## 22. Sınaq (test) — sayt həqiqətən işləyirmi

Bütün addımları tamamladıqdan sonra brauzerdə yoxlayın:

1. `https://app.birlikde.biz` — açılmalı, "Giriş" səhifəsinə yönləndirməlidir.
2. `https://app.birlikde.biz/qeydiyyat` — qeydiyyat forması görünməlidir,
   3 tab (Müştəri/Kuryer/Yükdaşıma) işləməlidir.
3. Test qeydiyyatdan keçin (öz telefon nömrənizlə) → "Giriş"ə yönləndirilməlisiniz.
4. Yeni nömrə ilə daxil olun → müştəri paneli açılmalıdır.
5. `https://appadmin.birlikde.biz` — ayrı giriş səhifəsi açılmalıdır (bölmə
   11-də yaratdığınız admin hesabı ilə daxil olun).
6. `https://app.birlikde.biz/huquqi` — 10 hüquqi sənədin siyahısı görünməlidir.
7. Telefonunuzda saytı açıb "Ana ekrana əlavə et" təklifini sınayın (PWA).

Xəta görsəniz, ən çox yayılan səbəblər:
- **500 xətası:** `.env` düzgün doldurulmayıb və ya `storage/` icazəsi yoxdur.
  Log baxın: `tail -50 /var/www/birlikde/storage/logs/*.log`
- **502 Bad Gateway:** PHP-FPM işləmir. Yoxlayın: `systemctl status php8.3-fpm`
- **Sertifikat xətası:** DNS hələ tam yayılmayıb, bir az gözləyin, `certbot --nginx` təkrar işlədin.

---

## 23. Ehtiyat nüsxə (backup)

Minimum tövsiyə — gündəlik DB dump-ı, 7 gün saxlanılsın:

```bash
mkdir -p /var/backups/birlikde
crontab -u root -e
```

Əlavə edin:

```cron
0 4 * * * mysqldump -u birlikde_app -p'DB_PAROLUNUZ' birlikde | gzip > /var/backups/birlikde/db-$(date +\%Y\%m\%d).sql.gz
0 5 * * * find /var/backups/birlikde -name "*.sql.gz" -mtime +7 -delete
```

İdeal olaraq bu backup fayllarını serverdən kənar bir yerə (məs. Hetzner
Storage Box, Hostinger-in backup xidməti, və ya öz kompüterinizə periodik
endirmə) də köçürün — server tamamilə itsə belə məlumat qalsın.

---

## 24. Hüquqi sənədlər — MÜTLƏQ oxuyun

`https://app.birlikde.biz/huquqi` ünvanında görünən 10 hüquqi sənəd (İstifadəçi
Müqaviləsi, Məxfilik Siyasəti və s.) hazırda **QARALAMA** statusundadır:

- Süni intellekt tərəfindən hazırlanıb, **lisenziyalı hüquqşünas hələ TƏSDİQLƏMƏYİB**.
- Mətndə bracket-lə (`[VÖEN]`, `[məbləğ]`, `[müddət]`, `[dəstək WhatsApp nömrəsi]`
  və s.) işarələnmiş yer tutucular var — bunlar `config/legal/content/` qovluğundakı
  fayllarda REAL məlumatla doldurulmalıdır.
- Canlıya (real müştərilərə) açmazdan ƏVVƏL bu mətnləri lisenziyalı hüquqşünasa
  göstərib təsdiq almaq MÜTLƏQDİR — hazırkı halında bu, rəsmi hüquqi sənəd
  DEYİL, yalnız işə başlanğıc nöqtəsidir.

---

## 25. Yekun yoxlama siyahısı (Go-Live Checklist)

Real istifadəçilərə açmazdan əvvəl bunların HAMISI ✅ olmalıdır:

- [ ] Domen DNS qeydləri düzgün yayılıb (`app.` və `appadmin.`)
- [ ] `.env` faylı doldurulub, `APP_DEBUG=false`, `SESSION_SECURE=true`
- [ ] Migration + seed işə salınıb, cədvəllər DB-də var
- [ ] Ən azı bir admin hesabı yaradılıb, girişi sınanıb
- [ ] Nginx + SSL işləyir, hər iki subdomen `https://` ilə açılır
- [ ] Cron tapşırıqları qoyulub (`crontab -u www-data -l` ilə yoxlanılıb)
- [ ] `storage/` qovluğu yazıla bilir (banner yükləmə sınanıb)
- [ ] Firewall (UFW) aktivdir, yalnız 22/80/443 açıqdır
- [ ] Gündəlik DB backup cron-u qoyulub
- [ ] VAPID açarları yaradılıb və `.env`-ə yazılıb
- [ ] PWA ikonları (istəsəniz real loqo ilə) yenilənib
- [ ] WhatsApp dəstək nömrəsi düzgün yazılıb
- [ ] Ödəniş provayderi seçilib, real credential-lar alınıb, kod uyğunlaşdırılıb (bölmə 18)
- [ ] **Hüquqi mətnlər hüquqşünas tərəfindən yoxlanılıb və bracket yer tutucular doldurulub**
- [ ] Abunə qiyməti (`ayarlar.abune_qiymeti` cədvəli, default 15 AZN) real
      qiymətə uyğunlaşdırılıb (admin panelindən deyil, birbaşa DB-dən: bax aşağı)
- [ ] Real telefonla qeydiyyat→sifariş→WhatsApp axını əvvəldən sona sınanıb

**Abunə qiymətini dəyişmək** (admin paneldə bu düymə yoxdur, qəsdən — yalnız
DB-dən):

```bash
mysql -u birlikde_app -p birlikde -e "UPDATE ayarlar SET deyer='20.00' WHERE acar='abune_qiymeti';"
```

(`20.00` yerinə istədiyiniz AZN məbləğini yazın.)

---

## Nəyisə başa düşmədim / xəta aldım — nə edim?

Hər addımda tıxandığınız yeri (əmr, xəta mesajı, ekran görüntüsü) mənə göstərin —
birlikdə addım-addım həll edərik. Bu sənədi izləyərkən HEÇ NƏ silməyin/dəyişməyin
əvvəlcə mənə soruşmadan (xüsusən `rm`, `DROP DATABASE` kimi geri dönməz əmrlər).
