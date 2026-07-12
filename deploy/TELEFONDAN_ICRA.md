# BİRLİKDƏ — Yalnız Telefonla Yerləşdirmə Təlimatı

> Bu sənəd kompüter OLMADAN, sırf telefonla (iOS və ya Android) saytı canlıya
> çıxarmaq üçündür. Əvvəlki `YERLESDIRME_SENEDI.md` ilə eyni nəticəyə aparır,
> amma hər addım telefon ekranına uyğunlaşdırılıb: uzun mətn redaktoru (nano)
> demək olar HEÇ yerdə istifadə olunmur (telefonda barmaqla kursor gəzdirmək
> əzabdır) — onun əvəzinə tək-sətirlik kopyala-yapışdır əmrləri var, sirlər
> (parollar) əl ilə YAZILMIR, serverin özü yaradır, siz sadəcə kopyalayırsınız.

---

## 0. Lazım olan tətbiqlər

| Tətbiq | Nə üçün | Haradan |
|---|---|---|
| **Termius** | Serverə SSH ilə qoşulmaq (uzaqdan əmr yazmaq) | App Store / Google Play, pulsuzdur |
| Telefonun öz brauzeri (Safari/Chrome) | Hetzner/Hostinger panel, domen paneli, saytı test etmək | Artıq var |

Başqa heç nə lazım deyil. Termius-u indi yükləyib açın (qeydiyyat/hesab
yaratmağa ehtiyac yoxdur, sadəcə "Skip"/"Keç" edə bilərsiniz).

---

## T. Telefonda əmr necə yazılır/kopyalanır (bunu əvvəlcə oxuyun)

Bütün bu sənəd boyu sizə "bu əmri kopyalayıb Termius-a yapışdırın" deyiləcək.
Üsul həmişə eynidir:

1. Bu sənəddəki (aşağıdakı kimi) qutunun üzərindəki **"Kopyala"** düyməsinə basın.
2. Termius tətbiqinə keçin, serverinizə qoşulu olan ekrana daxil olun.
3. Terminal sahəsinə (qara ekran) barmağınızla basıb saxlayın (long-press) →
   **"Paste"** (Yapışdır) çıxacaq → ona basın.
4. Klaviaturada **Enter/Return** düyməsinə basıb əmri işə salın.

```
nümunə əmr — bunu sınayaraq öyrənə bilərsiniz
```

**Vacib fərq:** Bəzi əmrlərdə (məs. parol yaradanda) server özü bir dəyər
"çap edəcək" (ekrana yazacaq) — o dəyəri növbəti addımda İSTİFADƏ EDƏCƏKSİNİZ.
Onu köçürmək üçün: ekrandakı həmin sətrə barmağınızla basıb saxlayın, seçim
tutacaqları çıxacaq, sözü/sətri seçib **"Copy"** (Kopyala) düyməsinə basın —
bütün SSH tətbiqləri terminal ekranındakı mətni seçib köçürməyə icazə verir,
məhz bunu bacarmaq bu təlimatın açarıdır.

**Klaviatura məsləhəti:** Telefonun klaviatura ayarlarından "Avtomatik düzəliş"
/ "Predictive text" seçimini müvəqqəti söndürün (Ayarlar → Klaviatura) —
xüsusən parol/əmr yazarkən avtomatik düzəliş sözləri dəyişib əmri poza bilər.

---

## 1. Server seçimi: Hetzner yoxsa Hostinger

Hər ikisi telefon brauzerindən tam idarə oluna bilir, addımlar demək olar
kompüterdəki ilə eynidir (sayt mobil rejimə uyğunlaşır).

| Meyar | Hetzner Cloud | Hostinger VPS |
|---|---|---|
| Qiymət (ən ucuz uyğun plan) | ~4-5 €/ay (CX22, 2 vCPU/4GB) | ~5-8 $/ay (KVM 2) |
| Tövsiyə | Minimum 2 vCPU + 4GB RAM seçin (hər ikisində) | |

