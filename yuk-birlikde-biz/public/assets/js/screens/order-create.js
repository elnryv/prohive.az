import { api, ApiError } from '../api.js';
import { createStepper } from '../components/stepper.js';
import { openSheet } from '../components/sheet.js';
import { showToast } from '../components/toast.js';
import { navigate } from '../router.js';
import { esc } from '../utils.js';
import { getState } from '../store.js';

const CITIES = ['Bakı', 'Sumqayıt', 'Gəncə', 'Mingəçevir', 'Naxçıvan', 'Şəki', 'Lənkəran', 'Şirvan'];
const BAKU_DISTRICTS = [
  'Yasamal', 'Nəsimi', 'Nərimanov', 'Səbail', 'Xətai', 'Nizami',
  'Binəqədi', 'Suraxanı', 'Sabunçu', 'Qaradağ', 'Xəzər', 'Pirallahı',
];

async function cropToMaxSide(file, maxSide) {
  const bitmap = await createImageBitmap(file);
  const scale = Math.min(1, maxSide / Math.max(bitmap.width, bitmap.height));
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(bitmap.width * scale);
  canvas.height = Math.round(bitmap.height * scale);
  canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
  return new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.8));
}

function readCookie(name) {
  const match = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
  return match ? decodeURIComponent(match[1]) : '';
}

