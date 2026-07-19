import { api } from '../api.js';
import { setState } from '../store.js';
import { navigate } from '../router.js';

export async function mount(root) {
  root.classList.add('splash-screen');
  root.innerHTML = `
    <div class="splash-logo"></div>
    <div class="splash-title">Yük.Birlikdə.biz</div>
    <div class="splash-tagline">Yükünüzü etibarlı əllərə buraxın</div>
  `;

  const start = Date.now();
  const data = await api.get('/auth/me').catch(() => ({ authenticated: false }));
  setState({ authenticated: data.authenticated, user: data.user ?? null });

  const elapsed = Date.now() - start;
  const wait = Math.max(0, 1200 - elapsed);

  setTimeout(() => {
    navigate(data.authenticated ? '/ana-sehife' : '/telefon', { replace: true });
  }, wait);

  return () => root.classList.remove('splash-screen');
}
