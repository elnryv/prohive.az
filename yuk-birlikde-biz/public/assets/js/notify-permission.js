import { api } from './api.js';
import { openSheet } from './components/sheet.js';

const STORAGE_KEY = 'ybb_notif_prompt_state';
const RETRY_DAYS = 7;
const MAX_SHOWS = 2; // ilk göstəriş + 7 gündən sonra bir dəfə nəzakətli təkrar

const MESSAGES = {
  customer_first_order: {
    title: 'Təkliflər gələndə sizə xəbər verək?',
    body: 'Sürücülərdən yeni təklif gələn kimi bildiriş alacaqsınız.',
  },
  driver_first_feed: {
    title: 'Yeni elanlardan anında xəbərdar olun?',
    body: 'Marşrutunuza uyğun yeni elan dərc olunanda bildiriş alacaqsınız.',
  },
};

function urlBase64ToUint8Array(base64url) {
  const padded = base64url.padEnd(base64url.length + (4 - (base64url.length % 4)) % 4, '=');
  const base64 = padded.replace(/-/g, '+').replace(/_/g, '/');
  const raw = atob(base64);
  return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

function readState() {
  try {
    return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '{}');
  } catch {
    return {};
  }
}
function writeState(state) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

async function subscribeToPush(vapidPublicKey) {
  if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
    return;
  }
  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });
    await api.post('/push', subscription.toJSON());
  } catch {
    // Cihaz/brauzer push dəstəkləmirsə səssizcə keç.
  }
}

function showExplanationSheet(context, vapidPublicKey) {
  // Göstərilmə vaxtında sayılır (bağlanma üsulundan asılı olmayaraq: düymə,
  // backdrop klik və ya geri gesture — hamısı eyni "bir dəfə göstərildi" deməkdir).
  const state = readState();
  state.shownCount = (state.shownCount ?? 0) + 1;
  state.dismissedAt = Date.now();
  writeState(state);

  const message = MESSAGES[context];
  const content = document.createElement('div');
  content.innerHTML = `
    <h3 class="h3">${message.title}</h3>
    <p class="small-text" style="margin:12px 0 20px;color:var(--text-muted);">${message.body}</p>
    <button type="button" class="btn btn-primary" id="allow-btn">İcazə ver</button>
    <button type="button" class="btn btn-secondary" id="later-btn" style="margin-top:8px;">Sonra</button>
  `;
  const { close } = openSheet(content);

  content.querySelector('#allow-btn').addEventListener('click', async () => {
    close();
    if (typeof Notification === 'undefined') return;
    const permission = await Notification.requestPermission();
    if (permission === 'granted') {
      await subscribeToPush(vapidPublicKey);
    }
  });

  content.querySelector('#later-btn').addEventListener('click', close);
}

export async function maybePromptNotificationPermission(context) {
  if (typeof Notification === 'undefined' || !('serviceWorker' in navigator)) {
    return;
  }

  if (Notification.permission === 'granted') {
    const config = await api.get('/config').catch(() => null);
    if (config?.vapid_public_key) {
      await subscribeToPush(config.vapid_public_key);
    }
    return;
  }

  if (Notification.permission === 'denied') {
    return; // brauzer səviyyəsində bloklanıb — yalnız istifadəçi özü ayarlardan aça bilər
  }

  const state = readState();
  const shownCount = state.shownCount ?? 0;

  if (shownCount >= MAX_SHOWS) {
    return;
  }
  if (shownCount === 1 && Date.now() - (state.dismissedAt ?? 0) < RETRY_DAYS * 86400000) {
    return;
  }

  const config = await api.get('/config').catch(() => null);
  if (!config?.vapid_public_key) {
    return;
  }

  showExplanationSheet(context, config.vapid_public_key);
}