### 1A. Hetzner (telefon brauzerindən)

1. Brauzerdə `console.hetzner.cloud` açın, hesab yaradın, kart əlavə edin.
2. "New Project" → ad verin (`birlikde`).
3. "Add Server": Location — Nuremberg/Falkenstein; Image — Ubuntu 24.04;
   Type — ən azı CX22; **Authentication: Password** seçin (telefonla SSH
   açarı idarə etmək əlavə çətinlikdir — parolla başlamaq daha rahatdır,
   bölmə 21-də sonra sərtləşdirə bilərsiniz).
4. "Create & Buy Now" — IP ünvanı ekranda görünəcək, ilk qoşulma parolu
   e-poçtunuza gələcək (və ya konsolda "Reset root password"). **IP-ni və
   parolu bir yerə (Qeydlər tətbiqinə) yazın.**

### 1B. Hostinger (telefon brauzerindən, ya da Hostinger tətbiqi ilə)

1. `hpanel.hostinger.com` açın (və ya Hostinger tətbiqini yükləyin).
2. Sol menyu (☰) → **VPS** → Yeni VPS al.
3. Plan: ən azı **KVM 2**. Əməliyyat sistemi: **Ubuntu 24.04 (panelsiz)**.
4. Ödəyib aktivləşdirin. VPS → İdarə et bölməsində IP ünvanı və root parolu
   görünəcək — **hər ikisini Qeydlər tətbiqinə yazın.**

---

## 2. Domeni serverə bağlamaq (DNS)

Domeninizin idarə panelində (domeni aldığınız yer, ya da Hostinger-də
Domenlər → DNS Zone) telefon brauzerindən iki qeyd əlavə edin:

| Tip | Ad | Dəyər | TTL |
|---|---|---|---|
| A | `app` | Server IP-niz | 3600 |
| A | `appadmin` | Eyni server IP-niz | 3600 |

Yayılma 10 dəqiqə – bir neçə saat çəkə bilər.

---

## 3. Termius ilə serverə qoşulma

1. Termius-u açın → sağ aşağıda/yuxarıda **"+"** (yeni host) düyməsi.
2. **Address:** server IP-niz (məs. `95.216.123.45`).
3. **Username:** `root`
4. **Password:** bölmə 1-də aldığınız parol (yapışdıra bilərsiniz, əlinizlə
   yazmağa ehtiyac yoxdur — parolu Qeydlər tətbiqindən kopyalayıb bura
   yapışdırın).
5. Host-un adına basıb qoşulun. İlk dəfə "Bu serveri etibarlı sayırsınız?"
   kimi bir xəbərdarlıq çıxacaq — **Continue/Trust** basın.
6. Qara terminal ekranı açılacaq — bundan sonra bütün əmrləri buraya
   yapışdıracaqsınız.

---

## 4. Serveri hazırlamaq (paketlər)

```
apt update && apt upgrade -y
```

```
apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl php8.3-xml php8.3-gd php8.3-zip git curl unzip ufw
```

Yoxlayın:

```
php -v && nginx -v && mysql --version
```

PHP 8.3 görünmürsə:

```
apt install -y software-properties-common && add-apt-repository ppa:ondrej/php -y && apt update && apt install -y php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-curl php8.3-xml php8.3-gd php8.3-zip
```

---

## 5. Kodu serverə köçürmək

```
mkdir -p /var/www && cd /var/www && git clone https://github.com/elnryv/prohive.az.git birlikde && cd birlikde
```

Repo özəldirsə istifadəçi adı/token soruşacaq — GitHub → Settings →
Developer settings → Personal access tokens bölməsindən yaradıb parol
yerinə yapışdırın.

---

## 6. Verilənlər bazasını qurmaq (avtomatik parol)

Bu əmr DB üçün TƏSADÜFİ, güclü parol yaradıb ekrana çap edəcək — heç nə
əl ilə YAZMAYACAQSINIZ:

