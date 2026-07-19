// PinPad komponenti — Hissə 2.5. Sistem klaviaturası çağırılmır, 3×4 grid.
export function createPinPad({ length = 4, onComplete } = {}) {
  let value = '';
  let disabled = false;

  const el = document.createElement('div');

  const dotsEl = document.createElement('div');
  dotsEl.className = 'pinpad-dots';
  for (let i = 0; i < length; i++) {
    const dot = document.createElement('div');
    dot.className = 'pinpad-dot';
    dotsEl.appendChild(dot);
  }

  const gridEl = document.createElement('div');
  gridEl.className = 'pinpad-grid';
  const keys = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '', '0', '⌫'];
  keys.forEach((key) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'pinpad-key' + (key === '' ? ' empty' : '');
    btn.textContent = key;
    if (key !== '') {
      btn.addEventListener('click', () => handleKey(key));
    }
    gridEl.appendChild(btn);
  });

  el.appendChild(dotsEl);
  el.appendChild(gridEl);

  function renderDots() {
    [...dotsEl.children].forEach((dot, i) => {
      dot.className = 'pinpad-dot' + (i < value.length ? ' filled' : '');
    });
  }

  function handleKey(key) {
    if (disabled) return;
    if (key === '⌫') {
      value = value.slice(0, -1);
      renderDots();
      return;
    }
    if (value.length >= length) return;
    value += key;
    renderDots();
    if (value.length === length) {
      onComplete?.(value);
    }
  }

  function reset() {
    value = '';
    renderDots();
  }

  function shakeError() {
    [...dotsEl.children].forEach((dot) => dot.classList.add('error'));
    dotsEl.classList.add('pinpad-shake');
    setTimeout(() => {
      dotsEl.classList.remove('pinpad-shake');
      reset();
      [...dotsEl.children].forEach((dot) => dot.classList.remove('error'));
    }, 400);
  }

  function setDisabled(next) {
    disabled = next;
    gridEl.classList.toggle('is-disabled', next);
    gridEl.style.opacity = next ? '0.4' : '1';
  }

  return { el, reset, shakeError, setDisabled, getValue: () => value };
}
