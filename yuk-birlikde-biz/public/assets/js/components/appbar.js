export function createAppBar({ title, onBack, action } = {}) {
  const el = document.createElement('div');
  el.className = 'appbar';

  if (onBack) {
    const backBtn = document.createElement('button');
    backBtn.className = 'appbar-back';
    backBtn.innerHTML = '←';
    backBtn.addEventListener('click', onBack);
    el.appendChild(backBtn);
  } else {
    const spacer = document.createElement('div');
    spacer.style.width = '44px';
    el.appendChild(spacer);
  }

  const titleEl = document.createElement('div');
  titleEl.className = 'appbar-title h3';
  titleEl.textContent = title ?? '';
  el.appendChild(titleEl);

  const actionEl = document.createElement('div');
  actionEl.className = 'appbar-action';
  if (action) {
    actionEl.appendChild(action);
  }
  el.appendChild(actionEl);

  const scrollTarget = () => window.scrollY > 8;
  window.addEventListener('scroll', () => {
    el.classList.toggle('scrolled', scrollTarget());
  });

  return el;
}
