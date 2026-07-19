import { registerRoute, init, start, refresh } from './router.js';
import { api } from './api.js';
import { setState } from './store.js';
import { bumpSessionCount } from './install-prompt.js';

registerRoute('/', () => import('./screens/splash.js'));
registerRoute('/telefon', () => import('./screens/phone.js'));
registerRoute('/pin', () => import('./screens/pin.js'));
registerRoute('/qeydiyyat', () => import('./screens/register.js'));
registerRoute('/ana-sehife', () => import('./screens/home.js'));
registerRoute('/elan/yeni', () => import('./screens/order-create.js'));
registerRoute('/elan/:id', () => import('./screens/order-detail.js'));

function setupOfflineBanner() {
  const banner = document.getElementById('offline-banner');

  function render(status) {
    if (status === 'offline') {
      banner.innerHTML = '<div class="conn-banner conn-banner-warning">İnternet bağlantısı yoxdur</div>';
    } else if (status === 'reconnected') {
      banner.innerHTML = '<div class="conn-banner conn-banner-success">Bağlantı bərpa olundu</div>';
      setTimeout(() => { banner.innerHTML = ''; }, 2000);
    } else {
      banner.innerHTML = '';
    }
  }

  window.addEventListener('offline', () => render('offline'));
  window.addEventListener('online', () => { render('reconnected'); refresh(); });
  if (!navigator.onLine) {
    render('offline');
  }
}

function setupServiceWorker() {
  if (!('serviceWorker' in navigator)) {
    return;
  }

  navigator.serviceWorker.register('/sw.js').then((registration) => {
    registration.addEventListener('updatefound', () => {
      const installing = registration.installing;
      if (!installing) return;

      installing.addEventListener('statechange', () => {
        // navigator.serviceWorker.controller yalnız artıq idarə edən bir SW
        // varsa mövcuddur — yəni bu, ilk quraşdırma deyil, həqiqi yeniləmədir.
        if (installing.state === 'installed' && navigator.serviceWorker.controller) {
          showUpdateToast(registration);
        }
      });
    });
  }).catch(() => {
    // SW dəstəklənmirsə/qeydiyyat uğursuzsa tətbiq adi rejimdə işləməyə davam edir.
  });

  let reloaded = false;
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    if (reloaded) return;
    reloaded = true;
    location.reload();
  });
}

function showUpdateToast(registration) {
  const el = document.createElement('div');
  el.className = 'update-toast';
  el.innerHTML = `<span>Yeni versiya hazırdır</span><button type="button">Yenilə</button>`;
  el.querySelector('button').addEventListener('click', () => {
    registration.waiting?.postMessage({ type: 'SKIP_WAITING' });
    el.remove();
  });
  document.body.appendChild(el);
}

async function boot() {
  const root = document.getElementById('app');
  init(root);
  setupOfflineBanner();
  setupServiceWorker();
  bumpSessionCount();

  // Sessiya vəziyyəti router başlamazdan əvvəl bir dəfə hidratasiya olunur —
  // əks halda tam səhifə yenilənməsində (reload) hər hansı ekran istifadəçinin
  // rolunu bilmədən (customer/driver) mount olunardı.
  const data = await api.get('/auth/me').catch(() => ({ authenticated: false }));
  setState({ authenticated: data.authenticated, user: data.user ?? null });

  start();
}

document.addEventListener('DOMContentLoaded', boot);
