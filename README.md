# Birlikdə Getdik

Bölgə qonaq evləri vitrin platforması. Native PHP 8.3, MySQL 8.0, Nginx (framework/Composer yoxdur).

Tam texniki şərtnamə və icra planı üçün bax: `PROGRESS.md` (icra jurnalı, hər fazadan
sonra yenilənir).

## Lokal quraşdırma

```bash
mysql -u root -e "CREATE DATABASE getdik CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root getdik < install.sql
php -S 127.0.0.1:8000 -t public public/index.php
```

Admin panel eyni kod bazasını `public_admin/` giriş nöqtəsi ilə istifadə edir:

```bash
php -S 127.0.0.1:8001 -t public_admin public_admin/index.php
```

Dev seed admin: `admin` / `ChangeMe!2026` — istehsalat serverində dərhal dəyişin.

Serverə yerləşdirmə üçün nümunə konfiqlər `deploy/` qovluğundadır (nginx, PHP-FPM pool).