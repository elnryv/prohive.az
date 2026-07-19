import { api, ApiError } from '../api.js';
import { createPinPad } from '../components/pinpad.js';
import { openSheet } from '../components/sheet.js';
import { getState, setState } from '../store.js';
import { navigate } from '../router.js';

function maskPhone(phone) {
  // 994501234567 → +994 50 123-45-67
  return `+${phone.slice(0, 3)} ${phone.slice(3, 5)} ${phone.slice(5, 8)}-${phone.slice(8, 10)}-${phone.slice(10, 12)}`;
}

export async function mount(root) {
  const phone = getState().pendingPhone;
  if (!phone) {
    navigate('/telefon', { replace: true });
    return () => {};
  }

  root.classList.add('pin-screen');
  root.innerHTML = `
    <h2 class="h2">Xoş gəldiniz!</h2>
    <div class="pin-phone-row small-text" style="color:var(--text-muted)">
      <span>${maskPhone(phone)}</span>
      <button type="button" class="pin-change-link" id="change-link">Dəyiş</button>
    </div>
    <div id="pinpad-slot"></div>
    <div class="pin-lockout" id="lockout-text" style="display:none;"></div>
    <button type="button" class="pin-forgot-link" id="forgot-link">PIN kodu unutmusunuz?</button>
  `;

  root.querySelector('#change-link').addEventListener('click', () => navigate('/telefon'));

  const lockoutEl = root.querySelector('#lockout-text');

  const pinpad = createPinPad({
    length: 4,
    onComplete: async (pin) => {
      pinpad.setDisabled(true);
      try {
        const { user } = await api.post('/auth/login', { phone, pin });
        setState({ authenticated: true, user });
        navigate('/ana-sehife', { replace: true });
      } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Xəta baş verdi.';
        if (e instanceof ApiError && e.code === 'PIN_LOCKED') {
          lockoutEl.textContent = message;
          lockoutEl.style.display = 'block';
          pinpad.setDisabled(true);
          return;
        }
        pinpad.shakeError();
      } finally {
        if (!(lockoutEl.style.display === 'block')) {
          pinpad.setDisabled(false);
        }
      }
    },
  });
  root.querySelector('#pinpad-slot').appendChild(pinpad.el);

  root.querySelector('#forgot-link').addEventListener('click', async () => {
    const config = await api.get('/config').catch(() => ({ whatsapp_number: '' }));
    const content = document.createElement('div');
    content.innerHTML = `
      <p class="body-text">PIN bərpası üçün dəstəklə əlaqə saxlayın</p>
      <a class="btn btn-primary" style="margin-top:16px;display:flex;text-decoration:none;"
         href="https://wa.me/${config.whatsapp_number.replace(/\D/g, '')}" target="_blank">WhatsApp ilə yaz</a>
    `;
    openSheet(content);
  });

  return () => root.classList.remove('pin-screen');
}
