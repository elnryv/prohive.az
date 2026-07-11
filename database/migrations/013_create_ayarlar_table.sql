-- Bax CLAUDE.md bölmə 4.8 / orijinal sənəd bölmə 4.8 (qlobal toggle, məs. abune_rejimi)
CREATE TABLE IF NOT EXISTS ayarlar (
  ad     VARCHAR(60) PRIMARY KEY,
  deyer  VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
