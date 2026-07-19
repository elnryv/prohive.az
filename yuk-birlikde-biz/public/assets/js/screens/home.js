import { getState } from '../store.js';
import { createBottomNav } from '../components/bottomnav.js';
import { api } from '../api.js';
import { navigate } from '../router.js';

const TABS = {
  customer: [
    { label: 'Ana səhifə', view: () => import('../views/customer-home.js') },
    { label: 'Elanlarım', view: () => import('../views/my-orders.js') },
    { label: 'Bildirişlər', view: () => import('../views/notifications-view.js') },
    { label: 'Profil', view: () => import('../views/profile-view.js') },
  ],
  driver: [
    { label: 'Lent', view: () => import('../views/driver-feed.js') },
    { label: 'Təkliflərim', view: () => import('../views/my-offers.js') },
    { label: 'Bildirişlər', view: () => import('../views/notifications-view.js') },
    { label: 'Profil', view: () => import('../views/profile-view.js') },
  ],
};

export async function mount(root) {
  const { user, authenticated } = getState();
  if (!authenticated) {
    navigate('/telefon', { replace: true });
    return () => {};
  }
  const tabs = TABS[user?.role ?? 'customer'];

  root.classList.add('home-screen');
  root.innerHTML = `<div id="tab-content"></div>`;
  const contentEl = root.querySelector('#tab-content');

  let activeIndex = 0;

  async function showTab(index) {
    activeIndex = index;
    bottomNav.setActive?.(index);
    contentEl.innerHTML = '';
    const mod = await tabs[index].view();
    await mod.render(contentEl);
    refreshBadge();
  }

  async function refreshBadge() {
    try {
      const { notifications } = await api.get('/notifications');
      const unread = notifications.filter((n) => !n.is_read).length;
      bottomNav.setBadge?.(2, unread > 0 ? String(unread) : null);
    } catch {
      // sakitcə keç — badge kritik deyil
    }
  }

  const bottomNav = createBottomNav(
    tabs.map((t) => ({ label: t.label })),
    { active: 0, onChange: (index) => showTab(index) }
  );
  root.appendChild(bottomNav.el);

  window.addEventListener('ybb:tab', (e) => showTab(e.detail));

  await showTab(0);

  return () => {
    root.classList.remove('home-screen');
  };
}
