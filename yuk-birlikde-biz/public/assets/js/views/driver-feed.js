import { api } from '../api.js';
import { getState } from '../store.js';
import { navigate } from '../router.js';
import { createSkeletonList } from '../components/skeleton.js';
import { createEmptyState } from '../components/empty.js';
import { createListingCard } from '../components/card.js';
import { createSegmentedTabs } from '../components/chip.js';
import { openOfferSheet } from '../components/offer-sheet.js';
import { esc } from '../utils.js';

export async function render(root) {
  const { user } = getState();

  root.innerHTML = `
    <div class="home-top-row">
      <h3 class="h3">Salam, ${esc(user?.first_name)}</h3>
    </div>
    <div id="scope-tabs" style="margin:12px 0;"></div>
    <div class="chip-row" id="filter-chips" style="margin-bottom:16px;"></div>
    <div id="feed-list"></div>
  `;

  const filters = { scope: 'baku' };
  const listEl = root.querySelector('#feed-list');

  const scopeTabs = createSegmentedTabs(['Bakı daxili', 'Bölgələrarası'], {
    onChange: (index) => {
      filters.scope = index === 0 ? 'baku' : 'interregional';
      loadFeed();
    },
  });
  root.querySelector('#scope-tabs').appendChild(scopeTabs.el);

  const filterChipsEl = root.querySelector('#filter-chips');
  ['Şəhər', 'Tarix', 'Yük növü'].forEach((label) => {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'chip';
    chip.textContent = label;
    filterChipsEl.appendChild(chip);
  });

  async function loadFeed() {
    listEl.innerHTML = '';
    listEl.appendChild(createSkeletonList(4));

    const params = new URLSearchParams();
    if (filters.scope) params.set('scope', filters.scope);

    try {
      const { listings } = await api.get(`/feed?${params.toString()}`);
      listEl.innerHTML = '';

      if (listings.length === 0) {
        listEl.appendChild(createEmptyState({
          title: 'Hazırda aktiv elan yoxdur',
          description: 'Yeni elan gələn kimi burada görünəcək.',
        }));
        return;
      }

      listings.forEach((listing) => {
        const card = createListingCard(listing);
        const actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:8px;margin-top:12px;';
        actions.innerHTML = `
          <button type="button" class="btn btn-secondary" style="height:40px;" data-action="detail">Ətraflı</button>
          <button type="button" class="btn btn-primary" style="height:40px;" data-action="offer">Təklif ver</button>
        `;
        card.appendChild(actions);
        actions.querySelector('[data-action="detail"]').addEventListener('click', () => navigate(`/elan/${listing.id}`));
        actions.querySelector('[data-action="offer"]').addEventListener('click', () => {
          openOfferSheet(listing.id, { onSubmitted: loadFeed });
        });
        listEl.appendChild(card);
      });
    } catch {
      listEl.innerHTML = '';
      listEl.appendChild(createEmptyState({ title: 'Xəta baş verdi', description: 'Yenidən cəhd edin.' }));
    }
  }

  await loadFeed();
}
