export function createBottomNav(tabs, { active = 0, onChange } = {}) {
  const el = document.createElement('div');
  el.className = 'bottomnav';

  const badges = [];

  const buttons = tabs.map((tab, i) => {
    const btn = document.createElement('button');
    btn.className = 'bottomnav-tab' + (i === active ? ' active' : '');
    btn.innerHTML = `<span>${tab.icon ?? ''}</span><span>${tab.label}</span>`;

    const badge = document.createElement('span');
    badge.className = 'bottomnav-badge';
    badge.style.display = 'none';
    btn.appendChild(badge);
    badges.push(badge);

    btn.addEventListener('click', () => {
      setActive(i);
      onChange?.(i);
    });
    el.appendChild(btn);
    return btn;
  });

  function setActive(index) {
    buttons.forEach((btn, idx) => btn.classList.toggle('active', idx === index));
  }

  function setBadge(index, value) {
    const badge = badges[index];
    if (!badge) return;
    if (value) {
      badge.textContent = value;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  return { el, setActive, setBadge };
}
