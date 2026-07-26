export function createEmptyState({ title, description, ctaLabel, onCta } = {}) {
  const el = document.createElement('div');
  el.className = 'empty-state';

  el.innerHTML = `
    <svg viewBox="0 0 96 96" fill="none" xmlns="http://www.w3.org/2000/svg">
      <circle cx="48" cy="48" r="44" stroke="currentColor" stroke-width="2"/>
      <path d="M32 48h32M48 32v32" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
    <h3 class="h3">${title}</h3>
    <p class="small-text" style="color:var(--text-muted)">${description}</p>
  `;

  if (ctaLabel) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-primary';
    btn.style.marginTop = '16px';
    btn.textContent = ctaLabel;
    btn.addEventListener('click', () => onCta?.());
    el.appendChild(btn);
  }

  return el;
}
