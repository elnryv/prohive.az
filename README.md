# Birlikdə Bot Builder

"ManyChat" səviyyəsində, AI-siz, qayda-əsaslı Telegram bot qurucu sistemi. Native PHP 8.3
(framework və Composer yoxdur), MySQL 8.0, admin panel OLED qara + glassmorphism dizaynla.

Bütün kodlar subdomen olaraq `bot.birlikde.biz` altında yerləşməli, kök qovluq
`/var/www/bot.birlikde.biz/` olmalıdır. Yalnız `public/` qovluğu Nginx tərəfindən serv
olunur; `app/`, `config/`, `database/`, `storage/`, `cron/`, `.env` brauzerdən gizli qalır
(bax: `deploy/bot.birlikde.biz.conf`).

## Quraşdırma addımları

### 1. Fayl yerləşdirmə

```bash
sudo mkdir -p /var/www/bot.birlikde.biz
sudo rsync -a --exclude='.git' ./ /var/www/bot.birlikde.biz/
cd /var/www/bot.birlikde.biz
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

### 2. Verilənlər bazası

```bash
mysql -u root -p -e "CREATE DATABASE birlikde_bots CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'birlikde_user'@'127.0.0.1' IDENTIFIED BY 'GÜCLÜ_PAROL';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON birlikde_bots.* TO 'birlikde_user'@'127.0.0.1'; FLUSH PRIVILEGES;"

mysql -u birlikde_user -p birlikde_bots < database/schema.sql
mysql -u birlikde_user -p birlikde_bots < database/seed.sql
```

Cədvəllər: `users, bots, bot_nodes, bot_node_buttons, bot_subscribers, subscriber_fields,
tags, subscriber_tags, bot_keywords, broadcasts, broadcast_queue, sequences,
sequence_messages, subscriber_sequences, bot_logs` — tam sxem üçün `database/schema.sql`
faylına baxın.

### 3. .env

```bash
cp .env.example .env
```

`.env` faylını doldurun:

```
APP_URL=https://bot.birlikde.biz
APP_ENV=production
DB_HOST=127.0.0.1
DB_NAME=birlikde_bots
DB_USER=birlikde_user
DB_PASS=GÜCLÜ_PAROL
TELEGRAM_API=https://api.telegram.org
SESSION_NAME=birlikde_admin
```

**VACİB:** `APP_ENV=production` olmalıdır ki, `TelegramApi` real Telegram serverlərinə
sorğu göndərsin. `APP_ENV=test` rejimində bütün Telegram API çağırışları real şəbəkəyə
çıxmır, əvəzinə `storage/logs/telegram_test.log`-a yazılır (yalnız development/test üçün).

### 4. Nginx + SSL

```bash
sudo cp deploy/bot.birlikde.biz.conf /etc/nginx/sites-available/bot.birlikde.biz.conf
sudo ln -s /etc/nginx/sites-available/bot.birlikde.biz.conf /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
sudo certbot --nginx -d bot.birlikde.biz
```

**HTTPS məcburidir** — Telegram webhook yalnız HTTPS ünvanlara qoşulur.

### 5. İlk giriş

Panelə `https://bot.birlikde.biz/login` ünvanından daxil olun:

- **Email:** `admin@birlikde.biz`
- **Parol:** `Admin123!`

İlk girişdən sonra parolu dəyişməyiniz tövsiyə olunur (hazırda parol dəyişmə UI-i yoxdur —
`users` cədvəlində `password_hash` sütununu `password_hash()` ilə yenidən yazın).

### 6. Bot əlavə etmə

