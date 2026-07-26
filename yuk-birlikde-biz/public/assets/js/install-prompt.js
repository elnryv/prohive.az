import { api } from './api.js';
import { openSheet } from './components/sheet.js';

const STORAGE_KEY = 'ybb_install_prompt_state';
const SESSION_COUNT_KEY = 'ybb_session_count';
const RETRY_DAYS = 3;
const MAX_SHOWS = 3;

let deferredPrompt = null;
let sessionCount = 0;

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
});

export function bumpSessionCount() {
  sessionCount = parseInt(localStorage.getItem(SESSION_COUNT_KEY) ?? '0', 10) + 1;
  localStorage.setItem(SESSION_COUNT_KEY, String(sessionCount));
  return sessionCount;
}

function isStandalone() {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
}

function isIos() {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
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

function showInstallSheet() {
  const state = readState();
  state.shownCount = (state.shownCount ?? 0) + 1;
  state.lastShownAt = Date.now();
  writeState(state);

  const content = document.createElement('div');

  if (isIos()) {
    content.innerHTML = `
      <h3 class="h3">Yük.Birlikdə-ni ana ekrana əlavə edin</h3>
      <p class="small-text" style="margin:12px 0 20px;color:var(--text-muted);">
        Bu, tətbiqin daha sürətli işləməsinə və bildirişləri qəbul etməyinizə kömək edəcək.
      </p>
      <div style="display:flex;flex-direction:column;gap:12px;">
        <div class="body-text">1. Paylaş ikonuna toxunun</div>
        <div class="body-text">2. "Ana ekrana əlavə et" seçin</div>
        <div class="body-text">3. "Əlavə et" təsdiqləyin</div>
      </div>
      <button type="button" class="btn btn-secondary" id="ok-btn" style="margin-top:20px;">Anladım</button>
    `;
    const { close } = openSheet(content);
    content.querySelector('#ok-btn').addEventListener('click', close);
    return;
  }

  content.innerHTML = `
    <h3 class="h3">Yük.Birlikdə-ni ana ekrana əlavə edin</h3>
    <p class="small-text" style="margin:12px 0 20px;color:var(--text-muted);">
      Bu, tətbiqin daha sürətli işləməsinə və bildirişləri qəbul etməyinizə kömək edəcək.
    </p>
    <button type="button" class="btn btn-primary" id="install-btn">İndi əlavə et</button>
    <button type="button" class="btn btn-secondary" id="later-btn" style="margin-top:8px;">Sonra</button>
  `;
  const { close } = openSheet(content);

  content.querySelector('#install-btn').addEventListener('click', async () => {
    close();
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    await deferredPrompt.userChoice;
    deferredPrompt = null;
  });
  content.querySelector('#later-btn').addEventListener('click', close);
}

export async function maybePromptInstall() {
  if (isStandalone() || sessionCount < 2) {
    return;
  }

  const config = await api.get('/config').catch(() => null);
  if (!config?.pwa_prompt_enabled) {
    return;
  }

  const state = readState();
  const shownCount = state.shownCount ?? 0;
  if (shownCount >= MAX_SHOWS) {
    return;
  }
  if (shownCount > 0 && Date.now() - (state.lastShownAt ?? 0) < RETRY_DAYS * 86400000) {
    return;
  }
  if (!isIos() && !deferredPrompt) {
    return; // Android-də beforeinstallprompt hələ tetiklənməyibsə göstərməyə dəyməz
  }

  showInstallSheet();
}
