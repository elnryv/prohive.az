import { api, ApiError } from '../api.js';
import { navigate } from '../router.js';
import { createSkeletonList } from '../components/skeleton.js';
import { createEmptyState } from '../components/empty.js';
import { showToast } from '../components/toast.js';
import { openOfferSheet } from '../components/offer-sheet.js';
import { esc } from '../utils.js';

export async function render(root) {
  root.innerHTML = `
    <h2 class="h2" style="margin-bottom:16px;">Təkliflərim</h2>
    <div id="scope-tabs" style="margin-bottom:16px;"></div>
    <div id="list"></div>
  `;

  const tabsEl = root.querySelector('#scope-tabs');
  const listEl = root.querySelector('#list');
  let tab = 'active';

  const tabButtons = ['Aktiv', 'Seçilənlər'].map((label, i) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'chip' + (i === 0 ? ' active' : '');
    btn.textContent = label;
    btn.addEventListener('click', () => {
      tab = i === 0 ? 'active' : 'selected';
      tabButtons.forEach((b, idx) => b.classList.toggle('active', idx === i));
      load();
    });
    tabsEl.appendChild(btn);
    return btn;
  });

  async function load() {
    listEl.innerHTML = '';
    listEl.appendChild(createSkeletonList(3));

    try {
      const { offers } = await api.get(`/offers/my?tab=${tab}`);
      listEl.innerHTML = '';

      if (offers.length === 0) {
        listEl.appendChild(createEmptyState({
          title: tab === 'active' ? 'Aktiv təklifiniz yoxdur' : 'Qazandığınız sifariş yoxdur',
          description: '',
          ctaLabel: tab === 'active' ? 'Lentə keç' : undefined,
          onCta: tab === 'active' ? () => window.dispatchEvent(new CustomEvent('ybb:tab', { detail: 0 })) : undefined,
        }));
        return;
      }

      offers.forEach((offer) => listEl.appendChild(renderOfferCard(offer, tab, load)));
    } catch {
      listEl.innerHTML = '';
      listEl.appendChild(createEmptyState({ title: 'Xəta baş verdi', description: 'Yenidən cəhd edin.' }));
    }
  }

  function renderOfferCard(offer, tab, reload) {
    const card = document.createElement('div');
    card.className = 'listing-card' + (offer.order_status !== 'negotiating' && offer.order_status !== 'active' && offer.order_status !== 'waiting' ? ' status-closed' : '');
    card.innerHTML = `
      <div class="card-top-row">
        <span class="body-text" style="font-weight:600;">${esc(offer.number)}</span>
        <span class="caption-text">${esc(offer.date_time)}</span>
      </div>
      <div class="card-route">${esc(offer.from_city)} → ${esc(offer.to_city)}</div>
      <div class="h3 tabular-nums" style="margin-top:8px;">${esc(offer.price)} AZN</div>
      ${tab === 'selected' ? `
        <div class="small-text" style="margin-top:8px;">${esc(offer.customer_name)} · ${esc(offer.customer_phone)}</div>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <a class="btn btn-secondary" style="height:40px;text-decoration:none;" href="tel:${encodeURIComponent(offer.customer_phone ?? '')}">Zəng</a>
          <a class="btn btn-secondary" style="height:40px;text-decoration:none;" target="_blank"
             href="https://wa.me/${encodeURIComponent((offer.customer_phone ?? '').replace(/\D/g, ''))}">WhatsApp</a>
        </div>
        ${offer.order_status === 'negotiating' ? '<button type="button" class="btn btn-danger" style="height:40px;margin-top:8px;" data-action="decline">İmtina et</button>' : ''}
      ` : `
        <div class="small-text" style="color:var(--text-muted);margin-top:4px;">Gözlənilir</div>
        <div style="display:flex;gap:8px;margin-top:12px;">
          <button type="button" class="btn btn-secondary" style="height:40px;" data-action="edit">Dəyiş</button>
          <button type="button" class="btn btn-danger" style="height:40px;" data-action="withdraw">Geri çək</button>
        </div>
      `}
    `;

    card.addEventListener('click', (e) => {
      if (e.target.closest('[data-action]') || e.target.closest('a')) return;
      navigate(`/elan/${offer.order_id}`);
    });

    card.querySelector('[data-action="edit"]')?.addEventListener('click', () => {
      openOfferSheet(offer.order_id, { existingOffer: offer, onSubmitted: reload });
    });

    card.querySelector('[data-action="withdraw"]')?.addEventListener('click', async () => {
      try {
        await api.post(`/offers/${offer.id}/withdraw`);
        showToast('Təklifiniz geri çəkildi.');
        reload();
      } catch (e) {
        showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
      }
    });

    card.querySelector('[data-action="decline"]')?.addEventListener('click', async () => {
      try {
        await api.post(`/offers/${offer.id}/decline`);
        showToast('İmtina edildi.');
        reload();
      } catch (e) {
        showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
      }
    });

    return card;
  }

  await load();
}
