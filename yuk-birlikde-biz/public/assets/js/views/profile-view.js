import { api, ApiError } from '../api.js';
import { openSheet } from '../components/sheet.js';
import { createPinPad } from '../components/pinpad.js';
import { showToast } from '../components/toast.js';
import { getState, setState } from '../store.js';
import { navigate } from '../router.js';
import { esc } from '../utils.js';

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

export async function render(root) {
  const { user } = getState();
  const config = await api.get('/config').catch(() => ({}));
  const profile = await api.get('/profile').catch(() => ({ profile: null }));
  const p = profile.profile ?? {};

  root.innerHTML = `
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
      <div class="avatar-circle" style="width:64px;height:64px;">
        ${p.avatar_path ? `<img src="/uploads/${p.avatar_path}">` : ''}
      </div>
      <div>
        <div class="h3">${esc(p.first_name)} ${esc(p.last_name)}</div>
        <div class="small-text" style="color:var(--text-muted);">+${esc(p.phone)}</div>
      </div>
    </div>

    <div class="sheet-row" id="edit-name">Ad və soyadı dəyiş</div>
    <div class="sheet-row" id="edit-pin">PIN dəyişdir</div>
    ${p.role === 'driver' ? `<div class="sheet-row" id="edit-vehicle">Avtomobil məlumatları</div>` : ''}

    <h3 class="h3" style="margin-top:24px;">Bildiriş ayarları</h3>
    <div class="sheet-row"><span>Təklif bildirişləri</span><input type="checkbox" id="notify-offers" ${p.notify_offers ? 'checked' : ''}></div>
    <div class="sheet-row"><span>Status bildirişləri</span><input type="checkbox" id="notify-status" ${p.notify_status ? 'checked' : ''}></div>
    <div class="sheet-row"><span>Sistem xəbərləri</span><input type="checkbox" id="notify-system" ${p.notify_system ? 'checked' : ''}></div>

    <h3 class="h3" style="margin-top:24px;">Dəstək</h3>
    <div class="sheet-row" id="complaint-link">Şikayət göndər</div>
    <div class="sheet-row" id="about-link">Haqqımızda</div>
    <div class="sheet-row" id="terms-link">İstifadə Şərtləri</div>
    <div class="sheet-row" id="privacy-link">Məxfilik Siyasəti</div>
    <div class="sheet-row" id="faq-link">FAQ</div>
    <div class="sheet-row" id="contact-link">Əlaqə</div>

    <button type="button" class="btn btn-danger" id="delete-btn" style="margin-top:24px;">Hesabı sil</button>
    <button type="button" class="btn btn-secondary" id="logout-btn" style="margin-top:12px;">Çıxış</button>

    <div class="caption-text" style="text-align:center;margin-top:24px;">${config.copyright ?? ''}</div>
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
  }
}
