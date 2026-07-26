import { api } from '../api.js';
import { navigate } from '../router.js';
import { createSkeletonList } from '../components/skeleton.js';
import { createEmptyState } from '../components/empty.js';
import { esc } from '../utils.js';

function timeAgo(dateStr) {
  const diffMs = Date.now() - new Date(dateStr.replace(' ', 'T')).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return 'indicə';
  if (mins < 60) return `${mins} dəq əvvəl`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours} saat əvvəl`;
  return `${Math.floor(hours / 24)} gün əvvəl`;
}

export async function render(root) {
  root.innerHTML = `
    <h2 class="h2" style="margin-bottom:16px;">Bildirişlər</h2>
    <div id="list"></div>
  `;

  const listEl = root.querySelector('#list');
  listEl.appendChild(createSkeletonList(4));

  try {
    const { notifications } = await api.get('/notifications');
    listEl.innerHTML = '';

    if (notifications.length === 0) {
      listEl.appendChild(createEmptyState({ title: 'Bildirişiniz yoxdur.', description: '' }));
      return;
    }

    notifications.forEach((n) => {
      const row = document.createElement('div');
      row.className = 'listing-card';
      row.style.borderLeft = 'none';
      if (!n.is_read) row.style.background = 'var(--primary-soft)';
      row.innerHTML = `
        <div class="body-text" style="font-weight:600;">${esc(n.title)}</div>
        <div class="small-text" style="margin:4px 0;">${esc(n.body)}</div>
        <div class="caption-text">${timeAgo(n.created_at)}</div>
      `;
      if (n.link) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', () => navigate(n.link));
      }
      listEl.appendChild(row);
    });

    if (notifications.some((n) => !n.is_read)) {
      api.post('/notifications/read-all').catch(() => {});
    }
  } catch {
    listEl.innerHTML = '';
    listEl.appendChild(createEmptyState({ title: 'Xəta baş verdi', description: 'Yenidən cəhd edin.' }));
  }
}
