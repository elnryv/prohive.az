import { api, ApiError } from '../api.js';
import { getState } from '../store.js';
import { navigate } from '../router.js';
import { createAppBar } from '../components/appbar.js';
import { createSkeletonList } from '../components/skeleton.js';
import { showToast } from '../components/toast.js';
import { connectSSE } from '../sse.js';
import { esc } from '../utils.js';
import { mountBanners } from '../banner-loader.js';

const PAYMENT_STATUS_LABELS = {
  pending: 'Gözləyir',
  success: 'Uğurlu',
  failed: 'Uğursuz',
};

export async function mount(root) {
  const { user, authenticated } = getState();
  if (!authenticated) {
    navigate('/telefon', { replace: true });
    return () => {};
  }
  if (user?.role !== 'driver') {
    navigate('/ana-sehife', { replace: true });
    return () => {};
  }

  root.innerHTML = `<div id="banner-slot" style="padding:0 16px;"></div><div id="sub-body" style="padding-bottom:96px;"></div>`;
  const body = root.querySelector('#sub-body');
  const appbar = createAppBar({ title: 'Abunə', onBack: () => navigate('/ana-sehife') });
  root.prepend(appbar);
  body.appendChild(createSkeletonList(3));

  const cleanupBanners = mountBanners(root.querySelector('#banner-slot'), 'subscription');

  const paymentParam = new URLSearchParams(location.search).get('payment');
  if (paymentParam === 'success') {
    showToast('Ödəniş tamamlandı, təsdiqlənir…');
  } else if (paymentParam === 'cancelled') {
    showToast('Ödəniş ləğv edildi.');
  } else if (paymentParam === 'declined') {
    showToast('Ödəniş rədd edildi.');
  }

  async function load() {
    try {
      const data = await api.get('/subscription/status');
      render(data);
    } catch (e) {
      body.innerHTML = `<p class="small-text" style="padding:16px;">${e instanceof ApiError ? e.message : 'Xəta baş verdi.'}</p>`;
    }
  }

  function statusCard(data) {
    if (data.mode === 'free') {
      return `
        <div class="listing-card" style="background:var(--primary-soft);border:none;">
          <div class="body-text" style="font-weight:600;">Platforma hazırda pulsuz istifadə olunur</div>
          <div class="small-text" style="margin-top:4px;">Təklif göndərmək üçün abunə tələb olunmur.</div>
        </div>
      `;
    }
    if (data.active) {
      return `
        <div class="listing-card" style="background:var(--success-soft);border:none;">
          <div class="body-text" style="font-weight:600;">Abunəniz aktivdir</div>
          <div class="small-text" style="margin-top:4px;">Bitmə tarixi: ${esc(data.ends_at)} · qalan ${data.days_left} gün</div>
        </div>
      `;
    }
    return `
      <div class="listing-card" style="background:var(--error-soft);border:none;">
        <div class="body-text" style="font-weight:600;">Təklif göndərmək üçün abunə lazımdır</div>
      </div>
    `;
  }

  function render(data) {
    body.innerHTML = `
      <div style="padding:16px;">
        <div id="status-slot">${statusCard(data)}</div>

        <div class="listing-card" style="margin-top:16px;">
          <div class="card-top-row"><span class="h3">${data.price} ${esc(data.currency)}</span><span class="caption-text">/ ${data.days} gün</span></div>
        </div>

        <button type="button" class="btn btn-primary" id="pay-btn" style="margin-top:16px;">Payriff ilə ödə</button>

        <h3 class="h3" style="margin-top:24px;">Ödəniş tarixçəm</h3>
        <div id="history-slot"></div>
      </div>
    `;

    const historySlot = body.querySelector('#history-slot');
    if (data.history.length === 0) {
      historySlot.innerHTML = '<p class="small-text" style="color:var(--text-muted);">Hələ ödəniş yoxdur.</p>';
    } else {
      data.history.forEach((p) => {
        const row = document.createElement('div');
        row.className = 'sheet-row';
        row.innerHTML = `<span>${esc(p.created_at)} · ${p.amount} ${esc(p.currency)}</span><span class="chip${p.status === 'success' ? ' active' : ''}">${esc(PAYMENT_STATUS_LABELS[p.status] ?? p.status)}</span>`;
        historySlot.appendChild(row);
      });
    }

    const payBtn = body.querySelector('#pay-btn');
    payBtn.addEventListener('click', async () => {
      payBtn.disabled = true;
      payBtn.classList.add('btn-loading');
      try {
        const { payment_url } = await api.post('/subscription/checkout');
        location.href = payment_url;
      } catch (e) {
        showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
        payBtn.disabled = false;
        payBtn.classList.remove('btn-loading');
      }
    });
  }

  await load();

  // Ödəniş callback-i (webhook) fondan gəlir — bu ekran açıq qalsa belə
  // canlı yenilənir (Hissə 7.2 user:{id} kanalı).
  const sse = connectSSE([`user:${user.id}`], {
    'subscription.activated': () => { showToast('Abunəniz aktivləşdi!'); load(); },
    'subscription.expired': () => { showToast('Abunəniz bitdi.'); load(); },
  });

  return () => {
    sse.close();
    cleanupBanners();
  };
}
