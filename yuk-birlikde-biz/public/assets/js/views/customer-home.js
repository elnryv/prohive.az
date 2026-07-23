import { api } from '../api.js';
import { getState } from '../store.js';
import { navigate } from '../router.js';
import { createSkeletonList } from '../components/skeleton.js';
import { createEmptyState } from '../components/empty.js';
import { createListingCard } from '../components/card.js';
import { esc } from '../utils.js';
import { mountBanners } from '../banner-loader.js';

export async function render(root) {
  const { user } = getState();

  root.innerHTML = `
    <div class="home-hero">
      <h3 class="h3 home-hero-greeting">Salam, ${esc(user?.first_name)}</h3>
      <div class="small-text home-hero-sub">Yükünüzü indi elan edin, sürücülər dəqiqələr içində təklif göndərsin.</div>
    </div>
    <div id="banner-slot"></div>
    <button type="button" class="btn btn-primary" id="create-btn">+ Yeni Elan Yarat</button>
    <div id="negotiating-section" style="margin-top:24px;"></div>
    <div style="margin-top:24px;display:flex;align-items:center;justify-content:space-between;">
      <h3 class="h3">Aktiv elanlarım</h3>
      <button type="button" class="chip" id="see-all-btn" style="border:none;color:var(--primary);background:none;">Hamısına bax</button>
    </div>
    <div id="active-list" style="margin-top:12px;"></div>
  `;

  root.querySelector('#create-btn').addEventListener('click', () => navigate('/elan/yeni'));
  root.querySelector('#see-all-btn').addEventListener('click', () => window.dispatchEvent(new CustomEvent('ybb:tab', { detail: 1 })));

  const cleanupBanners = mountBanners(root.querySelector('#banner-slot'), 'home_top');

  const activeList = root.querySelector('#active-list');
  activeList.appendChild(createSkeletonList(3));

  const negotiatingSection = root.querySelector('#negotiating-section');

  try {
    const { orders } = await api.get('/orders/my');

    const negotiating = orders.filter((o) => o.status === 'negotiating');
    negotiatingSection.innerHTML = '';
    if (negotiating.length > 0) {
      const heading = document.createElement('h3');
      heading.className = 'h3';
      heading.textContent = 'Danışıq gedən';
      negotiatingSection.appendChild(heading);
      negotiating.forEach((order) => {
        const card = createListingCard({
          status: 'negotiating',
          cargo_type: order.cargo_type_name,
          from: order.from_city,
          to: order.to_city,
          date_time: order.date_time,
          thumb_url: order.thumb_url,
          note_preview: 'Sürücü seçilib — əlaqə saxlayın',
        });
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => navigate(`/elan/${order.id}`));
        negotiatingSection.appendChild(card);
      });
    }

    const active = orders.filter((o) => ['active', 'waiting'].includes(o.status)).slice(0, 3);
    activeList.innerHTML = '';

    if (active.length === 0) {
      activeList.appendChild(createEmptyState({
        title: 'Hələ elanınız yoxdur',
        description: 'İlk elanınızı yaradın — sürücülər dəqiqələr içində təklif göndərəcək.',
        ctaLabel: 'Elan yarat',
        onCta: () => navigate('/elan/yeni'),
      }));
      return cleanupBanners;
    }

    active.forEach((order) => {
      const card = createListingCard({
        status: order.status,
        cargo_type: order.cargo_type_name,
        from: order.from_city,
        to: order.to_city,
        date_time: order.date_time,
        thumb_url: order.thumb_url,
        note_preview: order.offer_count > 0 ? `${order.offer_count} təklif` : 'Hələ təklif yoxdur',
      });
      card.style.cursor = 'pointer';
      card.addEventListener('click', () => navigate(`/elan/${order.id}`));
      activeList.appendChild(card);
    });
  } catch {
    activeList.innerHTML = '';
    activeList.appendChild(createEmptyState({ title: 'Xəta baş verdi', description: 'Yenidən cəhd edin.' }));
  }

  return cleanupBanners;
}
