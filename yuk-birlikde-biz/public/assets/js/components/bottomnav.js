export function createBottomNav(tabs, { active = 0, onChange } = {}) {
  const el = document.createElement('div');
  el.className = 'bottomnav';

  tabs.forEach((tab, i) => {
    const btn = document.createElement('button');
    btn.className = 'bottomnav-tab' + (i === active ? ' active' : '');
    btn.innerHTML = `<span>${tab.icon ?? ''}</span><span>${tab.label}</span>`;

    if (tab.badge) {
      const badge = document.createElement('span');
      badge.className = 'bottomnav-badge';
      badge.textContent = tab.badge;
      btn.appendChild(badge);
    }

    btn.addEventListener('click', () => {
      [...el.children].forEach((child, idx) => child.classList.toggle('active', idx === i));
      onChange?.(i);
    });
    el.appendChild(btn);
  });

  return el;
}
