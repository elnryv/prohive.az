export function createSkeletonList(count = 3) {
  const el = document.createElement('div');
  for (let i = 0; i < count; i++) {
    const card = document.createElement('div');
    card.className = 'skeleton skeleton-card';
    el.appendChild(card);
  }
  return el;
}
