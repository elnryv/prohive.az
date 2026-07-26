import { api, ApiError } from '../api.js';
import { getState } from '../store.js';
import { navigate } from '../router.js';
import { createAppBar } from '../components/appbar.js';
import { createOfferCard } from '../components/card.js';
import { createSkeletonList } from '../components/skeleton.js';
import { openSheet } from '../components/sheet.js';
import { showToast } from '../components/toast.js';
import { openOfferSheet } from '../components/offer-sheet.js';
import { ICONS } from '../components/icons.js';
import { esc, formatDateTime } from '../utils.js';
import { connectSSE } from '../sse.js';

const STATUS_LABELS = {
  active: 'Aktiv',
  waiting: 'Təklif Gözləyir',
  negotiating: 'Danışıq Gedir',
  closed: 'Bağlandı',
  cancelled: 'Ləğv Edildi',
  expired: 'Müddəti Bitdi',
};

function routeVisual(fromLabel, toLabel) {
  return `
    <div class="route-visual">
      <div class="route-visual-rail">
        <span class="route-dot"></span>
        <span class="route-connector"></span>
        <span class="route-dot route-dot-end"></span>
      </div>
      <div class="route-labels">
        <div>
          <div class="route-label-tag">Haradan</div>
          <div class="route-label-city">${esc(fromLabel)}</div>
        </div>
        <div>
          <div class="route-label-tag">Haraya</div>
          <div class="route-label-city">${esc(toLabel)}</div>
        </div>
      </div>
    </div>
  `;
}

function confirmSheet(message, onConfirm) {
  const content = document.createElement('div');
  content.innerHTML = `
    <p class="body-text" style="margin-bottom:16px;">${message}</p>
    <button type="button" class="btn btn-primary" id="confirm-btn">Təsdiqlə</button>
    <button type="button" class="btn btn-secondary" id="cancel-btn" style="margin-top:8px;">İmtina</button>
  `;
  const { close } = openSheet(content);
  content.querySelector('#confirm-btn').addEventListener('click', () => { close(); onConfirm(); });
  content.querySelector('#cancel-btn').addEventListener('click', close);
}

// Paylaş ikonu → Web Share API → /e/{slug} (Hissə 4.5). Dəstəklənmirsə
// keçid mübadilə buferinə kopyalanır.
async function shareOrder(order) {
  const url = `${location.origin}/e/${order.slug}`;
  const shareData = {
    title: `Yük.Birlikdə.biz — ${order.cargo_type_name}`,
    text: `${order.from_city} → ${order.to_city}`,
    url,
  };
  if (navigator.share) {
    try {
      await navigator.share(shareData);
    } catch {
      // istifadəçi ləğv edib — sakitcə keç
    }
  } else if (navigator.clipboard) {
    await navigator.clipboard.writeText(url);
    showToast('Keçid kopyalandı!');
  } else {
    showToast(url);
  }
}

function ratingSheet(orderId, onDone) {
  const content = document.createElement('div');
  content.innerHTML = `
    <h3 class="h3">Sürücünü qiymətləndirin</h3>
    <div id="stars" style="display:flex;gap:8px;margin:16px 0;font-size:32px;cursor:pointer;"></div>
    <textarea class="input" id="note-input" style="height:80px;padding-top:12px;" maxlength="200" placeholder="Qeyd (istəyə bağlı)"></textarea>
    <button type="button" class="btn btn-primary" id="send-btn" style="margin-top:16px;">Göndər</button>
    <button type="button" class="btn btn-secondary" id="skip-btn" style="margin-top:8px;">Keç</button>
  `;
  const { close } = openSheet(content);
  const starsEl = content.querySelector('#stars');
  let stars = 0;
  for (let i = 1; i <= 5; i++) {
    const star = document.createElement('span');
    star.textContent = '☆';
    star.dataset.value = i;
    star.addEventListener('click', () => {
      stars = i;
      [...starsEl.children].forEach((s, idx) => { s.textContent = idx < i ? '★' : '☆'; });
    });
    starsEl.appendChild(star);
  }
  content.querySelector('#skip-btn').addEventListener('click', close);
  content.querySelector('#send-btn').addEventListener('click', async () => {
    if (stars === 0) { close(); return; }
    try {
      await api.post('/ratings', { order_id: orderId, stars, note: content.querySelector('#note-input').value.trim() || undefined });
      showToast('Təşəkkürlər!');
    } catch (e) {
      showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
    }
    close();
    onDone?.();
  });
}