```
cd /var/www/birlikde && DB_PASS=$(openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 24) && echo "DB PAROLUNUZ (bunu kopyalayın, saxlayın): $DB_PASS"
```

Ekranda çıxan `DB PAROLUNUZ: xxxxxxxx` sətrindəki dəyəri seçib **kopyalayın**
(Qeydlər tətbiqinə yapışdırıb saxlaya bilərsiniz — sonra lazım olacaq).

**Eyni terminal pəncərəsində davam edin** (bağlamayın!) və bu əmri yapışdırın
— verilənlər bazasını və istifadəçini yaradacaq:

```
sudo mysql <<'SQL'
CREATE DATABASE IF NOT EXISTS birlikde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'birlikde_app'@'localhost' IDENTIFIED BY 'DB_PASS_BURAYA';
GRANT ALL PRIVILEGES ON birlikde.* TO 'birlikde_app'@'localhost';
FLUSH PRIVILEGES;
SQL
```

**Diqqət:** yuxarıdakı əmri yapışdırdıqdan SONRA, göndərmədən əvvəl,
`DB_PASS_BURAYA` sözünü silib yerinə kopyaladığınız DB parolunu yazın/
yapışdırın (bu, terminala göndərilməmiş, hələ redaktə etdiyiniz sətir olduğu
üçün normal mətn kimi barmağınızla toxunub dəyişə bilərsiniz — nano kimi
ox düymələri lazım deyil).

---

## 7. `.env` faylını doldurmaq (tək-sətir əmrlərlə, nano YOX)

```
cd /var/www/birlikde && cp .env.example .env
```

İndi bu əmrləri BİR-BİR yapışdırıb işə salın (hər biri bir sətri dəyişir).
`DB_PASS_BURAYA` yerinə bölmə 6-da kopyaladığınız DB parolunu yazın (əmri
yapışdırdıqdan sonra, göndərmədən əvvəl):

```
sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's/^SESSION_SECURE=.*/SESSION_SECURE=true/' .env
sed -i 's/^DB_DATABASE=.*/DB_DATABASE=birlikde/' .env
sed -i 's/^DB_USERNAME=.*/DB_USERNAME=birlikde_app/' .env
sed -i 's/^DB_PASSWORD=.*/DB_PASSWORD=DB_PASS_BURAYA/' .env
```

Domeniniz `birlikde.biz`-dən fərqlidirsə, bu iki sətri də öz domeninizlə
işə salın (əks halda ötürün):

```
sed -i 's#^APP_URL=.*#APP_URL=https://app.SIZIN-DOMENIZ#' .env
sed -i 's#^ADMIN_URL=.*#ADMIN_URL=https://appadmin.SIZIN-DOMENIZ#' .env
```

Doğru yazıldığını yoxlamaq üçün:

```
cat .env
```

(Ekranda bütün sətirləri görəcəksiniz — `DB_PASSWORD` sətrinin doğru dəyərlə
dolduğuna baxın.)

> Qalan sətirlər (`VAPID_*`, `WHATSAPP_SUPPORT_NUMBER`, `BIRBANK_*`/`PAYRIFF_*`)
> bölmə 15-17-də doldurulacaq — indilik yer tutucu qalsalar da sayt işləyəcək.

---

## 8. Verilənlər bazası cədvəllərini yaratmaq

```
php database/migrate.php
```

Sonra başlanğıc data:

```
php database/seed.php
```

Hər ikisi "OK ..." sətirləri ilə bitməlidir.

---

## 9. Admin hesabı yaratmaq (parol yapışdıraraq, YAZMADAN)

Əvvəlcə güclü parol yaradaq (yenə server yaradır, siz yazmırsınız):

```
openssl rand -base64 18 | tr -dc 'A-Za-z0-9' | head -c 24; echo
```

Ekrana çıxan sətri seçib **kopyalayın** (Qeydlər tətbiqinə yapışdırıb
saxlayın — bu, admin panelə giriş parolunuz olacaq, unutmayın).

