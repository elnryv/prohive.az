import { api } from './api.js';
import { createBannerSlot } from './components/banner.js';

// 4 yerdə banner render (Hissə 10.9): home_top, feed, profile, subscription.
// SSE `banner.updated` hadisəsi (system kanalı, home.js-də dinlənilir) bu
// modulun ybb:bannersUpdated custom event-ini eşitməsi ilə canlı yenilənir.
export function mountBanners(container, placement) {
  let cancelled = false;

  async function load() {
    if (cancelled) return;
    try {
      const { banners } = await api.get(`/banners?placement=${encodeURIComponent(placement)}`);
      if (cancelled) return;
      container.innerHTML = '';
      banners.forEach((banner) => {
        const el = createBannerSlot(banner, {
          onClick: (b) => {
            api.post(`/banners/${b.id}/click`).catch(() => {});
            if (b.link) window.open(b.link, '_blank', 'noopener');
          },
        });
        container.appendChild(el);
      });
    } catch {
      // sakitcə keç — banner kritik deyil
    }
  }

  function onUpdated() { load(); }
  window.addEventListener('ybb:bannersUpdated', onUpdated);
  load();

  return () => {
    cancelled = true;
    window.removeEventListener('ybb:bannersUpdated', onUpdated);
  };
}
