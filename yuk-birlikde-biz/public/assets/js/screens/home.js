import { api } from '../api.js';
import { getState, setState } from '../store.js';
import { navigate } from '../router.js';

export async function mount(root) {
  const { user } = getState();

  root.classList.add('home-screen');
  root.innerHTML = `
    <div class="home-top-row">
      <h3 class="h3">Salam, ${user?.first_name ?? ''}</h3>
      <button type="button" class="chip" id="logout-btn">Çıxış</button>
    </div>
    <p class="small-text" style="color:var(--text-muted)">
      ${user?.role === 'driver' ? 'Real-time elan lenti Faza 2-də qurulacaq.' : 'Elan yaratma axını Faza 2-də qurulacaq.'}
    </p>
  `;

  root.querySelector('#logout-btn').addEventListener('click', async () => {
    await api.post('/auth/logout').catch(() => {});
    setState({ authenticated: false, user: null, pendingPhone: null });
    navigate('/', { replace: true });
  });

  return () => root.classList.remove('home-screen');
}
