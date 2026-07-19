import { getState } from '../store.js';
import { navigate } from '../router.js';

export async function mount(root) {
  root.classList.add('splash-screen');
  root.innerHTML = `
    <div class="splash-logo"></div>
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
