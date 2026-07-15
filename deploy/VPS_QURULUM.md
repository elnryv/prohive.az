# VPS Qurulumu — Faza 0 (Hostinger, Ubuntu)

Qısa əməliyyat siyahısı. Detallı təhlükəsizlik/tənzimləmə seçimləri canlıya keçmədən
əvvəl nəzərdən keçirilməlidir.

1. **Paketlər:** `nginx`, `php8.3-fpm`, `php8.3-mysql`, `php8.3-mbstring`,
   `php8.3-curl`, `php8.3-xml`, `mysql-server-8.0`, `certbot` + `python3-certbot-nginx`.
2. **MySQL:** `birlikde` verilənlər bazası və ayrıca DB istifadəçisi yaradılır
   (root istifadə olunmur), `.env`-ə yazılır.
3. **Nginx:** `deploy/nginx/app.birlikde.biz.conf` və
   `deploy/nginx/appadmin.birlikde.biz.conf` `/etc/nginx/sites-available/`-ə
   kopyalanır, `sites-enabled`-ə symlink edilir, `nginx -t` yoxlanır.
4. **TLS:** `certbot --nginx -d app.birlikde.biz -d appadmin.birlikde.biz` (və ya
   wildcard `*.birlikde.biz` sertifikatı, DNS-01 challenge ilə).
5. **PHP-FPM:** SSE endpoint üçün ayrı pool tövsiyə olunur (yüksək
   `pm.max_children`, uzun `request_terminate_timeout`).
6. **Firewall:** UFW — yalnız 22 (SSH), 80, 443 açıq.
7. **Fayl icazələri:** `storage/` qovluğu `www-data` üçün yazıla bilən olmalıdır;
   `config/`, `app/`, `database/`, `routes/` Nginx-dən bloklanıb (bax nginx conf).
8. **Cron:** `crontab -u www-data -e` ilə `cron/sifaris_temizle.php` (saatlıq) və
   `cron/abunelik_yoxla.php` (gündəlik) qeydə alınır — bu skriptlər Faza 3/5-də yazılacaq.
