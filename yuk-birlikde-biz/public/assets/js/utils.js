const ESCAPE_MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };

// İstifadəçi tərəfindən daxil edilə bilən hər hansı mətn innerHTML-ə yerləşdirilməzdən
// əvvəl bu funksiyadan keçməlidir (stored-XSS qarşısı — Hissə 11.1).
export function esc(value) {
  return String(value ?? '').replace(/[&<>"']/g, (ch) => ESCAPE_MAP[ch]);
}
