import { api } from '../api.js';
import { navigate } from '../router.js';
import { createSkeletonList } from '../components/skeleton.js';
import { createEmptyState } from '../components/empty.js';
import { createListingCard } from '../components/card.js';

const TABS = [
  { label: 'Aktiv', statuses: 'active,waiting', empty: 'Hələ aktiv elanınız yoxdur.' },
  { label: 'Danışıq Gedir', statuses: 'negotiating', empty: 'Danışıq gedən elanınız yoxdur.' },
  { label: 'Bağlandı', statuses: 'closed', empty: 'Hələ bağlanmış sifarişiniz yoxdur.' },
  { label: 'Ləğv edildi', statuses: 'cancelled', empty: 'Ləğv edilmiş elanınız yoxdur.' },
  { label: 'Müddəti bitdi', statuses: 'expired', empty: 'Müddəti bitmiş elanınız yoxdur.' },
];

export async function render(root) {
  root.innerHTML = `
    <h2 class="h2" style="margin-bottom:16px;">Elanlarım</h2>
    <div class="chip-row" id="tabs"></div>
    <div id="list" style="margin-top:16px;"></div>
  `;

  const tabsEl = root.querySelector('#tabs');
  const listEl = root.querySelector('#list');
  let active = 0;

  const chips = TABS.map((tab, i) => {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'chip' + (i === 0 ? ' active' : '');
    chip.textContent = tab.label;
    chip.addEventListener('click', () => {
      active = i;
      chips.forEach((c, idx) => c.classList.toggle('active', idx === i));
      load();
    });
    tabsEl.appendChild(chip);
    return chip;
  });

  async function load() {
    listEl.innerHTML = '';
    listEl.appendChild(createSkeletonList(3));

    try {
      const tab = TABS[active];
      const { orders } = await api.get(`/orders/my?status=${tab.statuses}`);
      listEl.innerHTML = '';

      if (orders.length === 0) {
        listEl.appendChild(createEmptyState({ title: tab.empty, description: '' }));
        return;
      }

      orders.forEach((order) => {
        const card = createListingCard({
          status: order.status,
          cargo_type: order.cargo_type_name,
          from: order.from_city,
          to: order.to_city,
          date_time: order.date_time,
          note_preview: order.offer_count > 0 ? `${order.offer_count} təklif` : 'Hələ təklif yoxdur',
        });
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => navigate(`/elan/${order.id}`));
        listEl.appendChild(card);
      });
    } catch {
      listEl.innerHTML = '';
      listEl.appendChild(createEmptyState({ title: 'Xəta baş verdi', description: 'Yenidən cəhd edin.' }));
    }
  }

  await load();
}
