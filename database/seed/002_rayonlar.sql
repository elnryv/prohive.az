-- Bax CLAUDE.md bölmə 5.1.1 / orijinal sənəd bölmə 5.1.1
-- QEYD (SEED-5.1.1): Sumqayıtın rəsmi inzibati ərazi bölgüsü canlıya keçməzdən əvvəl
-- Sumqayıt İcra Hakimiyyətinin rəsmi siyahısı ilə yoxlanmalıdır. Admin panelindən (8.7)
-- əlavə/dəyişiklik mümkündür.

-- Bakı — 12 inzibati rayon
INSERT INTO rayonlar (sehir_id, ad_az, aktiv)
SELECT id, ad, 1 FROM sehirler
JOIN (
  SELECT 'Binəqədi' AS ad UNION ALL SELECT 'Xətai' UNION ALL SELECT 'Xəzər' UNION ALL
  SELECT 'Nərimanov' UNION ALL SELECT 'Nəsimi' UNION ALL SELECT 'Nizami' UNION ALL
  SELECT 'Pirallahı' UNION ALL SELECT 'Sabunçu' UNION ALL SELECT 'Səbail' UNION ALL
  SELECT 'Suraxanı' UNION ALL SELECT 'Yasamal' UNION ALL SELECT 'Qaradağ'
) AS bakı_rayonlari
WHERE sehirler.ad_az = 'Bakı';

-- Sumqayıt — mikrorayonlar (1-ci - 18-ci), qəsəbələr, yaşayış massivləri
INSERT INTO rayonlar (sehir_id, ad_az, aktiv)
SELECT id, ad, 1 FROM sehirler
JOIN (
  SELECT '1-ci mikrorayon' AS ad UNION ALL SELECT '2-ci mikrorayon' UNION ALL
  SELECT '3-cü mikrorayon' UNION ALL SELECT '4-cü mikrorayon' UNION ALL
  SELECT '5-ci mikrorayon' UNION ALL SELECT '6-cı mikrorayon' UNION ALL
  SELECT '7-ci mikrorayon' UNION ALL SELECT '8-ci mikrorayon' UNION ALL
  SELECT '9-cu mikrorayon' UNION ALL SELECT '10-cu mikrorayon' UNION ALL
  SELECT '11-ci mikrorayon' UNION ALL SELECT '12-ci mikrorayon' UNION ALL
  SELECT '13-cü mikrorayon' UNION ALL SELECT '14-cü mikrorayon' UNION ALL
  SELECT '15-ci mikrorayon' UNION ALL SELECT '16-cı mikrorayon' UNION ALL
  SELECT '17-ci mikrorayon' UNION ALL SELECT '18-ci mikrorayon' UNION ALL
  SELECT 'Corat qəsəbəsi' UNION ALL SELECT 'H.Z.Tağıyev qəsəbəsi' UNION ALL
  SELECT 'Kotec qəsəbəsi' UNION ALL SELECT 'İnşaatçılar qəsəbəsi' UNION ALL
  SELECT 'Yaşıldərə massivi' UNION ALL SELECT 'Qurd dərəsi massivi'
) AS sumqayit_erazileri
WHERE sehirler.ad_az = 'Sumqayıt';
