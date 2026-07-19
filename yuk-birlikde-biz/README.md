# Yük.Birlikdə.biz

Müştəriləri və yükdaşıma sürücülərini birləşdirən rəqəmsal elan və təklif platforması (PWA). Platforma yük daşımır, qiymət müəyyən etmir, komissiya hesablamır və tərəflər arasındakı razılaşmanın iştirakçısı deyil — bax "Qızıl Qayda", tam layihə sənədi.

**Stek:** Native PHP 8.3 · MySQL 8.0 · Nginx · Vanilla JavaScript (SPA-shell, build addımı yoxdur) · SSE · Web Push · Payriff. Framework, Composer və build tool yoxdur.

## Quraşdırma (lokal inkişaf)

```bash
cp .env.example .env   # DB məlumatlarını doldurun
mysql -u root -p -e "CREATE DATABASE yuk_birlikde CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
# --default-character-set=utf8mb4 vacibdir — olmasa Azərbaycan hərfləri mysql
# client-in defolt latin1 kodlaşdırması ilə import zamanı korlanır.
for f in migrations/*.sql; do mysql --default-character-set=utf8mb4 -u root -p yuk_birlikde < "$f"; done

cd public
# PHP_CLI_SERVER_WORKERS vacibdir — olmasa uzunömürlü SSE bağlantıları (/sse/stream.php)
# builtin server-in tək worker-ini tutub qalan bütün sorğuları bloklayır.
PHP_CLI_SERVER_WORKERS=16 php -S 0.0.0.0:8000 router-dev.php
```

Production-da `nginx.conf.example` faylındakı rewrite qaydaları istifadə olunur (pretty API URL-ləri, `/uploads/` statik xidməti, SPA fallback).

Cron (production crontab):

```
*/5 * * * * php /var/www/yuk-birlikde-biz/app/cron/expire_orders.php
* * * * *   php /var/www/yuk-birlikde-biz/app/cron/reminders.php
0 3 * * *   php /var/www/yuk-birlikde-biz/app/cron/cleanup_events.php
```

## Fayl strukturu

```
app/            Backend nüvə modulları (config, db, auth, csrf, settings, ratelimit, texts, image)
migrations/     Nömrələnmiş SQL miqrasiya faylları (bütün cədvəllər + seed data)
public/
  index.php     SPA-shell giriş nöqtəsi
  api/v1/       Endpoint faylları
  assets/       CSS token/komponentlər, JS router/api/sse/store, ekranlar
storage/        uploads/, logs/, backups/ (git-ə düşmür)
```

## Faza vəziyyəti (Hissə 13 — İcra Planı)

- [x] **Faza 1 — Təməl**: bütün DB miqrasiyaları · settings modulu · SPA shell + router + API/SSE client skeletləri · dizayn tokenləri · komponent kitabxanası (Hissə 2.5) · Splash → Telefon → check-phone → PIN/Qeydiyyat vahid axını (operator sheet, PinPad, rol seçimi, bütün addımlar) · sessiya sistemi · CMS səhifələri + razılıq.
- [x] **Faza 2 — Elan dövriyyəsi**: 6 addımlıq elan yaratma + şəkil yükləmə + icmal · YK nömrələmə + slug · statuslar + expire cron · sürücü lenti (adi yüklənmə, scope/filtr) · elan detalları (hər iki baxış) · təklif sheet-i + dəyiş/geri çək · Təkliflərim ekranı · seçim (atomik) → nömrə açılışı → Danışıq Gedir → Bağla/Ləğv et → yenidən açılma → imtina · qiymətləndirmə · Elanlarım tabları · Bildirişlər ekranı · Profil (hər iki rol, bildiriş ayarları, şikayət, hesab silmə).
- [x] **Faza 3 — Real-time (SSE)**: events cədvəli + publish helper + `/sse/stream.php` (kanal icazələri, Last-Event-ID catch-up, 25s heartbeat) · lentdə slide-down/collapse + "N yeni elan" düyməsi · müştəridə canlı təkliflər (listing:{id}) · reconnect + visibility sync (iOS arxa fon) · bağlantı statusu zolaqları · xatırlatma cron-u (15/30/60/1440 dəq, settings-dən) · events təmizləmə cron-u.
- [ ] Faza 4 — PWA + Push
- [ ] Faza 5 — Abunə
- [ ] Faza 6 — Admin panel
- [ ] Faza 7 — Paylaşım + Cilalama

## Qızıl Qayda

Platforma yalnız müştərilər və yükdaşıma sürücüləri arasında əlaqə yaradan rəqəmsal vasitəçidir. Qiymət hesablama, təklif sıralama və "tövsiyə" tipli funksiyalar qəti qadağandır — seçim hüququ tamamilə müştəriyə məxsusdur.