İndi admin hesabını yaradın (öz telefon nömrənizi yazın):

```
php database/create_admin.php 994501234567 Ad Soyad
```

Ekranda **"Parol (ən azı 8 simvol):"** yazısı çıxacaq və heç nə görünməyəcək
(bu normaldır, təhlükəsizlik üçündür) — indi kopyaladığınız parolu **YAZMAYIN,
YAPIŞDIRIN** (long-press → Paste), sonra Enter basın.

"Admin yaradıldı" yazısı çıxsa uğurludur. Bu nömrə+parolla
`https://appadmin.SIZIN-DOMENIZ` ünvanına daxil olacaqsınız.

---

## 10. Fayl icazələri

```
cd /var/www/birlikde && chown -R www-data:www-data /var/www/birlikde && chmod -R 755 /var/www/birlikde && chmod -R 775 storage
```

---

## 11. Nginx quraşdırılması

```
cp /var/www/birlikde/deploy/nginx/app.birlikde.biz.conf /etc/nginx/sites-available/
cp /var/www/birlikde/deploy/nginx/appadmin.birlikde.biz.conf /etc/nginx/sites-available/
ln -s /etc/nginx/sites-available/app.birlikde.biz.conf /etc/nginx/sites-enabled/
ln -s /etc/nginx/sites-available/appadmin.birlikde.biz.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
```

Domeniniz `birlikde.biz`-dən fərqlidirsə, bu tək əmrlə hər iki fayldakı
domeni əvəz edin (`SIZIN-DOMENIZ` yerinə real domeninizi yazaraq):

```
sed -i 's/birlikde\.biz/SIZIN-DOMENIZ/g' /etc/nginx/sites-available/app.birlikde.biz.conf /etc/nginx/sites-available/appadmin.birlikde.biz.conf
```

Yoxlayın (SSL hələ olmadığı üçün xəta verə bilər, normaldır — bölmə 13-dən
sonra düzələcək):

```
nginx -t
```

---

## 12. PHP-FPM tənzimləməsi (nano YOX, tək-sətir əmrlərlə)

```
sed -i 's/^pm.max_children = .*/pm.max_children = 50/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^pm.start_servers = .*/pm.start_servers = 10/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^pm.min_spare_servers = .*/pm.min_spare_servers = 5/' /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^pm.max_spare_servers = .*/pm.max_spare_servers = 20/' /etc/php/8.3/fpm/pool.d/www.conf
grep -q '^request_terminate_timeout' /etc/php/8.3/fpm/pool.d/www.conf || echo 'request_terminate_timeout = 3600' >> /etc/php/8.3/fpm/pool.d/www.conf
sed -i 's/^;\?request_terminate_timeout.*/request_terminate_timeout = 3600/' /etc/php/8.3/fpm/pool.d/www.conf
systemctl restart php8.3-fpm
```

---

## 13. SSL sertifikatı (https)

```
apt install -y certbot python3-certbot-nginx
```

```
certbot --nginx -d app.SIZIN-DOMENIZ -d appadmin.SIZIN-DOMENIZ
```

(Yapışdırmadan əvvəl `SIZIN-DOMENIZ` yerini öz domeninizlə əvəz edin.)
Suallara: e-poçtunuzu yazın, şərtləri qəbul edin (`A`), yönləndirmə təklif
olunsa (`2` — Redirect) seçin.

```
nginx -t && systemctl reload nginx
```

---

## 14. Cron tapşırıqları (redaktor açmadan)

Adətən `crontab -e` bir mətn redaktoru açır — telefonda bundan qaçmaq üçün
birbaşa əlavə edən əmrdən istifadə edirik:

