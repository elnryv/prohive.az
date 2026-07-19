import { api } from '../api.js';
import { getState } from '../store.js';
import { navigate } from '../router.js';
import { createSkeletonList } from '../components/skeleton.js';
import { createEmptyState } from '../components/empty.js';
import { createListingCard } from '../components/card.js';
import { createSegmentedTabs } from '../components/chip.js';
import { openOfferSheet } from '../components/offer-sheet.js';
import { esc } from '../utils.js';
import { connectSSE } from '../sse.js';

export async function render(root) {
  const { user } = getState();

  root.innerHTML = `
    <div class="home-top-row">
      <h3 class="h3">Salam, ${esc(user?.first_name)}</h3>
    </div>
    <div id="conn-banner"></div>
    <div id="scope-tabs" style="margin:12px 0;"></div>
    <div class="chip-row" id="filter-chips" style="margin-bottom:16px;"></div>
    <button type="button" class="new-listings-btn" id="new-listings-btn" style="display:none;"></button>
    <div id="feed-list"></div>
  `;

  const filters = { scope: 'baku' };
  const listEl = root.querySelector('#feed-list');
  const bannerEl = root.querySelector('#conn-banner');
  const newBtn = root.querySelector('#new-listings-btn');
  const screenEl = root.closest('.screen');

  let newCount = 0;

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

  function buildCard(listing) {
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
    return card;
  }

  async function loadFeed() {
    listEl.innerHTML = '';
    listEl.appendChild(createSkeletonList(4));
    hideNewButton();

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

      listings.forEach((listing) => listEl.appendChild(buildCard(listing)));
    } catch {
      listEl.innerHTML = '';
      listEl.appendChild(createEmptyState({ title: 'Xəta baş verdi', description: 'Yenidən cəhd edin.' }));
    }
  }

  function showNewButton() {
    newBtn.textContent = `↑ ${newCount} yeni elan`;
    newBtn.style.display = 'block';
  }
  function hideNewButton() {
    newCount = 0;
    newBtn.style.display = 'none';
  }
  newBtn.addEventListener('click', () => {
    screenEl?.scrollTo({ top: 0, behavior: 'smooth' });
    loadFeed();
  });

  function insertListingTop(payload) {
    if (payload.scope && payload.scope !== filters.scope) {
      return;
    }
    listEl.querySelector('.empty-state')?.remove();

    const existing = listEl.querySelector(`[data-id="${payload.id}"]`);
    existing?.remove();

    const card = buildCard(payload);
    card.style.animation = 'none';
    void card.offsetWidth;
    card.style.animation = 'slide-down 320ms ease-out both';
    card.classList.add('card-highlight');
    listEl.prepend(card);

    if ((screenEl?.scrollTop ?? 0) > 40) {
      newCount++;
      showNewButton();
    }
  }

  function removeListing(id) {
    const card = listEl.querySelector(`[data-id="${id}"]`);
    if (!card) return;
    card.style.animation = 'collapse-fade 260ms ease-in both';
    setTimeout(() => {
      card.remove();
      if (listEl.children.length === 0) {
        listEl.appendChild(createEmptyState({
          title: 'Hazırda aktiv elan yoxdur',
          description: 'Yeni elan gələn kimi burada görünəcək.',
        }));
      }
    }, 260);
  }

  function setConnBanner(status) {
    if (status === 'reconnecting') {
      bannerEl.innerHTML = `<div class="conn-banner conn-banner-warning">Yenidən qoşulur…</div>`;
    } else if (status === 'reconnected') {
      bannerEl.innerHTML = `<div class="conn-banner conn-banner-success">Bağlantı bərpa olundu</div>`;
      setTimeout(() => { bannerEl.innerHTML = ''; }, 2000);
    } else {
      bannerEl.innerHTML = '';
    }
  }

  const sse = connectSSE(['feed'], {
    'listing.new': insertListingTop,
    'listing.reopened': insertListingTop,
    'listing.removed': (payload) => removeListing(payload.id),
    'listing.expired': (payload) => removeListing(payload.id),
  }, { onStatus: setConnBanner });

  await loadFeed();

  return () => sse.close();
}
