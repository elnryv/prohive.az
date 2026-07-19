import { registerRoute, init, start } from './router.js';
import { api } from './api.js';
import { setState } from './store.js';

registerRoute('/', () => import('./screens/splash.js'));
registerRoute('/telefon', () => import('./screens/phone.js'));
registerRoute('/pin', () => import('./screens/pin.js'));
registerRoute('/qeydiyyat', () => import('./screens/register.js'));
registerRoute('/ana-sehife', () => import('./screens/home.js'));
registerRoute('/elan/yeni', () => import('./screens/order-create.js'));
registerRoute('/elan/:id', () => import('./screens/order-detail.js'));

async function boot() {
  const root = document.getElementById('app');
  init(root);

  // Sessiya vəziyyəti router başlamazdan əvvəl bir dəfə hidratasiya olunur —
  // əks halda tam səhifə yenilənməsində (reload) hər hansı ekran istifadəçinin
  // rolunu bilmədən (customer/driver) mount olunardı.
  const data = await api.get('/auth/me').catch(() => ({ authenticated: false }));
  setState({ authenticated: data.authenticated, user: data.user ?? null });

  start();
}

document.addEventListener('DOMContentLoaded', boot);
