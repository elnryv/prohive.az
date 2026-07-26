export function createBannerSlot(banner, { onClick } = {}) {
  const el = document.createElement('div');
  el.className = 'banner-slot';
  el.innerHTML = `
    <img src="${banner.image_path}" alt="${banner.title ?? ''}">
    <span class="banner-tag">Reklam</span>
  `;
  el.addEventListener('click', () => onClick?.(banner));
  return el;
}
