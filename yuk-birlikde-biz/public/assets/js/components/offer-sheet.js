import { api, ApiError } from '../api.js';
import { openSheet } from './sheet.js';
import { showToast } from './toast.js';
import { esc } from '../utils.js';

const ARRIVAL_OPTIONS = ['1 saat ərzində', '2–3 saat', 'Bu gün', 'Sabah'];

export function openOfferSheet(orderId, { onSubmitted, existingOffer } = {}) {
  const content = document.createElement('div');
  content.innerHTML = `
    <h3 class="h3" style="margin-bottom:16px;">Təklif göndər</h3>
    <div class="input-group">
      <div style="display:flex;align-items:center;gap:8px;border:1px solid var(--border);border-radius:var(--r-input);padding:0 16px;height:56px;">
        <input class="input h2 tabular-nums" id="price-input" style="border:none;padding:0;height:auto;"
               inputmode="decimal" placeholder="0" value="${esc(existingOffer?.price)}">
        <span class="body-text" style="color:var(--text-muted);">AZN</span>
      </div>
      <div class="input-error-text" id="price-error" style="display:none;">Qiyməti daxil edin.</div>
    </div>
    <div class="small-text" style="margin:12px 0 8px;color:var(--text-muted);">Gələ biləcəyi vaxt</div>
    <div class="chip-row" id="arrival-chips"></div>
    <div class="input-error-text" id="arrival-error" style="display:none;">Gələ biləcəyiniz vaxtı seçin.</div>
    <textarea class="input" id="note-input" style="height:80px;margin-top:16px;padding-top:12px;" maxlength="200"
              placeholder="Məsələn: Köməkçim var, lift olmasa da problem deyil.">${esc(existingOffer?.note)}</textarea>
    <button type="button" class="btn btn-primary" id="submit-btn" style="margin-top:16px;">Təklif göndər</button>
  `;

  const { close } = openSheet(content);

  const chipRow = content.querySelector('#arrival-chips');
  let arrival = existingOffer?.arrival_time ?? null;
  ARRIVAL_OPTIONS.forEach((label) => {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'chip' + (arrival === label ? ' active' : '');
    chip.textContent = label;
    chip.addEventListener('click', () => {
      arrival = label;
      [...chipRow.children].forEach((c) => c.classList.toggle('active', c === chip));
      content.querySelector('#arrival-error').style.display = 'none';
    });
    chipRow.appendChild(chip);
  });

  const submitBtn = content.querySelector('#submit-btn');
  submitBtn.addEventListener('click', async () => {
    const price = parseFloat(content.querySelector('#price-input').value);
    const note = content.querySelector('#note-input').value.trim();

    if (!price || price <= 0) {
      content.querySelector('#price-error').style.display = 'block';
      return;
    }
    if (!arrival) {
      content.querySelector('#arrival-error').style.display = 'block';
      return;
    }

    submitBtn.disabled = true;
    submitBtn.classList.add('btn-loading');
    try {
      if (existingOffer) {
        await api.patch(`/offers/${existingOffer.id}`, { price, arrival_time: arrival, note });
      } else {
        await api.post('/offers', { order_id: orderId, price, arrival_time: arrival, note });
      }
      showToast('Təklifiniz göndərildi.');
      close();
      onSubmitted?.();
    } catch (e) {
      showToast(e instanceof ApiError ? e.message : 'Xəta baş verdi.');
      submitBtn.disabled = false;
      submitBtn.classList.remove('btn-loading');
    }
  });
}
