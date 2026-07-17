# Birlikdə Yük

Yerli yük daşıma bazarı — native PHP 8.3, MySQL 8.0, Nginx. Framework/Composer/npm yoxdur.
Tam texniki şərtnamə və icra jurnalı üçün bax `PROGRESS.md`.

İki subdomain, EYNİ kod bazası, ayrı sənəd kökü:

| Domain | Sənəd kökü | Kim üçün |
|---|---|---|
| `yuk.birlikde.biz` | `public/` | müştəri + sürücü |
| `yukadmin.birlikde.biz` | `public_admin/` | idarəetmə mərkəzi (admin) |

## 1. Server tələbləri

- PHP 8.3+ (FPM), genişləmələr: `pdo_mysql`, `mbstring`, `gd`, `curl`, `openssl`, `fileinfo`
- MySQL 8.0 (və ya MariaDB 10.11+), `utf8mb4`
- Nginx
- Let's Encrypt (Certbot) — hər iki subdomain üçün ayrı sertifikat

## 2. Quraşdırma

```bash
git clone <repo> /var/www/yuk
cd /var/www/yuk

# 1) Konfiqurasiya
cp config.example.php config.php
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"   # nəticəni config.php -> app_key sahəsinə yaz
# config.php içində db/payriff/base_url dəyərlərini doldur

# 2) Verilənlər bazası
mysql -u root -p -e "CREATE DATABASE yuk_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'yuk_user'@'127.0.0.1' IDENTIFIED BY 'CHANGE_ME';"
mysql -u root -p -e "GRANT ALL ON yuk_db.* TO 'yuk_user'@'127.0.0.1';"
mysql -u yuk_user -p yuk_db < db/install.sql

# 3) Qovluq icazələri
mkdir -p public/uploads/vehicles public/uploads/listings public/storage/og storage/logs
chown -R www-data:www-data public/uploads public/storage storage

# 4) VAPID açarları (Web Push üçün, birdəfəlik)
php cron/vapid_keygen.php
# nəticə birbaşa settings cədvəlinə yazılır (vapid_public/vapid_private); --force ilə yenidən yaradıla bilər
```

**DƏRHAL DƏYİŞİN:** seed admin girişi `admin` / `ChangeMe!2026` (`db/install.sql`) — ilk girişdən sonra
şifrəni admin panelindən dəyişin.

## 3. Nginx + SSL

`nginx/yuk.birlikde.biz.conf` və `nginx/yukadmin.birlikde.biz.conf` fayllarını
`/etc/nginx/sites-available/`-ə köçürün, `sites-enabled/`-ə simlink yaradın:

```bash
cp nginx/*.conf /etc/nginx/sites-available/
ln -s /etc/nginx/sites-available/yuk.birlikde.biz.conf /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/yukadmin.birlikde.biz.conf /etc/nginx/sites-enabled/

# http bloğuna (nginx.conf) rate-limit zonalarını əlavə edin — konfiq fayllarında istinad olunur:
#   limit_req_zone $binary_remote_addr zone=yuk_general:10m rate=10r/s;
#   limit_req_zone $binary_remote_addr zone=yuk_admin:10m rate=5r/s;
#   limit_req_zone $binary_remote_addr zone=yuk_login:10m rate=1r/s;
# (yuk_login: /giris formuna qarşı IP-səviyyəli brute-force limiti, hər iki sənəd kökündə istifadə olunur —
#  hesab səviyyəli 5 cəhd/15 dəqiqə kilidinə əlavə qat, bax App\Core\Auth/AdminAuth)

certbot --nginx -d yuk.birlikde.biz -d yukadmin.birlikde.biz
nginx -t && systemctl reload nginx
```

**Vacib qeyd — `public_admin` ayrı sənəd kökündədir:** `public_admin/assets/css/{app.css,admin.css}`
`public/assets/css/`-in əl ilə saxlanılan surətidir (simlink deyil, çünki iki ayrı sənəd kökü Nginx
tərəfindən tam təcrid olunur). **CSS-də dəyişiklik edəndə hər iki qovluğu yeniləyin**, əks halda admin
paneli üslubsuz görünəcək:

