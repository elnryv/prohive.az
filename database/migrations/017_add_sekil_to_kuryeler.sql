-- Kuryer/yükdaşıma profil şəkli — bax "Sifarişlərim/Profil" bölmə tələbi.
-- Şəkil özü storage/kurye-sekiller/-da saxlanılır, burada yalnız fayl adı.
ALTER TABLE kuryeler ADD COLUMN sekil VARCHAR(64) NULL AFTER neqliyyat;
