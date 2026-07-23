import { api, ApiError } from '../api.js';
import { createPinPad } from '../components/pinpad.js';
import { createStepper } from '../components/stepper.js';
import { openSheet } from '../components/sheet.js';
import { ICONS } from '../components/icons.js';
import { getState, setState } from '../store.js';
import { navigate } from '../router.js';
import { esc } from '../utils.js';

function readCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : '';
}

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

async function cropToSquareFile(file) {
  const bitmap = await createImageBitmap(file);
  const side = Math.min(bitmap.width, bitmap.height);
  const canvas = document.createElement('canvas');
  canvas.width = 480;
  canvas.height = 480;
  const ctx = canvas.getContext('2d');
  ctx.drawImage(
    bitmap,
    (bitmap.width - side) / 2, (bitmap.height - side) / 2, side, side,
    0, 0, 480, 480
  );
  return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.85));
}

export async function mount(root) {
  const phone = getState().pendingPhone;
  if (!phone) {
    navigate('/telefon', { replace: true });
    return () => {};
  }

  const config = await api.get('/config').catch(() => ({ pin_length: 4, vehicles: [], vehicle_sizes: [] }));

  const data = {
    role: null,
    pin: null,
    first_name: '',
    last_name: '',
    avatarBlob: null,
    vehicle_id: null,
    vehicle_other: '',
    vehicle_size_id: null,
    consent: false,
  };

  root.classList.add('register-screen');
  root.innerHTML = `
    <div style="display:flex;align-items:center;">
      <button type="button" id="back-btn" class="appbar-back" aria-label="Geri">${ICONS.chevronLeft}</button>
      <div id="stepper-slot" style="flex:1"></div>
    </div>
    <div id="step-content"></div>
  `;

  const stepperSlot = root.querySelector('#stepper-slot');
  const contentEl = root.querySelector('#step-content');
  const backBtn = root.querySelector('#back-btn');

  function steps() {
    const base = ['role', 'pinCreate', 'pinConfirm', 'name', 'surname', 'avatar'];
    if (data.role === 'driver') {
      return [...base, 'vehicleType', 'vehicleSize', 'consent'];
    }
    return [...base, 'consent'];
  }

  let stepper = null;
  let index = 0;

  function goTo(newIndex) {
    index = newIndex;
    renderCurrent();
  }
  function goNext() { goTo(index + 1); }
  function goBack() {
    if (index === 0) {
      navigate('/telefon');
      return;
    }
    goTo(index - 1);
  }

  backBtn.addEventListener('click', goBack);

  function renderCurrent() {
    const list = steps();
    if (!stepper || stepper.total !== list.length) {
      stepperSlot.innerHTML = '';
      stepper = createStepper(list.length);
      stepper.total = list.length;
      stepperSlot.appendChild(stepper.el);
    }
    stepper.setStep(index);
    renderStep(list[index]);
  }

  function renderStep(key) {
    contentEl.innerHTML = '';
    contentEl.classList.remove('screen-enter');
    void contentEl.offsetWidth;
    contentEl.classList.add('screen-enter');

    const renderers = {
      role: renderRoleStep,
      pinCreate: () => renderPinStep('create'),
      pinConfirm: () => renderPinStep('confirm'),
      name: () => renderTextStep('first_name', 'Adınız', 'Adınızı daxil edin.'),
      surname: () => renderTextStep('last_name', 'Soyadınız', 'Soyadınızı daxil edin.'),
      avatar: renderAvatarStep,
      vehicleType: renderVehicleTypeStep,
      vehicleSize: renderVehicleSizeStep,
      consent: renderConsentStep,
    };
    renderers[key]();
  }

  function renderRoleStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Platformadan necə istifadə edəcəksiniz?</h2>
      <div class="role-cards">
        <div class="role-card" data-role="customer">
          <span class="role-card-icon">${ICONS.box}</span>
          <div class="role-card-title">Yük göndərirəm</div>
          <div class="role-card-desc">Elan yaradın, sürücülərdən təklif alın</div>
        </div>
        <div class="role-card" data-role="driver">
          <span class="role-card-icon">${ICONS.truck}</span>
          <div class="role-card-title">Yük daşıyıram</div>
          <div class="role-card-desc">Elanlara baxın, qiymət təklif edin</div>
        </div>
      </div>
      <button type="button" class="btn btn-primary" id="continue-btn" disabled>Davam et</button>
    `;
    const cards = [...contentEl.querySelectorAll('.role-card')];
    const continueBtn = contentEl.querySelector('#continue-btn');
    cards.forEach((card) => {
      card.addEventListener('click', () => {
        cards.forEach((c) => c.classList.toggle('selected', c === card));
        data.role = card.dataset.role;
        continueBtn.disabled = false;
      });
    });
    continueBtn.addEventListener('click', goNext);
  }

  function renderPinStep(mode) {
    const isCreate = mode === 'create';
    contentEl.innerHTML = `
      <h2 class="h2">${isCreate ? `${config.pin_length} rəqəmli PIN yaradın` : 'PIN kodu təkrar daxil edin'}</h2>
      <p class="small-text" style="color:var(--text-muted);margin:8px 0 16px;">
        ${isCreate ? 'Hesabınızı qorumaq üçün asan yadda qalan PIN seçin.' : ''}
      </p>
      <div id="pinpad-slot"></div>
    `;
    const pinpad = createPinPad({
      length: config.pin_length,
      onComplete: (pin) => {
        if (isCreate) {
          data.pin = pin;
          goNext();
          return;
        }
        if (pin !== data.pin) {
          pinpad.shakeError();
          setTimeout(() => {
            data.pin = null;
            goTo(steps().indexOf('pinCreate'));
          }, 450);
          return;
        }
        goNext();
      },
    });
    contentEl.querySelector('#pinpad-slot').appendChild(pinpad.el);
  }

  function renderTextStep(field, label, errorMsg) {
    contentEl.innerHTML = `
      <h2 class="h2">${label}</h2>
      <div class="input-group" style="margin-top:16px;">
        <input class="input" id="text-input" placeholder="${label}" value="${esc(data[field])}">
        <div class="input-error-text" id="text-error" style="display:none;">${errorMsg}</div>
      </div>
      <button type="button" class="btn btn-primary" id="continue-btn">Davam et</button>
    `;
    const input = contentEl.querySelector('#text-input');
    const errorEl = contentEl.querySelector('#text-error');
    contentEl.querySelector('#continue-btn').addEventListener('click', () => {
      const value = input.value.trim();
      if (value === '') {
        errorEl.style.display = 'block';
        return;
      }
      data[field] = value;
      goNext();
    });
  }

  function renderAvatarStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Profil şəkli (istəyə bağlı)</h2>
      <div class="avatar-picker">
        <div class="avatar-circle" id="avatar-preview"></div>
        <div class="avatar-actions" id="avatar-actions">
          <label class="chip">Kamera<input type="file" accept="image/*" capture="user" id="camera-input" hidden></label>
          <label class="chip">Qalereya<input type="file" accept="image/*" id="gallery-input" hidden></label>
          <button type="button" class="chip" id="skip-btn">Keç</button>
        </div>
      </div>
      <button type="button" class="btn btn-primary" id="continue-btn" style="display:none;">Təsdiqlə</button>
    `;
    const preview = contentEl.querySelector('#avatar-preview');
    const continueBtn = contentEl.querySelector('#continue-btn');
    const actions = contentEl.querySelector('#avatar-actions');

    async function handleFile(file) {
      if (!file) return;
      const blob = await cropToSquareFile(file);
      data.avatarBlob = blob;
      preview.innerHTML = `<img src="${URL.createObjectURL(blob)}">`;
      actions.style.display = 'none';
      continueBtn.style.display = 'flex';
    }

    contentEl.querySelector('#camera-input').addEventListener('change', (e) => handleFile(e.target.files[0]));
    contentEl.querySelector('#gallery-input').addEventListener('change', (e) => handleFile(e.target.files[0]));
    contentEl.querySelector('#skip-btn').addEventListener('click', () => goNext());
    continueBtn.addEventListener('click', () => goNext());
  }

  function renderVehicleTypeStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Avtomobilinizi seçin</h2>
      <div id="vehicle-list" style="margin-top:16px;"></div>
      <input class="input" id="vehicle-other-input" placeholder="Avtomobil növü" style="display:none;margin-top:12px;">
      <button type="button" class="btn btn-primary" id="continue-btn" style="margin-top:16px;" disabled>Davam et</button>
    `;
    const list = contentEl.querySelector('#vehicle-list');
    const otherInput = contentEl.querySelector('#vehicle-other-input');
    const continueBtn = contentEl.querySelector('#continue-btn');

    function checkValid() {
      const other = config.vehicles.find((v) => v.id === data.vehicle_id)?.name === 'Digər';
      continueBtn.disabled = !data.vehicle_id || (other && data.vehicle_other.trim() === '');
    }

    config.vehicles.forEach((vehicle) => {
      const row = document.createElement('div');
      row.className = 'sheet-row';
      row.textContent = vehicle.name;
      row.addEventListener('click', () => {
        [...list.children].forEach((el) => el.classList.remove('selected'));
        row.classList.add('selected');
        data.vehicle_id = vehicle.id;
        otherInput.style.display = vehicle.name === 'Digər' ? 'block' : 'none';
        checkValid();
      });
      list.appendChild(row);
    });

    otherInput.addEventListener('input', () => {
      data.vehicle_other = otherInput.value;
      checkValid();
    });

    continueBtn.addEventListener('click', goNext);
  }

  function renderVehicleSizeStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Yük bölməsinin ölçüsü</h2>
      <div class="vehicle-grid" id="size-grid"></div>
      <button type="button" class="btn btn-primary" id="continue-btn" disabled>Davam et</button>
    `;
    const grid = contentEl.querySelector('#size-grid');
    const continueBtn = contentEl.querySelector('#continue-btn');

    config.vehicle_sizes.forEach((size) => {
      const card = document.createElement('div');
      card.className = 'vehicle-size-card';
      card.innerHTML = `<div class="vehicle-size-code">${size.code}</div><div class="vehicle-size-dims">${size.dimensions}</div>`;
      card.addEventListener('click', () => {
        [...grid.children].forEach((el) => el.classList.remove('selected'));
        card.classList.add('selected');
        data.vehicle_size_id = size.id;
        continueBtn.disabled = false;
      });
      grid.appendChild(card);
    });

    continueBtn.addEventListener('click', goNext);
  }

  function renderConsentStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Son addım</h2>
      <div class="consent-row">
        <input type="checkbox" id="consent-check" style="margin-top:4px;">
        <label for="consent-check" class="small-text">
          <a id="terms-link">İstifadə Şərtləri</a> və <a id="privacy-link">Məxfilik Siyasəti</a> ilə razıyam
        </label>
      </div>
      <div class="input-error-text" id="submit-error" style="display:none;"></div>
      <button type="button" class="btn btn-primary" id="submit-btn" disabled>Qeydiyyatı tamamla</button>
    `;
    contentEl.querySelector('#terms-link').addEventListener('click', () => openCmsSheet('terms', 'İstifadə Şərtləri'));
    contentEl.querySelector('#privacy-link').addEventListener('click', () => openCmsSheet('privacy', 'Məxfilik Siyasəti'));

    const checkbox = contentEl.querySelector('#consent-check');
    const submitBtn = contentEl.querySelector('#submit-btn');
    const errorEl = contentEl.querySelector('#submit-error');

    checkbox.addEventListener('change', () => { submitBtn.disabled = !checkbox.checked; });

    submitBtn.addEventListener('click', async () => {
      submitBtn.disabled = true;
      submitBtn.classList.add('btn-loading');
      errorEl.style.display = 'none';

      try {
        const payload = {
          phone,
          pin: data.pin,
          role: data.role,
          first_name: data.first_name,
          last_name: data.last_name,
          consent: true,
        };
        if (data.role === 'driver') {
          payload.vehicle_id = data.vehicle_id;
          payload.vehicle_other = data.vehicle_other;
          payload.vehicle_size_id = data.vehicle_size_id;
        }

        const { user } = await api.post('/auth/register', payload);

        if (data.avatarBlob) {
          const form = new FormData();
          form.append('avatar', data.avatarBlob, 'avatar.jpg');
          await fetch('/api/v1/profile/avatar', {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': readCookie('ybb_csrf') },
          });
        }

        setState({ authenticated: true, user });
        navigate('/ana-sehife', { replace: true });
      } catch (e) {
        errorEl.textContent = e instanceof ApiError ? e.message : 'Xəta baş verdi.';
        errorEl.style.display = 'block';
        submitBtn.disabled = false;
      } finally {
        submitBtn.classList.remove('btn-loading');
      }
    });
  }

  renderCurrent();

  return () => root.classList.remove('register-screen');
}
