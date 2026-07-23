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
      <span class="phone-flag" aria-hidden="true">
        <svg viewBox="0 0 900 600" width="28" height="20">
          <rect width="900" height="200" y="0" fill="#0AADE3"/>
          <rect width="900" height="200" y="200" fill="#EF3340"/>
          <rect width="900" height="200" y="400" fill="#3EA72D"/>
          <circle cx="430" cy="300" r="90" fill="#FFFFFF"/>
          <circle cx="460" cy="300" r="76" fill="#EF3340"/>
          <rect x="526" y="266" width="68" height="68" fill="#FFFFFF"/>
          <rect x="526" y="266" width="68" height="68" fill="#FFFFFF" transform="rotate(45 560 300)"/>
        </svg>
      </span>
      <span class="phone-prefix">+994</span>
      <div class="phone-divider"></div>
      <button type="button" class="phone-operator-btn" id="operator-btn">
        <span id="operator-label">050</span>
        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="phone-divider"></div>
      <input class="phone-number-input" id="number-input" inputmode="numeric" placeholder="___-__-__" maxlength="9">
    </div>
    <div class="input-error-text" id="phone-error" style="display:none;"></div>
    <button type="button" class="btn btn-primary" id="continue-btn" disabled>Davam et</button>
  `;

  const operatorBtn = root.querySelector('#operator-btn');
  const operatorLabel = root.querySelector('#operator-label');
  const numberInput = root.querySelector('#number-input');
  const continueBtn = root.querySelector('#continue-btn');
  const errorEl = root.querySelector('#phone-error');

  let operator = '050';
  let config = null;

  api.get('/config').then((data) => {
    config = data;
  }).catch(() => {});

  const checkIcon = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';

  operatorBtn.addEventListener('click', () => {
    operatorBtn.classList.add('open');
    const list = document.createElement('div');
    (config?.operators ?? ['010', '050', '051', '055', '060', '070', '077', '099']).forEach((prefix) => {
      const row = document.createElement('div');
      row.className = 'sheet-row' + (prefix === operator ? ' selected' : '');
      row.innerHTML = `<span>${prefix}</span>` + (prefix === operator ? checkIcon : '');
      row.addEventListener('click', () => {
        operator = prefix;
        operatorLabel.textContent = prefix;
        sheet.close();
        numberInput.focus();
      });
      list.appendChild(row);
    });
    const sheet = openSheet(list, { onClose: () => operatorBtn.classList.remove('open') });
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

    // Operator prefix "050" kimi lokal formatda göstərilir, amma +994 ölkə kodu ilə
    // birləşəndə aparıcı sıfır düşür: +994 50 123-45-67 → 99450 1234567 (12 rəqəm).
    const phone = '994' + operator.replace(/^0/, '') + digits;
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
