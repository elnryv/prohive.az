# BİRLİKDƏ — Progress Log

Cari status izləmə jurnalı. Hər faza bitəndə burada yenilənir.

## Status

- **Cari faza:** Yaddaş sistemi quruldu — Faza 0 başlamağa hazır
- **Son əmr gözlənilir:** "Növbəti fazaya keç" (Faza 0 üçün)

## Faza Cədvəli

| Faza | Ad | Status | Tarix |
|---|---|---|---|
| — | Yaddaş sistemi (CLAUDE.md/PROGRESS.md) qurulması | ✅ Tamamlandı | 2026-07-11 |
| 0 | Təməl (VPS, qovluq, Core, config, error handler) | ⏳ Gözləyir | — |
| 1 | Verilənlər Bazası (migrations, seed) | ⏳ Gözləyir | — |
| 2 | Autentifikasiya (qeydiyyat/giriş/sözləşmə/middleware) | ⏳ Gözləyir | — |
| 3 | Sifariş və SSE (race qoruması, WhatsApp) | ⏳ Gözləyir | — |
| 4 | Admin Paneli | ⏳ Gözləyir | — |
| 5 | Abunə və Ödəniş (adapter, webhook) | ⏳ Gözləyir | — |
| 6 | PWA və Cilalama | ⏳ Gözləyir | — |
| 7 | Hüquqi (sonra) | ⏳ Gözləyir | — |

## Jurnalı

### 2026-07-11 — Yaddaş sistemi quruldu
- `Birlikde_Layihe_v2.docx` tam oxundu (11 bölmə + 2 əlavə).
- `CLAUDE.md` yaradıldı: layihə xülasəsi, texnologiya yığını, DB icmalı, əsas biznes
  qaydaları, təhlükəsizlik tələbləri, endpoint xəritəsi, faza planı, iş qaydaları.
- `PROGRESS.md` (bu fayl) yaradıldı.
- Hələ heç bir tətbiq kodu yazılmayıb. Növbəti addım: istifadəçi "Növbəti fazaya keç"
  deyəndə Faza 0-a başlanacaq.

## Qeydlər / Açıq Suallar (fazalar arası unudulmamalı)

- Hüquqi mətnlər (Müqavilə/Məxfilik) hələ yoxdur — Faza 7-yə saxlanılıb, infrastruktur
  (checkbox + `sozlesme_qebul`/`sozlesme_tarix` + `legal_logs`) Faza 2-də hazırlanmalıdır.
- Sumqayıt ərazi siyahısı seed məlumatı canlıya keçmədən əvvəl rəsmi mənbədən təsdiqlənməlidir.
- Ödəniş provayderi (Birbank/Payriff) seçimi Faza 5-də konkretləşdiriləcək — kod
  provayder-neytral interfeyslə yazılır.
