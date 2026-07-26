const ESCAPE_MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

// İstifadəçi tərəfindən daxil edilə bilən hər hansı mətn innerHTML-ə yerləşdirilməzdən
// əvvəl bu funksiyadan keçməlidir (stored-XSS qarşısı — Hissə 11.1).
export function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ESCAPE_MAP[ch]);
}

const MONTHS = ['yan', 'fev', 'mar', 'apr', 'may', 'iyn', 'iyl', 'avq', 'sen', 'okt', 'noy', 'dek'];

// Backend-in "YYYY-MM-DD HH:MM:SS" formatını insan-oxunaqlı "23 iyl, 14:30" şəklinə salır.
export function formatDateTime(value) {
  if (!value) return '';
  const d = new Date(String(value).replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return String(value);
  const hh = String(d.getHours()).padStart(2, '0');
  const mm = String(d.getMinutes()).padStart(2, '0');
  return `${d.getDate()} ${MONTHS[d.getMonth()]}, ${hh}:${mm}`;
}