export async function mount(root) {
  if (!getState().authenticated) {
    navigate('/telefon', { replace: true });
    return () => {};
  }

  const config = await api.get('/config').catch(() => ({ cargo_types: [] }));

  const data = {
    cargo_type_id: null,
    from: { city: '', district: '', street: '', note: '' },
    to: { city: '', district: '', street: '', note: '' },
    date_time: null,
    note: '',
    images: [],
  };

  const STEPS = ['cargo', 'from', 'to', 'datetime', 'note', 'images', 'review'];
  let index = 0;

  root.classList.add('register-screen');
  root.innerHTML = `
    <div style="display:flex;align-items:center;">
      <button type="button" id="back-btn" class="appbar-back">←</button>
      <div id="stepper-slot" style="flex:1"></div>
    </div>
    <div id="step-content"></div>
  `;

  const stepper = createStepper(STEPS.length);
  root.querySelector('#stepper-slot').appendChild(stepper.el);
  const contentEl = root.querySelector('#step-content');

  root.querySelector('#back-btn').addEventListener('click', () => {
    if (index === 0) {
      navigate('/ana-sehife');
      return;
    }
    goTo(index - 1);
  });

  function goTo(newIndex) {
    index = newIndex;
    stepper.setStep(index);
    renderStep(STEPS[index]);
  }
  function goNext() { goTo(index + 1); }

  function openCitySheet(onSelect) {
    const list = document.createElement('div');
    CITIES.forEach((city) => {
      const row = document.createElement('div');
      row.className = 'sheet-row';
      row.textContent = city;
      row.addEventListener('click', () => { onSelect(city); sheet.close(); });
      list.appendChild(row);
    });
    const otherRow = document.createElement('div');
    otherRow.style.marginTop = '8px';
    otherRow.innerHTML = `
      <input class="input" id="other-city-input" placeholder="Başqa şəhər adı">
      <button type="button" class="btn btn-primary" id="other-city-btn" style="margin-top:8px;">Seç</button>
    `;
    list.appendChild(otherRow);
    otherRow.querySelector('#other-city-btn').addEventListener('click', () => {
      const city = otherRow.querySelector('#other-city-input').value.trim();
      if (city) { onSelect(city); sheet.close(); }
    });
    const sheet = openSheet(list);
  }

  function openDistrictSheet(onSelect) {
    const list = document.createElement('div');
    BAKU_DISTRICTS.forEach((district) => {
      const row = document.createElement('div');
      row.className = 'sheet-row';
      row.textContent = district;
      row.addEventListener('click', () => { onSelect(district); sheet.close(); });
      list.appendChild(row);
    });
    const sheet = openSheet(list);
  }

  function renderStep(key) {
    contentEl.innerHTML = '';
    contentEl.classList.remove('screen-enter');
    void contentEl.offsetWidth;
    contentEl.classList.add('screen-enter');

    const renderers = {
      cargo: renderCargoStep,
      from: () => renderAddressStep('from', 'Yük haradan götürüləcək?'),
      to: () => renderAddressStep('to', 'Hara çatdırılacaq?'),
      datetime: renderDateTimeStep,
      note: renderNoteStep,
      images: renderImagesStep,
      review: renderReviewStep,
    };
    renderers[key]();
  }

  function renderCargoStep() {
    contentEl.innerHTML = `<h2 class="h2">Nə daşınacaq?</h2><div class="chip-row" id="cargo-list" style="flex-wrap:wrap;margin-top:16px;"></div>`;
    const list = contentEl.querySelector('#cargo-list');
    config.cargo_types.forEach((type) => {
      const chip = document.createElement('button');
      chip.type = 'button';
      chip.className = 'chip';
      chip.textContent = type.name;
      chip.addEventListener('click', () => {
        data.cargo_type_id = type.id;
        goNext();
      });
      list.appendChild(chip);
    });
  }

  function renderAddressStep(key, title) {
    const addr = data[key];
    contentEl.innerHTML = `
      <h2 class="h2">${title}</h2>
      <div class="sheet-row" id="city-row" style="margin-top:16px;">
        <span>Şəhər</span><span id="city-value" style="color:var(--text-muted)">${esc(addr.city) || 'Seçin'}</span>
      </div>
      <div class="sheet-row" id="district-row" style="display:${addr.city === 'Bakı' ? 'flex' : 'none'};">
        <span>Rayon</span><span id="district-value" style="color:var(--text-muted)">${esc(addr.district) || 'Seçin'}</span>
      </div>
      <input class="input" id="street-input" placeholder="Küçə" value="${esc(addr.street)}" style="margin-top:12px;">
      <input class="input" id="note-input" placeholder="Əlavə qeyd (bina, blok, mərtəbə...)" value="${esc(addr.note)}" style="margin-top:12px;">
      <div class="input-error-text" id="city-error" style="display:none;">Şəhər tələb olunur.</div>
      <button type="button" class="btn btn-primary" id="continue-btn" style="margin-top:16px;">Davam et</button>
    `;

    contentEl.querySelector('#city-row').addEventListener('click', () => {
      openCitySheet((city) => {
        addr.city = city;
        contentEl.querySelector('#city-value').textContent = city;
        contentEl.querySelector('#district-row').style.display = city === 'Bakı' ? 'flex' : 'none';
        if (city !== 'Bakı') addr.district = '';
      });
    });
    contentEl.querySelector('#district-row').addEventListener('click', () => {
      openDistrictSheet((district) => {
        addr.district = district;
        contentEl.querySelector('#district-value').textContent = district;
      });
    });
    contentEl.querySelector('#street-input').addEventListener('input', (e) => { addr.street = e.target.value; });
    contentEl.querySelector('#note-input').addEventListener('input', (e) => { addr.note = e.target.value; });

    contentEl.querySelector('#continue-btn').addEventListener('click', () => {
      if (!addr.city) {
        contentEl.querySelector('#city-error').style.display = 'block';
        return;
      }
      goNext();
    });
  }

  function renderDateTimeStep() {
    const today = new Date();
    const tomorrow = new Date(Date.now() + 86400000);
    const fmt = (d) => d.toISOString().slice(0, 10);

    contentEl.innerHTML = `
      <h2 class="h2">Nə vaxt daşınsın?</h2>
      <div class="chip-row" style="margin-top:16px;">
        <button type="button" class="chip" id="today-chip">Bu gün</button>
        <button type="button" class="chip" id="tomorrow-chip">Sabah</button>
      </div>
      <input class="input" type="date" id="date-input" style="margin-top:16px;" min="${fmt(today)}">
      <input class="input" type="time" id="time-input" step="1800" style="margin-top:12px;" value="12:00">
      <div class="input-error-text" id="date-error" style="display:none;">Keçmiş tarix seçilə bilməz.</div>
      <button type="button" class="btn btn-primary" id="continue-btn" style="margin-top:16px;">Davam et</button>
    `;

    const dateInput = contentEl.querySelector('#date-input');
    const timeInput = contentEl.querySelector('#time-input');
    contentEl.querySelector('#today-chip').addEventListener('click', () => { dateInput.value = fmt(today); });
    contentEl.querySelector('#tomorrow-chip').addEventListener('click', () => { dateInput.value = fmt(tomorrow); });

    contentEl.querySelector('#continue-btn').addEventListener('click', () => {
      if (!dateInput.value || !timeInput.value) {
        contentEl.querySelector('#date-error').textContent = 'Tarix və saatı seçin.';
        contentEl.querySelector('#date-error').style.display = 'block';
        return;
      }
      const selected = new Date(`${dateInput.value}T${timeInput.value}:00`);
      if (selected.getTime() < Date.now()) {
        contentEl.querySelector('#date-error').textContent = 'Keçmiş tarix seçilə bilməz.';
        contentEl.querySelector('#date-error').style.display = 'block';
        return;
      }
      data.date_time = `${dateInput.value} ${timeInput.value}:00`;
      goNext();
    });
  }

  function renderNoteStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Sürücüyə qeydiniz</h2>
      <textarea class="input" id="note-input" style="height:120px;padding-top:12px;margin-top:16px;" maxlength="500"
        placeholder="Məsələn: Binada lift yoxdur, 5-ci mərtəbədir, köməkçi lazımdır, əşyalar qablaşdırılıb...">${esc(data.note)}</textarea>
      <div class="caption-text" id="char-count" style="text-align:right;">${data.note.length}/500</div>
      <button type="button" class="btn btn-primary" id="continue-btn">Davam et</button>
      <button type="button" class="btn btn-secondary" id="skip-btn" style="margin-top:12px;">Keç</button>
    `;
    const textarea = contentEl.querySelector('#note-input');
    textarea.addEventListener('input', () => {
      data.note = textarea.value;
      contentEl.querySelector('#char-count').textContent = `${textarea.value.length}/500`;
    });
    contentEl.querySelector('#continue-btn').addEventListener('click', goNext);
    contentEl.querySelector('#skip-btn').addEventListener('click', () => { data.note = ''; goNext(); });
  }

  function renderImagesStep() {
    contentEl.innerHTML = `
      <h2 class="h2">Yükün şəkli (istəyə bağlı)</h2>
      <p class="small-text" style="color:var(--text-muted);margin:8px 0 16px;">Şəkil sürücülərə daha dəqiq qiymət verməyə kömək edir.</p>
      <div id="thumbs" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;"></div>
      <label class="chip">Şəkil əlavə et<input type="file" accept="image/*" id="file-input" hidden multiple></label>
      <button type="button" class="btn btn-primary" id="continue-btn" style="margin-top:16px;">Davam et</button>
      <button type="button" class="btn btn-secondary" id="skip-btn" style="margin-top:12px;">Keç</button>
    `;

    const thumbs = contentEl.querySelector('#thumbs');
    function renderThumbs() {
      thumbs.innerHTML = '';
      data.images.forEach((img, i) => {
        const wrap = document.createElement('div');
        wrap.style.cssText = 'position:relative;width:64px;height:64px;';
        wrap.innerHTML = `
          <img src="${img.url}" style="width:100%;height:100%;object-fit:cover;border-radius:12px;">
          <button type="button" style="position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:50%;
            border:none;background:var(--error);color:#fff;font-size:12px;line-height:1;">×</button>
        `;
        wrap.querySelector('button').addEventListener('click', () => {
          data.images.splice(i, 1);
          renderThumbs();
        });
        thumbs.appendChild(wrap);
      });
    }
    renderThumbs();

    contentEl.querySelector('#file-input').addEventListener('change', async (e) => {
      const files = [...e.target.files].slice(0, 5 - data.images.length);
      for (const file of files) {
        const blob = await cropToMaxSide(file, 1600);
        data.images.push({ blob, url: URL.createObjectURL(blob) });
      }
      renderThumbs();
    });

    contentEl.querySelector('#continue-btn').addEventListener('click', goNext);
    contentEl.querySelector('#skip-btn').addEventListener('click', goNext);
  }

  function renderReviewStep() {
    const addrText = (a) => `${esc(a.city)}${a.district ? ', ' + esc(a.district) : ''}${a.street ? ', ' + esc(a.street) : ''}`;
    const cargoName = config.cargo_types.find((c) => c.id === data.cargo_type_id)?.name ?? '';

    contentEl.innerHTML = `
      <h2 class="h2">İcmal</h2>
      <div class="listing-card" style="margin-top:16px;">
        <div class="card-top-row"><span>Yük növü</span><button type="button" class="chip" data-goto="0">Dəyiş</button></div>
        <div class="body-text">${esc(cargoName)}</div>
      </div>
      <div class="listing-card">
        <div class="card-top-row"><span>Marşrut</span><button type="button" class="chip" data-goto="1">Dəyiş</button></div>
        <div class="body-text">${addrText(data.from)} → ${addrText(data.to)}</div>
      </div>
      <div class="listing-card">
        <div class="card-top-row"><span>Tarix</span><button type="button" class="chip" data-goto="3">Dəyiş</button></div>
        <div class="body-text">${esc(data.date_time)}</div>
      </div>
      ${data.note ? `<div class="listing-card"><div class="card-top-row"><span>Qeyd</span><button type="button" class="chip" data-goto="4">Dəyiş</button></div><div class="body-text">${esc(data.note)}</div></div>` : ''}
      <div class="input-error-text" id="submit-error" style="display:none;"></div>
      <button type="button" class="btn btn-primary" id="publish-btn" style="margin-top:16px;">Elanı paylaş</button>
    `;

    contentEl.querySelectorAll('[data-goto]').forEach((btn) => {
      btn.addEventListener('click', () => goTo(parseInt(btn.dataset.goto, 10)));
    });

    contentEl.querySelector('#publish-btn').addEventListener('click', async () => {
      const btn = contentEl.querySelector('#publish-btn');
      const errorEl = contentEl.querySelector('#submit-error');
      btn.disabled = true;
      btn.classList.add('btn-loading');

      try {
        const { order } = await api.post('/orders', {
          cargo_type_id: data.cargo_type_id,
          from: data.from,
          to: data.to,
          date_time: data.date_time,
          note: data.note || undefined,
        });

        for (const img of data.images) {
          const form = new FormData();
          form.append('images[]', img.blob, 'image.jpg');
          await fetch(`/api/v1/orders/${order.id}/images`, {
            method: 'POST',
            body: form,
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': readCookie('ybb_csrf') },
          });
        }

        showToast('Elanınız dərc olundu! Təkliflər gələn kimi xəbər verəcəyik.');
        navigate(`/elan/${order.id}`, { replace: true });
      } catch (e) {
        errorEl.textContent = e instanceof ApiError ? e.message : 'Xəta baş verdi.';
        errorEl.style.display = 'block';
        btn.disabled = false;
        btn.classList.remove('btn-loading');
      }
    });
  }

  renderStep(STEPS[0]);

  return () => root.classList.remove('register-screen');
}
