// BottomSheet komponenti — Hissə 2.5. Sürüklə-bağla + backdrop klik ilə bağlanma.
// Açıq ikən back gesture/hardware back əvvəlcə sheet-i bağlayır, ekranı yox
// (Hissə 2.6: "sheet açıqdırsa əvvəl sheet bağlanır, sonra ekran geri gedir").
export function openSheet(contentEl, { onClose } = {}) {
  const backdrop = document.createElement('div');
  backdrop.className = 'sheet-backdrop';

  const sheet = document.createElement('div');
  sheet.className = 'sheet';

  const handle = document.createElement('div');
  handle.className = 'sheet-handle';
  sheet.appendChild(handle);
  sheet.appendChild(contentEl);

  document.body.appendChild(backdrop);
  document.body.appendChild(sheet);
  document.body.style.overflow = 'hidden';

  requestAnimationFrame(() => {
    backdrop.classList.add('open');
    sheet.classList.add('open');
  });

  history.pushState({ ybbSheet: true }, '', location.href);
  let poppedByHistory = false;
  function onPopState() {
    poppedByHistory = true;
    close();
  }
  window.addEventListener('popstate', onPopState);

  let closed = false;
  function close() {
    if (closed) return;
    closed = true;

    window.removeEventListener('popstate', onPopState);
    window.removeEventListener('pointermove', onPointerMove);
    window.removeEventListener('pointerup', onPointerUp);
    if (!poppedByHistory) {
      history.back();
    }

    sheet.classList.add('closing');
    sheet.classList.remove('open');
    backdrop.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(() => {
      backdrop.remove();
      sheet.remove();
      onClose?.();
    }, 240);
  }

  backdrop.addEventListener('click', close);

  let startY = null;
  function onPointerMove(e) {
    if (startY === null) return;
    const delta = e.clientY - startY;
    if (delta > 0) sheet.style.transform = `translateY(${delta}px)`;
  }
  function onPointerUp(e) {
    if (startY === null) return;
    const delta = e.clientY - startY;
    sheet.style.transform = '';
    if (delta > sheet.offsetHeight * 0.3) {
      close();
    }
    startY = null;
  }
  handle.addEventListener('pointerdown', (e) => { startY = e.clientY; });
  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);

  return { close, sheetEl: sheet };
}