Panel → **Botlarım → + Yeni bot** → ad + Telegram token daxil edin (tokeni
[@BotFather](https://t.me/BotFather)-dan alın). Sistem avtomatik olaraq:

1. `getMe` ilə tokeni doğrulayır,
2. unikal `webhook_secret` yaradır,
3. `setWebhook`-u `https://bot.birlikde.biz/webhook.php?s={secret}` ünvanına, secret token
   header yoxlaması ilə qurur.

### 7. Cron

```
* * * * * php /var/www/bot.birlikde.biz/cron/broadcast_worker.php >> /dev/null 2>&1
*/5 * * * * php /var/www/bot.birlikde.biz/cron/sequence_worker.php >> /dev/null 2>&1
```

## Layihə strukturu

```
bot.birlikde.biz/
├── public/              # Nginx-in gördüyü tək qovluq
│   ├── index.php        # Admin panel front controller
│   ├── webhook.php      # Telegram webhook endpoint
│   ├── manifest.json + sw.js  # PWA
│   └── assets/{css,js,icons}/
├── app/
│   ├── Core/             # Database, Router, Auth, Request, Response, Csrf
│   ├── Telegram/         # TelegramApi, UpdateParser, FlowEngine (bot mühərriki)
│   ├── Services/         # TemplateRenderer, SequenceTrigger (paylaşılan məntiq)
│   ├── Controllers/      # Admin panel controller-ləri
│   └── Views/            # OLED qara + glassmorphism şablonlar
├── config/config.php
├── database/{schema.sql, seed.sql}
├── storage/{logs, queue}/
├── cron/{broadcast_worker.php, sequence_worker.php}
├── deploy/bot.birlikde.biz.conf
├── .env.example
└── .gitignore
```

## Bot mühərriki necə işləyir (qısaca)

`webhook.php` → secret yoxlanır → `UpdateParser` update-i normallaşdırır →
`FlowEngine::handle()` prioritet sırası ilə işləyir:

1. **callback_data** (düymə basılıb) → hədəf node-a keç, `action_tag_id` varsa tag təyin et.
2. **`/start`** → botun `start_node_id`-sinə keç.
3. **`waiting_for` dolu** (input gözlənilir) → cavabı `subscriber_fields`-ə yaz, `input_next_node`-a keç.
4. **açar söz uyğunluğu** (`exact`/`contains`/`starts`) → hədəf node-a keç.
5. **heç nə uyğun deyil** → `fallback` node (varsa) və ya `start_node`.

Node tipləri: `text`, `buttons`, `input`, `image`, `delay`, `goto`, `api`, `final`.
`goto` zənciri sonsuz döngədən qorunmaq üçün maksimum 10 addımla məhdudlaşdırılıb.
Bütün sorğular `bot_id` ilə filtrlənir (tenant izolyasiyası).

Tag əlavə olunanda (`FlowEngine::addTagToSubscriber` və ya
`SubscriberController::addTag`) `SequenceTrigger` avtomatik yoxlanır — əgər tag bir
sequence-in `trigger_tag_id`-sidirsə, abunəçi üçün həmin sequence başladılır.

## Test hesabatı

### Bu mühitdə (Claude Code / CI konteyner) test edildi

Layihə real MariaDB 10.11 serverinə qarşı (`APP_ENV=test`, real Telegram API çağırışları
mock olunub və `storage/logs/telegram_test.log`-a yazılıb) tam simulyasiya ilə test edilib:

- **Sxem:** `database/schema.sql` xətasız import olundu (bütün cədvəllər, FK-lər).
  `database/seed.sql` uğurla yükləndi.
- **Core:** `Database` (PDO, prepared statements), `Auth` (login/logout, düzgün/yanlış
  parol ssenariləri), `Csrf` (token yaratma/yoxlama) birbaşa test skriptləri ilə yoxlandı.
- **FlowEngine (bütün axınlar simulyasiyada):**
  - `/start` → abunəçi yaradılır, `start_node`-a keçir, `bot_logs`-a in/out yazılır.
  - Callback düymə basılması → düzgün `target_node_id`-yə keçid, `answerCallbackQuery`
    çağırışı.
  - Input node → `waiting_for` təyin olunur → sonrakı mesaj `subscriber_fields`-ə yazılır
    → `input_next_node`-a keçid.
  - Açar söz (`contains`) uyğunluğu → düzgün node-a yönləndirmə.
  - `goto` özünə keçəndə 10 addımdan sonra rekursiya dayandırılır və xəta
    `storage/logs/flow_engine_error.log`-a yazılır (proses çökmür).
- **webhook.php:** PHP built-in serverlə canlı HTTP sorğuları göndərilib —
  - Yanlış `X-Telegram-Bot-Api-Secret-Token` → 200 OK, heç nə emal olunmur (abunəçi
    yaranmır).
  - Düzgün secret → normal emal, abunəçi yaranır/yenilənir.
  - Səhv JSON body → 200 OK (Telegram-ın təkrar göndərməsinin qarşısı alınır).
- **Admin panel (PHP built-in server, real HTTP + cookie sessiyaları ilə):**
  - Giriş/çıxış axını, dashboard, bot yaratma (mock `getMe`/`setWebhook` ilə).
  - Node + düymə yaratma, DB-də düzgün saxlanması yoxlanıldı.
  - **XSS testi:** node mətninə/başlığına `<script>` yazılıb, çıxışda `htmlspecialchars`
    ilə escape olunduğu (raw script tag-ın DOM-a düşmədiyi) təsdiqləndi.
  - **CSRF testi:** token olmadan POST göndərilib, əməliyyatın rədd edildiyi (sətir DB-yə
    yazılmadığı) təsdiqləndi.
  - **Owner izolyasiyası:** ikinci istifadəçi qeydiyyatdan keçirilib, birinci istifadəçinin
    botuna giriş cəhdi 404 ilə rədd edildiyi göstərildi.
- **Broadcast + Sequence + cron worker-lər:**
  - Broadcast yaradılıb, `broadcast_queue` sətirlərinin bütün aktiv abunəçilər üçün
    düzgün yaradıldığı yoxlanıldı (həm birbaşa, həm admin panel formu ilə).
  - `cron/broadcast_worker.php` CLI-də işlədilib (mock `TelegramApi`) — sətirlərin
    `sent` statusuna keçdiyi, `broadcasts.sent_count`/`status=done` düzgün
    yeniləndiyi təsdiqləndi.
  - Sequence yaradılıb, tag əlavə ediləndə `subscriber_sequences` avtomatik yaradıldığı
    (`SequenceTrigger`) yoxlanıldı.
  - `cron/sequence_worker.php` CLI-də işlədilib — birinci mesaj göndərilib, ikinci mesaja
    keçid, son mesajdan sonra `status=completed` düzgün işlədiyi təsdiqləndi.

### Canlı serverdə (real Telegram botu, HTTPS, SSL) test edilməli hissələr

Aşağıdakılar bu konteynerdə (internetə çıxışı olmayan, real Telegram API-yə qoşula
bilməyən mühit) yoxlanıla bilmədi və **mütləq canlı serverdə** təsdiqlənməlidir:

1. **`.env`-də `APP_ENV=production` təyin edildikdən sonra** real Telegram tokeni ilə bot
   yaradılması — `getMe` və `setWebhook`-un real şəbəkə cavabları.
2. **Real Telegram istifadəçisi ilə mesajlaşma** — `/start`, düymə basma, mətn yazma,
   açar sözlərin webhook vasitəsilə real update-lərlə işləməsi (bu mühitdə yalnız saxta
   JSON payload-larla simulyasiya edilib).
3. **`webhook.php`-nin HTTPS və Telegram-ın `secret_token` header-i ilə real çağırışı**
   (Telegram yalnız etibarlı SSL sertifikatlı HTTPS ünvanlara qoşulur).
4. **`nginx -t`** — `deploy/bot.birlikde.biz.conf` faylının real Nginx quraşdırmasında
   sintaksis yoxlaması (bu konteynerdə Nginx quraşdırılmayıb).
5. **Certbot ilə SSL sertifikatının alınması** (`certbot --nginx -d bot.birlikde.biz`).
6. **Broadcast/Sequence-in real Telegram istifadəçilərinə mesaj göndərməsi** — cron
   worker-lər bu mühitdə yalnız mock (test rejimi) ilə işlədilib; real rate limit
   davranışı (saniyədə ~25 mesaj) canlı yükdə yoxlanılmalıdır.
7. **PWA "add to home screen" davranışı** və `sw.js` keşləməsinin real brauzerdə/HTTPS
   üzərində sınanması (localhost xaricində Service Worker HTTPS tələb edir).
8. **Fayl icazələri** — `storage/` qovluğunun `www-data` istifadəçisi tərəfindən yazıla
   bilən olması (`chmod`/`chown`) real serverdə təsdiqlənməlidir.

## Yekun təhvil yoxlama siyahısı

- [x] Cədvəllər xətasız import olunur (MariaDB 10.11 ilə test edildi)
- [x] Core sinifləri işləyir (DB qoşulması test edilib)
- [x] FlowEngine: start/callback/input/keyword/goto axınları simulyasiyada işləyir
- [x] webhook.php həmişə 200 qaytarır, secret yoxlaması var
- [x] Admin panel: login, bot/node/düymə/subscriber/keyword idarəsi işləyir
- [x] Owner izolyasiyası + XSS escape + CSRF + prepared statements
- [x] Broadcast + sequence + cron worker-lər (mock test edilib)
- [x] PWA manifest, Nginx conf, README hazır
- [x] README-də canlı server test addımları aydın yazılıb
