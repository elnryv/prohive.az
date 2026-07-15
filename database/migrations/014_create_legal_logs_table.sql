-- Bax CLAUDE.md bölmə 4.9 / orijinal sənəd bölmə 4.9
-- Append-only audit jurnalı: UPDATE/DELETE tətbiq səviyyəsində qadağandır (DB DELETE/UPDATE
-- imtiyazı app DB istifadəçisinə verilməməlidir — bax deploy/VPS_QURULUM.md).
CREATE TABLE IF NOT EXISTS legal_logs (
  id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  actor_id    BIGINT UNSIGNED NULL,
  sifaris_id  BIGINT UNSIGNED NULL,
  hadise      VARCHAR(60) NOT NULL,
  detal_json  JSON NULL,
  ip_adres    VARBINARY(16),
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