```bash
cp public/assets/css/app.css public/assets/css/admin.css public_admin/assets/css/
```

## 4. Cron qeydiyyatı

```
crontab -e
```

```cron
# Elan müddəti bitmə/tamamlanma yoxlaması — hər saat
0 * * * * /usr/bin/php /var/www/yuk/cron/hourly.php >> /var/www/yuk/storage/logs/hourly.log 2>&1

# Abunə bitmə, xatırlatma push-ları, sse_events təmizliyi, sitemap.xml — hər gecə 03:00
0 3 * * * /usr/bin/php /var/www/yuk/cron/daily.php >> /var/www/yuk/storage/logs/daily.log 2>&1

# Yarımçıq (pending) Payriff ödənişlərinin təkrar yoxlanması — hər 15 dəqiqə
*/15 * * * * /usr/bin/php /var/www/yuk/cron/payriff_recheck.php >> /var/www/yuk/storage/logs/payriff_recheck.log 2>&1

# Gecəlik yedəkləmə (mysqldump + uploads rsync) — hər gecə 02:30
30 2 * * * /var/www/yuk/scripts/backup.sh >> /var/www/yuk/storage/logs/backup.log 2>&1
```

Əl ilə `crontab -e` etmək əvəzinə, yuxarıdakı 4 sətri idempotent əlavə edən hazır skript də var
(artıq quraşdırılmış sətirləri təkrarlamır, təhlükəsiz şəkildə istənilən qədər işə salına bilər):

```bash
bash scripts/install_cron.sh
crontab -l   # təsdiq üçün
```

## 5. Yedəkləmə

`scripts/backup.sh` `config.php`-dən DB məlumatlarını oxuyur, `mysqldump` ilə sıxılmış dump yaradır
(`--single-transaction`, InnoDB üçün kilidsiz) və `public/uploads/`-u rsync edir. Defolt hədəf qovluq
`/var/backups/birlikde-yuk` (dəyişmək üçün `BIRLIKDE_BACKUP_DIR` env dəyişəni), 14 gündən köhnə
dump-lar avtomatik silinir.

## 6. Payriff (ödəniş) inteqrasiyası

`config.php` → `payriff.merchant_id`/`payriff.secret_key` boş qaldıqca ödəniş düymələri "tezliklə"
rejimindədir, sistem yıxılmır (`PayriffProvider::isConfigured()`). Canlıya keçməzdən əvvəl:

1. Payriff kabinetindən Secret Key alın, `config.php`-ə yazın.
2. `payriff.callback_url` real domenə (`https://yuk.birlikde.biz/odenis/callback`) uyğun olmalıdır.
3. Sandbox açarları ilə bir test ödənişi edib `storage/logs/payriff.log`-u yoxlayın.

Callback təhlükəsizlik qaydası: gələn callback body-yə güvənilmir, status Payriff-dən
(`getOrderStatus`) yenidən sorğulanır və `FOR UPDATE` ilə idempotent tətbiq olunur.

## 7. Dillər

`app/lang/{az,ru,en}.php` — interfeys mətnləri. Yeni açar əlavə edəndə **hər üç faylda** eyni açarın
olması tələb olunur (parity yoxlaması: `php -r` ilə açar siyahılarını müqayisə edin, ya da
PROGRESS.md-dəki FAZA 6 qeydinə bax).

## 8. Fontlar (ixtiyari)

Hazırda `public/assets/fonts/DejaVuSans(.ttf/-Bold.ttf)` fallback şrift kimi bundle edilib (internet
girişi olmayan mühitdə build edildiyi üçün). Əsl brend şriftlərini (Space Grotesk/Manrope `.woff2`)
əlavə etmək üçün onları `public/assets/fonts/`-a qoyub `public/assets/css/app.css`-dəki `@font-face`
qaydalarını bağlayın.

## 9. İnkişaf (lokal test)

```bash
cp config.example.php config.php   # local qiymətlərlə redaktə et (base_url: http://localhost:8080)
php -S localhost:8080 -t public
php -S localhost:8081 -t public_admin
```