```
(crontab -u www-data -l 2>/dev/null; echo "0 * * * * php /var/www/birlikde/cron/sifaris_temizle.php >> /var/www/birlikde/storage/logs/cron.log 2>&1"; echo "0 3 * * * php /var/www/birlikde/cron/abunelik_yoxla.php >> /var/www/birlikde/storage/logs/cron.log 2>&1") | crontab -u www-data -
```

Yoxlamaq üçün:

```
crontab -u www-data -l
```

İki sətri görməlisiniz.

---

## 15. Web Push açarları (tam avtomatik)

Bu tək əmr açarları yaradıb birbaşa `.env`-ə yazır — heç nə kopyalamağa
ehtiyac yoxdur:

```
cd /var/www/birlikde && VAPID_OUT=$(php database/generate_vapid_keys.php) && PUB=$(echo "$VAPID_OUT" | grep VAPID_PUBLIC_KEY | cut -d= -f2) && PRIV=$(echo "$VAPID_OUT" | grep VAPID_PRIVATE_KEY | cut -d= -f2) && sed -i "s#^VAPID_PUBLIC_KEY=.*#VAPID_PUBLIC_KEY=$PUB#" .env && sed -i "s#^VAPID_PRIVATE_KEY=.*#VAPID_PRIVATE_KEY=$PRIV#" .env && sed -i 's/^VAPID_SUBJECT=.*/VAPID_SUBJECT=mailto:info@birlikde.biz/' .env && echo "VAPID hazırdır"
```

**Vacib:** bu əmri YALNIZ BİR DƏFƏ işlədin, təkrar işlətməyin — dəyişsə,
əvvəlki bildiriş abunəlikləri etibarsız olar.

---

## 16. Ödəniş provayderi (Birbank/Payriff)

Bank seçib müqavilə bağlayanda (bax əsas yerləşdirmə sənədi, bölmə 18) sizə
Merchant ID/Secret Key veriləcək. O zaman bu əmrləri (öz dəyərlərinizlə)
yapışdırın:

```
sed -i 's/^PAYMENT_PROVIDER=.*/PAYMENT_PROVIDER=birbank/' /var/www/birlikde/.env
sed -i 's/^BIRBANK_MERCHANT_ID=.*/BIRBANK_MERCHANT_ID=BURAYA_ID/' /var/www/birlikde/.env
sed -i 's/^BIRBANK_SECRET_KEY=.*/BIRBANK_SECRET_KEY=BURAYA_ACAR/' /var/www/birlikde/.env
```

Bank hələ seçilməyibsə bu bölməni ötürün — sayt işləyəcək, sadəcə "Ödə"
düyməsi aktiv olmayacaq. **Qeyd:** bank real sənədləşmə verəndə kodda da
kiçik uyğunlaşdırma lazım ola bilər (bax əsas sənəd, bölmə 18) — bu, ayrıca
mənə bildirməlisiniz, sadəcə `.env` ilə bitmir.

---

## 17. WhatsApp dəstək nömrəsi

```
sed -i 's/^WHATSAPP_SUPPORT_NUMBER=.*/WHATSAPP_SUPPORT_NUMBER=994501234567/' /var/www/birlikde/.env
```

(`994501234567` yerinə real dəstək nömrənizi yazın, `+` işarəsiz.)

---

## 18. PWA ikonları (indi lazım deyil, sonraya saxlaya bilərsiniz)

Hazırkı sadə "B" ikonu funksionaldır, sayt bununla tam işləyir. Real loqonuzu
telefondan yükləmək texniki cəhətdən mümkündür (Termius-da fayl köçürmə
funksiyası, ya da iOS-da Files → "Connect to Server" → `sftp://root@IP`), amma
bunun üçün əvvəlcə loqonu düzgün ölçüdə (192×192 və 512×512, tam dolu arxa
fonlu) hazırlamaq lazımdır. Tövsiyə: bu addımı KOMPÜTERƏ çıxana qədər gecikdirin
— sayt bu olmadan da tam işlək olacaq.

---

## 19. Firewall

```
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw enable
```

Soruşanda `y` yazıb Enter basın.

---

