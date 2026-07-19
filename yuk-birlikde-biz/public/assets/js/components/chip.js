export function createChip(label, { active = false, onClick } = {}) {
  const el = document.createElement('button');
  el.type = 'button';
  el.className = 'chip' + (active ? ' active' : '');
  el.textContent = label;
  el.addEventListener('click', () => onClick?.(el));
  return el;
}

export function createSegmentedTabs(labels, { onChange } = {}) {
  const el = document.createElement('div');
  el.className = 'segmented-tabs';

  const thumb = document.createElement('div');
  thumb.className = 'segmented-thumb';
  el.appendChild(thumb);

  const tabs = labels.map((label, i) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'segmented-tab' + (i === 0 ? ' active' : '');
    btn.textContent = label;
    btn.addEventListener('click', () => setActive(i));
    el.appendChild(btn);
    return btn;
  });

  function positionThumb(index) {
    const width = 100 / labels.length;
    thumb.style.width = `${width}%`;
    thumb.style.transform = `translateX(${index * 100}%)`;
  }

  function setActive(index) {
    tabs.forEach((tab, i) => tab.classList.toggle('active', i === index));
    positionThumb(index);
    onChange?.(index);
  }

  requestAnimationFrame(() => positionThumb(0));

  return { el, setActive };
}
