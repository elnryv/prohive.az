import { getState } from '../store.js';
import { navigate } from '../router.js';

export async function mount(root) {
  root.classList.add('splash-screen');
  root.innerHTML = `
    <div class="splash-logo">
      <svg viewBox="0 0 512 512" role="img" aria-label="Yük.Birlikdə.biz">
        <defs>
          <linearGradient id="splash-mark-bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#4C8DFB"/>
            <stop offset="55%" stop-color="#2563EB"/>
            <stop offset="100%" stop-color="#15348F"/>
          </linearGradient>
          <radialGradient id="splash-mark-sheen" cx="28%" cy="18%" r="60%">
            <stop offset="0%" stop-color="#FFFFFF" stop-opacity="0.22"/>
            <stop offset="100%" stop-color="#FFFFFF" stop-opacity="0"/>
          </radialGradient>
          <radialGradient id="splash-mark-vignette" cx="78%" cy="88%" r="65%">
            <stop offset="0%" stop-color="#0B1B4D" stop-opacity="0.28"/>
            <stop offset="100%" stop-color="#0B1B4D" stop-opacity="0"/>
          </radialGradient>
          <filter id="splash-mark-shadow" x="-40%" y="-30%" width="180%" height="180%">
            <feDropShadow dx="0" dy="10" stdDeviation="14" flood-color="#0A1E4A" flood-opacity="0.32"/>
          </filter>
        </defs>
        <rect width="512" height="512" fill="url(#splash-mark-bg)"/>
        <rect width="512" height="512" fill="url(#splash-mark-sheen)"/>
        <rect width="512" height="512" fill="url(#splash-mark-vignette)"/>
        <g filter="url(#splash-mark-shadow)">
          <polygon points="256,82 410,169 256,256 102,169" fill="#FFFFFF"/>
          <polygon points="102,169 256,256 256,430 102,343" fill="#CFDFFC"/>
          <polygon points="410,169 410,343 256,430 256,256" fill="#8FAEF2"/>
        </g>
      </svg>
    </div>
    <div class="splash-title">Yük.Birlikdə.biz</div>
    <div class="splash-tagline">Yükünüzü etibarlı əllərə buraxın</div>
  `;

  // Sessiya vəziyyəti app.js boot() tərəfindən artıq hidratasiya olunub;
  // bura yalnız 1.2s brendli keçid üçün minimum gözləmə tətbiq edir.
  setTimeout(() => {
    navigate(getState().authenticated ? '/ana-sehife' : '/telefon', { replace: true });
  }, 1200);

  return () => root.classList.remove('splash-screen');
}