## 20. Sınaq — telefonunuzun öz brauzerində

Bu, telefonla etmək üçün əslində ƏN RAHAT hissədir — sadəcə brauzeri açın:

1. `https://app.SIZIN-DOMENIZ` — "Giriş" səhifəsi açılmalıdır.
2. `https://app.SIZIN-DOMENIZ/qeydiyyat` — 3 tab (Müştəri/Kuryer/Yükdaşıma)
   görünməlidir, test qeydiyyatdan keçin.
3. Yeni hesabla giriş edin → müştəri/kuryer panelinə düşməlisiniz.
4. `https://appadmin.SIZIN-DOMENIZ` — bölmə 9-dakı admin hesabı ilə daxil olun.
5. `https://app.SIZIN-DOMENIZ/huquqi` — hüquqi sənədlərin siyahısı görünməlidir.
6. Brauzer menyusundan **"Ana ekrana əlavə et"** seçin — PWA quraşdırma
   təklifini sınayın.

Xəta görsəniz, Termius-da bu əmrlə son logları oxuyun:

```
tail -50 /var/www/birlikde/storage/logs/*.log
```

---

## 21. Ehtiyat nüsxə (backup)

```
mkdir -p /var/backups/birlikde
(crontab -u root -l 2>/dev/null; echo "0 4 * * * mysqldump -u birlikde_app -p'DB_PASS_BURAYA' birlikde | gzip > /var/backups/birlikde/db-\$(date +\%Y\%m\%d).sql.gz"; echo "0 5 * * * find /var/backups/birlikde -name '*.sql.gz' -mtime +7 -delete") | crontab -u root -
```

(`DB_PASS_BURAYA` yerinə bölmə 6-dakı DB parolunu yazın, göndərmədən əvvəl.)

---

## 22. Hüquqi sənədlər — mütləq oxuyun

`https://app.SIZIN-DOMENIZ/huquqi` ünvanındakı 10 sənəd hazırda **QARALAMA**
statusundadır (süni intellekt hazırlayıb, hüquqşünas hələ təsdiqləməyib,
bracket-lə (`[VÖEN]`, `[məbləğ]` və s.) yer tutucular var). Real
müştərilərə açmazdan əvvəl lisenziyalı hüquqşünas yoxlaması mütləqdir.

---

## 23. Yekun yoxlama siyahısı

- [ ] Domen DNS qeydləri yayılıb (`app.` və `appadmin.`)
- [ ] `.env` doldurulub (`cat .env` ilə yoxlanılıb)
- [ ] Migration + seed işə salınıb
- [ ] Admin hesabı yaradılıb, girişi sınanıb
- [ ] Nginx + SSL işləyir, hər iki subdomen `https://` ilə açılır
- [ ] Cron-lar qoyulub (`crontab -u www-data -l` iki sətir göstərir)
- [ ] Firewall aktivdir
- [ ] Gündəlik DB backup cron-u qoyulub
- [ ] VAPID açarları yaradılıb
- [ ] WhatsApp dəstək nömrəsi düzgündür
- [ ] Ödəniş provayderi (hazır olanda) qoşulub
- [ ] **Hüquqi mətnlər hüquqşünas tərəfindən yoxlanılıb**
- [ ] Real telefonla qeydiyyat → sifariş → WhatsApp axını sınanıb

**Abunə qiymətini dəyişmək** (admin paneldə düymə yoxdur, qəsdən):

```
mysql -u birlikde_app -p birlikde -e "UPDATE ayarlar SET deyer='20.00' WHERE acar='abune_qiymeti';"
```

---

## Xəta alsanız

Termius ekranındakı xəta mesajının şəklini çəkib mənə göstərin (ekran görüntüsü
telefonda ən rahat üsuldur) — birlikdə həll edərik. Heç nəyi (xüsusən
`DROP DATABASE`, `rm -rf` kimi əmrləri) əvvəlcə soruşmadan işlətməyin.