export async function mount(root, params) {
  if (!getState().authenticated) {
    navigate('/telefon', { replace: true });
    return () => {};
  }

  const orderId = parseInt(params.id, 10);
  const { user } = getState();
  const isDriver = user?.role === 'driver';

  root.innerHTML = `<div id="detail-body" style="padding-bottom:96px;"></div>`;
  const body = root.querySelector('#detail-body');

  let currentOrder = null;
  const shareBtn = document.createElement('button');
  shareBtn.type = 'button';
  shareBtn.className = 'appbar-back';
  shareBtn.innerHTML = ICONS.share;
  shareBtn.setAttribute('aria-label', 'Paylaş');
  shareBtn.addEventListener('click', () => currentOrder && shareOrder(currentOrder));

  const appbar = createAppBar({
    title: 'Elan',
    onBack: () => navigate('/ana-sehife'),
    action: isDriver ? undefined : shareBtn,
  });
  root.prepend(appbar);
  body.appendChild(createSkeletonList(3));

  // Müştəri elan hələ təklif qəbul edirsə (active/waiting), listing:{id}
  // kanalına qoşulub yeni/geri çəkilmiş təklifləri canlı görür — Hissə 7.2.
  let sseController = null;
  function syncRealtime(status) {
    const shouldConnect = !isDriver && ['active', 'waiting'].includes(status);
    if (shouldConnect && !sseController) {
      sseController = connectSSE([`listing:${orderId}`], {
        'offer.new': () => { showToast('Yeni təklif gəldi!'); load(); },
        'offer.withdrawn': () => load(),
      });
    } else if (!shouldConnect && sseController) {
      sseController.close();
      sseController = null;
    }
  }

  async function load() {
    body.innerHTML = '';
    try {
      if (isDriver) {
        const { order, my_offer } = await api.get(`/feed/${orderId}`);
        renderDriverView(order, my_offer);
      } else {
        const { order, offers } = await api.get(`/orders/${orderId}`);
        renderCustomerView(order, offers);
      }
    } catch (e) {
      body.innerHTML = `<p class="small-text" style="padding:16px;">${e instanceof ApiError ? e.message : 'Xəta baş verdi.'}</p>`;
    }
  }

  function renderGallery(images) {
    if (!images || images.length === 0) return '';
    return `<div class="detail-gallery">
      ${images.map((img) => `<img src="/uploads/${esc(img.path)}" alt="">`).join('')}
    </div>`;
  }

  function renderCustomerView(order, offers) {
    appbar.querySelector('.appbar-title').textContent = `Elan #${order.number}`;
    currentOrder = order;
    syncRealtime(order.status);

    body.innerHTML = `
      <div style="padding:16px;">
        ${renderGallery(order.images)}
        <div class="detail-card">
          <div class="card-top-row">
            <span class="chip chip-status-${esc(order.status)}">${esc(STATUS_LABELS[order.status] ?? order.status)}</span>
            <span class="caption-text">${esc(order.cargo_type_name)} · ${esc(formatDateTime(order.date_time))}</span>
          </div>
          ${routeVisual(order.from_city + (order.from_district ? ', ' + order.from_district : ''), order.to_city)}
          ${order.note ? `<div class="small-text" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);color:var(--text-muted);">${esc(order.note)}</div>` : ''}
        </div>

        <div id="negotiation-section"></div>
        <div id="offers-section" style="margin-top:24px;"></div>
      </div>
    `;

    const negotiationSection = body.querySelector('#negotiation-section');
    const offersSection = body.querySelector('#offers-section');

    if (order.status === 'negotiating') {
      const selected = offers.find((o) => o.status === 'selected');
      const initials = (selected?.driver_name ?? '').split(' ').map((p) => p[0]).join('').slice(0, 2);
      negotiationSection.innerHTML = `
        <div class="detail-card" style="background:var(--success-soft);border:none;margin-top:16px;">
          <div class="body-text" style="font-weight:600;color:var(--success);">Sürücü seçildi — əlaqə saxlayın.</div>
        </div>
        <div class="detail-card">
          <div style="display:flex;align-items:center;gap:12px;">
            <div class="offer-card-avatar">${esc(initials)}</div>
            <div>
              <div class="body-text" style="font-weight:600;">${esc(selected?.driver_name)}</div>
              <div class="small-text" style="color:var(--text-muted);">${esc(selected?.vehicle_name)}</div>
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-top:14px;">
            <a class="btn btn-secondary" style="height:44px;text-decoration:none;" href="tel:${encodeURIComponent(selected?.driver_phone ?? '')}">Zəng</a>
            <a class="btn btn-secondary" style="height:44px;text-decoration:none;" target="_blank"
               href="https://wa.me/${encodeURIComponent((selected?.driver_phone ?? '').replace(/\D/g, ''))}">WhatsApp</a>
          </div>
        </div>
        <button type="button" class="btn btn-primary" id="close-btn" style="margin-top:12px;">Sifarişi Bağla</button>
        <button type="button" class="btn btn-danger" id="cancel-sel-btn" style="margin-top:8px;">Təklifi Ləğv Et</button>
      `;
      negotiationSection.querySelector('#close-btn').addEventListener('click', () => {
        confirmSheet('Sifariş bağlansın? Elan arxivə keçəcək.', async () => {
          try {
            await api.post(`/orders/${orderId}/close`);
            showToast('Sifariş tamamlandı!');
            load();
          } catch (e) {
            showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
          }
        });
      });
      negotiationSection.querySelector('#cancel-sel-btn').addEventListener('click', () => {
        confirmSheet('Seçim ləğv edilsin? Elan yenidən aktiv olacaq və digər sürücülər təklif göndərə biləcək.', async () => {
          try {
            await api.post(`/orders/${orderId}/cancel-selection`);
            showToast('Seçim ləğv edildi.');
            load();
          } catch (e) {
            showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
          }
        });
      });
    } else if (order.status === 'closed') {
      negotiationSection.innerHTML = `<div id="rating-cta"></div>`;
      const cta = document.createElement('button');
      cta.type = 'button';
      cta.className = 'btn btn-secondary';
      cta.style.marginTop = '12px';
      cta.textContent = 'Sürücünü qiymətləndir';
      cta.addEventListener('click', () => ratingSheet(orderId, load));
      negotiationSection.querySelector('#rating-cta').appendChild(cta);
    } else if (order.status === 'expired') {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-primary';
      btn.style.marginTop = '12px';
      btn.textContent = 'Yenidən dərc et';
      btn.addEventListener('click', async () => {
        try {
          const { order: newOrder } = await api.post(`/orders/${orderId}/republish`);
          navigate(`/elan/${newOrder.id}`, { replace: true });
        } catch (e) {
          showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
        }
      });
      negotiationSection.appendChild(btn);
    }

    if (['active', 'waiting'].includes(order.status)) {
      const heading = document.createElement('h3');
      heading.className = 'h3';
      heading.textContent = `Təkliflər (${offers.length})`;
      offersSection.appendChild(heading);

      if (offers.length === 0) {
        const empty = document.createElement('p');
        empty.className = 'small-text';
        empty.style.color = 'var(--text-muted)';
        empty.textContent = 'Hələ təklif yoxdur. Sürücülər elanınızı görür — ilk təkliflər adətən bir neçə dəqiqəyə gəlir.';
        offersSection.appendChild(empty);
      } else {
        offers.forEach((offer) => {
          const card = createOfferCard({
            driver_name: offer.driver_name,
            vehicle: `${offer.vehicle_name} · ${offer.vehicle_size_code}`,
            rating: offer.rating_avg,
            price: offer.price,
            arrival: offer.arrival_time,
          });
          const selectBtn = document.createElement('button');
          selectBtn.type = 'button';
          selectBtn.className = 'btn btn-secondary';
          selectBtn.style.marginTop = '8px';
          selectBtn.textContent = 'Seç';
          selectBtn.addEventListener('click', () => {
            confirmSheet(`${esc(offer.driver_name)} seçilsin? Seçimdən sonra əlaqə nömrələri qarşılıqlı açılacaq və elana yeni təkliflər bağlanacaq.`, async () => {
              try {
                await api.post(`/offers/${offer.id}/select`);
                showToast('Sürücü seçildi.');
                load();
              } catch (e) {
                showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
              }
            });
          });
          card.appendChild(selectBtn);
          offersSection.appendChild(card);
        });
      }
    }
  }

  function renderDriverView(order, myOffer) {
    appbar.querySelector('.appbar-title').textContent = `Elan #${order.number}`;

    const fromLabel = order.from_city + (order.from_district ? ', ' + order.from_district : '') + (order.from_street ? ', ' + order.from_street : '');
    const toLabel = order.to_city + (order.to_district ? ', ' + order.to_district : '') + (order.to_street ? ', ' + order.to_street : '');

    body.innerHTML = `
      <div style="padding:16px;">
        ${renderGallery(order.images)}
        <div class="detail-card">
          <div class="card-top-row">
            <span class="chip chip-status-active">${esc(order.cargo_type)}</span>
            <span class="caption-text">${esc(formatDateTime(order.date_time))}</span>
          </div>
          ${routeVisual(fromLabel, toLabel)}
          ${order.note ? `<div class="small-text" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border);color:var(--text-muted);">${esc(order.note)}</div>` : ''}
          <div class="small-text" style="margin-top:14px;color:var(--text-muted);display:flex;align-items:center;gap:6px;">${esc(order.offer_count)} təklif verilib</div>
        </div>
        <div id="action-section" style="margin-top:16px;"></div>
      </div>
    `;

    const actionSection = body.querySelector('#action-section');

    if (myOffer?.status === 'selected' && ['negotiating', 'closed'].includes(order.status)) {
      actionSection.innerHTML = `
        <div class="detail-card" style="background:var(--success-soft);border:none;">
          <div class="body-text" style="font-weight:600;color:var(--success);">Siz seçildiniz — əlaqə saxlayın.</div>
          <div class="small-text" style="margin-top:4px;">${esc(order.customer_first_name)} · ${esc(order.customer_phone)}</div>
          <div style="display:flex;gap:8px;margin-top:14px;">
            <a class="btn btn-secondary" style="height:44px;text-decoration:none;" href="tel:${encodeURIComponent(order.customer_phone ?? '')}">Zəng</a>
            <a class="btn btn-secondary" style="height:44px;text-decoration:none;" target="_blank"
               href="https://wa.me/${encodeURIComponent((order.customer_phone ?? '').replace(/\D/g, ''))}">WhatsApp</a>
          </div>
        </div>
        ${order.status === 'negotiating' ? '<button type="button" class="btn btn-danger" id="decline-btn" style="margin-top:8px;">İmtina et</button>' : ''}
      `;
      actionSection.querySelector('#decline-btn')?.addEventListener('click', () => {
        confirmSheet('İmtina etsəniz elan yenidən aktiv olacaq.', async () => {
          try {
            await api.post(`/offers/${myOffer.id}/decline`);
            showToast('İmtina edildi.');
            load();
          } catch (e) {
            showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
          }
        });
      });
    } else if (['active', 'waiting'].includes(order.status)) {
      if (myOffer) {
        actionSection.innerHTML = `
          <div class="small-text" style="color:var(--text-muted);">Təklifiniz: <b class="tabular-nums">${esc(myOffer.price)} AZN</b> — Gözlənilir</div>
          <div style="display:flex;gap:8px;margin-top:12px;">
            <button type="button" class="btn btn-secondary" id="edit-offer-btn">Dəyiş</button>
            <button type="button" class="btn btn-danger" id="withdraw-btn">Geri çək</button>
          </div>
        `;
        actionSection.querySelector('#edit-offer-btn').addEventListener('click', () => {
          openOfferSheet(order.id, { existingOffer: myOffer, onSubmitted: load });
        });
        actionSection.querySelector('#withdraw-btn').addEventListener('click', async () => {
          try {
            await api.post(`/offers/${myOffer.id}/withdraw`);
            showToast('Təklifiniz geri çəkildi.');
            load();
          } catch (e) {
            showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
          }
        });
      } else {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-primary';
        btn.textContent = 'Təklif ver';
        btn.addEventListener('click', () => openOfferSheet(order.id, { onSubmitted: load }));
        actionSection.appendChild(btn);
      }
    }
  }

  await load();

  return () => sseController?.close();
}
