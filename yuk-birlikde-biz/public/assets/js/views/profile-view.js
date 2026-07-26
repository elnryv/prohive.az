import { api, ApiError } from '../api.js';
import { openSheet } from '../components/sheet.js';
import { createPinPad } from '../components/pinpad.js';
import { showToast } from '../components/toast.js';
import { ICONS } from '../components/icons.js';
import { getState, setState } from '../store.js';
import { navigate } from '../router.js';
import { esc } from '../utils.js';
import { mountBanners } from '../banner-loader.js';

const ROUTE_CITIES = ['Bakı', 'Sumqayıt', 'Gəncə', 'Mingəçevir', 'Naxçıvan', 'Şəki', 'Lənkəran', 'Şirvan'];

async function openCmsSheet(slug, fallbackTitle) {
  const content = document.createElement('div');
  content.innerHTML = `<h3 class="h3">${fallbackTitle}</h3><p class="small-text">Yüklənir…</p>`;
  openSheet(content);
  try {
    const { page } = await api.get(`/pages/${slug}`);
    content.innerHTML = `<h3 class="h3">${page.title}</h3><div class="body-text">${page.content}</div>`;
  } catch {
    content.innerHTML = `<h3 class="h3">${fallbackTitle}</h3><p class="small-text">Yüklənə bilmədi.</p>`;
  }
}

function openPinChangeSheet() {
  const content = document.createElement('div');
  content.innerHTML = `<h3 class="h3" style="margin-bottom:16px;">Köhnə PIN</h3><div id="step-slot"></div>`;
  const { close, sheetEl } = openSheet(content);
  const slot = content.querySelector('#step-slot');

  let oldPin = null;
  function showOld() {
    slot.innerHTML = '';
    const pad = createPinPad({
      length: 4,
      onComplete: async (pin) => {
        oldPin = pin;
        sheetEl.querySelector('h3').textContent = 'Yeni PIN';
        showNew();
      },
    });
    slot.appendChild(pad.el);
  }
  function showNew() {
    slot.innerHTML = '';
    const pad = createPinPad({
      length: 4,
      onComplete: async (pin) => {
        try {
          await api.post('/auth/change-pin', { old_pin: oldPin, new_pin: pin });
          showToast('PIN uğurla dəyişdirildi.');
          close();
        } catch (e) {
          showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
          sheetEl.querySelector('h3').textContent = 'Köhnə PIN';
          showOld();
        }
      },
    });
    slot.appendChild(pad.el);
  }
  showOld();
}

function openDeleteAccountSheet() {
  const content = document.createElement('div');
  content.innerHTML = `
    <h3 class="h3" style="color:var(--error);">Hesabı sil</h3>
    <p class="small-text" style="margin:12px 0;">Hesabınız və bütün məlumatlarınız silinəcək. Bu əməliyyat geri qaytarılmır. Təsdiqləmək üçün PIN daxil edin.</p>
    <div id="pin-slot"></div>
  `;
  const { close } = openSheet(content);
  const pad = createPinPad({
    length: 4,
    onComplete: async (pin) => {
      try {
        await api.post('/auth/delete-account', { pin });
        setState({ authenticated: false, user: null, pendingPhone: null });
        close();
        navigate('/', { replace: true });
      } catch (e) {
        showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
        pad.shakeError();
      }
    },
  });
  content.querySelector('#pin-slot').appendChild(pad.el);
}

function openComplaintSheet() {
  const content = document.createElement('div');
  content.innerHTML = `
    <h3 class="h3" style="margin-bottom:16px;">Şikayət göndər</h3>
    <input class="input" id="subject-input" placeholder="Mövzu" style="margin-bottom:12px;">
    <textarea class="input" id="message-input" style="height:100px;padding-top:12px;" placeholder="Mesajınız"></textarea>
    <button type="button" class="btn btn-primary" id="send-btn" style="margin-top:16px;">Göndər</button>
  `;
  const { close } = openSheet(content);
  content.querySelector('#send-btn').addEventListener('click', async () => {
    const subject = content.querySelector('#subject-input').value.trim();
    const message = content.querySelector('#message-input').value.trim();
    if (!subject || !message) {
      showToast('Bütün sahələri doldurun.');
      return;
    }
    try {
      await api.post('/complaints', { subject, message });
      showToast('Şikayətiniz göndərildi.');
      close();
    } catch (e) {
      showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
    }
  });
}

