import { api, ApiError } from '../api.js';
import { openSheet } from '../components/sheet.js';
import { setState, getState } from '../store.js';
import { navigate } from '../router.js';

export async function mount(root) {
  root.classList.add('phone-screen');
  root.innerHTML = `
    <h2 class="h2">Telefon nömrənizi daxil edin</h2>
    <p class="small-text" style="color:var(--text-muted);margin-top:8px;">
      Hesabınız varsa daxil olacaqsınız, yoxdursa yeni hesab yaradılacaq.
    </p>
    <div class="phone-row">
      <span class="phone-prefix">+994</span>
      <div class="phone-divider"></div>
      <button type="button" class="phone-operator-btn" id="operator-btn">050 ▾</button>
      <div class="phone-divider"></div>
      <input class="phone-number-input" id="number-input" inputmode="numeric" placeholder="___-__-__" maxlength="9">
    </div>
    <div class="input-error-text" id="phone-error" style="display:none;"></div>
    <button type="button" class="btn btn-primary" id="continue-btn" disabled>Davam et</button>
  `;

  const operatorBtn = root.querySelector('#operator-btn');
  const numberInput = root.querySelector('#number-input');
  const continueBtn = root.querySelector('#continue-btn');
  const errorEl = root.querySelector('#phone-error');

  let operator = '050';
  let config = null;

  api.get('/config').then((data) => {
    config = data;
  }).catch(() => {});

  operatorBtn.addEventListener('click', () => {
    const list = document.createElement('div');
    (config?.operators ?? ['010', '050', '051', '055', '060', '070', '077', '099']).forEach((prefix) => {
      const row = document.createElement('div');
      row.className = 'sheet-row' + (prefix === operator ? ' selected' : '');
      row.textContent = prefix;
      row.addEventListener('click', () => {
        operator = prefix;
        operatorBtn.textContent = `${prefix} ▾`;
        sheet.close();
        numberInput.focus();
      });
      list.appendChild(row);
    });
    const sheet = openSheet(list);
  });

  function formatDigits(digits) {
    return digits.replace(/(\d{3})(\d{0,2})(\d{0,2})/, (_, a, b, c) => [a, b, c].filter(Boolean).join('-'));
  }

  numberInput.addEventListener('input', () => {
    const digits = numberInput.value.replace(/\D/g, '').slice(0, 7);
    numberInput.value = formatDigits(digits);
    continueBtn.disabled = digits.length !== 7;
    errorEl.style.display = 'none';
  });

  continueBtn.addEventListener('click', async () => {
    const digits = numberInput.value.replace(/\D/g, '');
    if (digits.length !== 7) {
      errorEl.textContent = 'Telefon nömrəsi natamamdır.';
      errorEl.style.display = 'block';
      return;
    }

    const phone = '994' + operator + digits;
    continueBtn.disabled = true;
    continueBtn.classList.add('btn-loading');
    continueBtn.textContent = 'Yoxlanılır…';

    try {
      const { exists } = await api.post('/auth/check-phone', { phone });
      setState({ pendingPhone: phone });

      if (exists) {
        navigate('/pin');
      } else if (config && !config.reg_customer_enabled && !config.reg_driver_enabled) {
        errorEl.textContent = 'Hazırda yeni qeydiyyat müvəqqəti dayandırılıb.';
        errorEl.style.display = 'block';
      } else {
        navigate('/qeydiyyat');
      }
    } catch (e) {
      errorEl.textContent = e instanceof ApiError ? e.message : 'Xəta baş verdi.';
      errorEl.style.display = 'block';
    } finally {
      continueBtn.disabled = digits.length !== 7;
      continueBtn.classList.remove('btn-loading');
      continueBtn.textContent = 'Davam et';
    }
  });

  return () => root.classList.remove('phone-screen');
}