function openAddRouteSheet(onAdded) {
  const content = document.createElement('div');
  content.innerHTML = `
    <h3 class="h3" style="margin-bottom:16px;">Marşrut əlavə et</h3>
    <div class="sheet-row" id="baku-preset">Bakı daxili (hamısı)</div>
    <div class="small-text" style="margin:12px 0 4px;color:var(--text-muted);">Haradan</div>
    <div id="from-list"></div>
    <div class="small-text" style="margin:12px 0 4px;color:var(--text-muted);">Hara</div>
    <div id="to-list"></div>
    <button type="button" class="btn btn-primary" id="add-btn" style="margin-top:16px;" disabled>Əlavə et</button>
  `;
  const { close } = openSheet(content);

  let fromCity = null;
  let toCity = null;
  const addBtn = content.querySelector('#add-btn');
  function checkValid() { addBtn.disabled = !fromCity || !toCity; }

  function buildList(containerId, onSelect) {
    const container = content.querySelector(containerId);
    ROUTE_CITIES.forEach((city) => {
      const row = document.createElement('div');
      row.className = 'sheet-row';
      row.textContent = city;
      row.addEventListener('click', () => {
        [...container.children].forEach((c) => c.classList.remove('selected'));
        row.classList.add('selected');
        onSelect(city);
        checkValid();
      });
      container.appendChild(row);
    });
  }
  buildList('#from-list', (city) => { fromCity = city; });
  buildList('#to-list', (city) => { toCity = city; });

  content.querySelector('#baku-preset').addEventListener('click', async () => {
    try {
      await api.post('/routes', { from_city: 'Bakı', to_city: 'Bakı' });
      close();
      onAdded();
    } catch (e) {
      showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
    }
  });

  addBtn.addEventListener('click', async () => {
    try {
      await api.post('/routes', { from_city: fromCity, to_city: toCity });
      close();
      onAdded();
    } catch (e) {
      showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
    }
  });
}

async function renderRouteWatches(container) {
  container.innerHTML = '<p class="small-text" style="color:var(--text-muted);">Yüklənir…</p>';
  try {
    const { routes } = await api.get('/routes');
    container.innerHTML = '';

    routes.forEach((route) => {
      const row = document.createElement('div');
      row.className = 'sheet-row';
      row.innerHTML = `<span>${esc(route.from_city)} → ${esc(route.to_city)}</span><button type="button" class="route-remove-btn" style="border:none;background:none;">${ICONS.closeSmall}</button>`;
      row.querySelector('button').addEventListener('click', async () => {
        try {
          await api.del('/routes', { id: route.id });
          renderRouteWatches(container);
        } catch (e) {
          showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
        }
      });
      container.appendChild(row);
    });

    if (routes.length < 5) {
      const addBtn = document.createElement('button');
      addBtn.type = 'button';
      addBtn.className = 'chip';
      addBtn.style.marginTop = '8px';
      addBtn.textContent = '+ Marşrut əlavə et';
      addBtn.addEventListener('click', () => openAddRouteSheet(() => renderRouteWatches(container)));
      container.appendChild(addBtn);
    }
  } catch {
    container.innerHTML = '<p class="small-text">Yüklənə bilmədi.</p>';
  }
}

async function renderSubscriptionChip(chipEl) {
  try {
    const data = await api.get('/subscription/status');
    if (data.mode === 'free') {
      chipEl.textContent = 'Pulsuz rejim';
      chipEl.classList.add('active');
    } else if (data.active) {
      chipEl.textContent = 'Aktiv';
      chipEl.classList.add('active');
    } else {
      chipEl.textContent = 'Bitib';
      chipEl.style.color = 'var(--error)';
    }
  } catch {
    chipEl.textContent = '—';
  }
}

function settingsRow(id, icon, label, { value = '', danger = false, noChevron = false } = {}) {
  return `
    <div class="settings-row" id="${id}">
      <span class="settings-icon${danger ? ' danger' : ''}">${icon}</span>
      <span class="settings-label">${label}</span>
      ${value ? `<span class="settings-value">${value}</span>` : ''}
      ${noChevron ? '' : `<span class="settings-chevron">${ICONS.chevronRight}</span>`}
    </div>
  `;
}

function toggleRow(id, icon, label, checked) {
  return `
    <div class="settings-row">
      <span class="settings-icon">${icon}</span>
      <span class="settings-label">${label}</span>
      <label class="toggle">
        <input type="checkbox" id="${id}" ${checked ? 'checked' : ''}>
        <span class="toggle-track"></span>
        <span class="toggle-thumb"></span>
      </label>
    </div>
  `;
}

export async function render(root) {
  const { user } = getState();
  const config = await api.get('/config').catch(() => ({}));
  const profile = await api.get('/profile').catch(() => ({ profile: null }));
  const p = profile.profile ?? {};
  const initials = `${(p.first_name ?? '')[0] ?? ''}${(p.last_name ?? '')[0] ?? ''}`;

  root.innerHTML = `
    <div class="profile-hero">
      <div class="profile-hero-avatar">
        ${p.avatar_path ? `<img src="/uploads/${esc(p.avatar_path)}" alt="">` : esc(initials)}
      </div>
      <div style="min-width:0;">
        <div class="h3 profile-hero-name">${esc(p.first_name)} ${esc(p.last_name)}</div>
        <div class="small-text profile-hero-phone">+${esc(p.phone)}</div>
        <span class="profile-hero-role">${p.role === 'driver' ? 'Sürücü' : 'Müştəri'}</span>
      </div>
    </div>

    <div id="banner-slot"></div>

    <div class="detail-card settings-card">
      ${settingsRow('edit-name', ICONS.person, 'Ad və soyadı dəyiş')}
      ${settingsRow('edit-pin', ICONS.lock, 'PIN dəyişdir')}
      ${p.role === 'driver' ? settingsRow('edit-vehicle', ICONS.truck, 'Avtomobil məlumatları') : ''}
    </div>

    ${p.role === 'driver' ? `
      <h3 class="h3" style="margin:20px 4px 8px;">Abunə</h3>
      <div class="detail-card settings-card">
        ${settingsRow('subscription-link', ICONS.bank, 'Abunə statusu', { value: '<span id="subscription-chip" class="chip" style="height:auto;padding:3px 12px;font-size:12px;">Yüklənir…</span>' })}
      </div>

      <h3 class="h3" style="margin:20px 4px 8px;">İzlədiyim marşrutlar</h3>
      <div class="detail-card" id="route-watches"></div>
    ` : ''}

    <h3 class="h3" style="margin:20px 4px 8px;">Bildiriş ayarları</h3>
    <div class="detail-card settings-card">
      ${toggleRow('notify-offers', ICONS.tag, 'Təklif bildirişləri', p.notify_offers)}
      ${toggleRow('notify-status', ICONS.bell, 'Status bildirişləri', p.notify_status)}
      ${toggleRow('notify-system', ICONS.info, 'Sistem xəbərləri', p.notify_system)}
    </div>

    <h3 class="h3" style="margin:20px 4px 8px;">Dəstək</h3>
    <div class="detail-card settings-card">
      ${settingsRow('complaint-link', ICONS.flag, 'Şikayət göndər')}
      ${settingsRow('about-link', ICONS.info, 'Haqqımızda')}
      ${settingsRow('terms-link', ICONS.doc, 'İstifadə Şərtləri')}
      ${settingsRow('privacy-link', ICONS.doc, 'Məxfilik Siyasəti')}
      ${settingsRow('faq-link', ICONS.help, 'FAQ')}
      ${settingsRow('contact-link', ICONS.phone, 'Əlaqə')}
    </div>

    <div class="detail-card settings-card">
      ${settingsRow('delete-btn', ICONS.trash, 'Hesabı sil', { danger: true, noChevron: true })}
      ${settingsRow('logout-btn', ICONS.logout, 'Çıxış', { noChevron: true })}
    </div>

    <div class="caption-text" style="text-align:center;margin-top:8px;">${config.copyright ?? ''}</div>
  `;

  root.querySelector('#edit-name').addEventListener('click', () => {
    const content = document.createElement('div');
    content.innerHTML = `
      <h3 class="h3" style="margin-bottom:16px;">Ad və soyadı dəyiş</h3>
      <input class="input" id="fn" value="${esc(p.first_name)}" style="margin-bottom:12px;">
      <input class="input" id="ln" value="${esc(p.last_name)}">
      <button type="button" class="btn btn-primary" id="save-btn" style="margin-top:16px;">Yadda saxla</button>
    `;
    const { close } = openSheet(content);
    content.querySelector('#save-btn').addEventListener('click', async () => {
      const firstName = content.querySelector('#fn').value.trim();
      const lastName = content.querySelector('#ln').value.trim();
      try {
        await api.patch('/profile', { first_name: firstName, last_name: lastName });
        setState({ user: { ...user, first_name: firstName, last_name: lastName } });
        showToast('Yadda saxlanıldı.');
        close();
        render(root);
      } catch (e) {
        showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
      }
    });
  });

  const cleanupBanners = mountBanners(root.querySelector('#banner-slot'), 'profile');

  root.querySelector('#edit-pin').addEventListener('click', openPinChangeSheet);
  root.querySelector('#complaint-link').addEventListener('click', openComplaintSheet);
  root.querySelector('#about-link').addEventListener('click', () => openCmsSheet('about', 'Haqqımızda'));
  root.querySelector('#terms-link').addEventListener('click', () => openCmsSheet('terms', 'İstifadə Şərtləri'));
  root.querySelector('#privacy-link').addEventListener('click', () => openCmsSheet('privacy', 'Məxfilik Siyasəti'));
  root.querySelector('#faq-link').addEventListener('click', () => openCmsSheet('faq', 'FAQ'));
  root.querySelector('#contact-link').addEventListener('click', () => openCmsSheet('contact', 'Əlaqə'));
  root.querySelector('#delete-btn').addEventListener('click', openDeleteAccountSheet);

  root.querySelector('#logout-btn').addEventListener('click', async () => {
    await api.post('/auth/logout').catch(() => {});
    setState({ authenticated: false, user: null, pendingPhone: null });
    navigate('/', { replace: true });
  });

  ['offers', 'status', 'system'].forEach((key) => {
    root.querySelector(`#notify-${key}`).addEventListener('change', (e) => {
      api.patch('/profile', { [`notify_${key}`]: e.target.checked }).catch(() => {
        showToast('Yadda saxlanıla bilmədi.');
      });
    });
  });

  if (p.role === 'driver') {
    root.querySelector('#edit-vehicle').addEventListener('click', () => {
      const content = document.createElement('div');
      content.innerHTML = `<h3 class="h3" style="margin-bottom:16px;">Avtomobil</h3><div id="vehicle-list"></div>`;
      const { close } = openSheet(content);
      const list = content.querySelector('#vehicle-list');
      (config.vehicles ?? []).forEach((vehicle) => {
        const row = document.createElement('div');
        row.className = 'sheet-row' + (vehicle.id === p.driver?.vehicle_id ? ' selected' : '');
        row.textContent = vehicle.name;
        row.addEventListener('click', async () => {
          try {
            await api.patch('/profile', { vehicle_id: vehicle.id });
            showToast('Avtomobil yeniləndi.');
            close();
            render(root);
          } catch (e) {
            showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
          }
        });
        list.appendChild(row);
      });
    });

    root.querySelector('#subscription-link').addEventListener('click', () => navigate('/abune'));
    renderSubscriptionChip(root.querySelector('#subscription-chip'));
    renderRouteWatches(root.querySelector('#route-watches'));
  }

  return cleanupBanners;
}
