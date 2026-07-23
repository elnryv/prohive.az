import { esc, formatDateTime } from '../utils.js';
import { ICONS } from './icons.js';

const STATUS_LABELS = {
  active: 'Aktiv',
  waiting: 'Təklif Gözləyir',
  negotiating: 'Danışıq Gedir',
  closed: 'Bağlandı',
  cancelled: 'Ləğv Edildi',
  expired: 'Müddəti Bitdi',
};

export function createListingCard(listing) {
  const status = listing.status ?? 'active';
  const el = document.createElement('div');
  el.className = `listing-card status-${esc(status)}`;
  el.dataset.id = listing.id;

  const thumb = listing.thumb_url
    ? `<img class="card-thumb" src="${esc(listing.thumb_url)}" alt="">`
    : `<div class="card-thumb-fallback">${ICONS.box}</div>`;

  // Elanlarım-da (customer öz elanları) status çipini göstəririk, driver
  // lentində isə hamısı aktiv/gözləyən olduğu üçün yük növünü önə çıxarırıq.
  const showStatusChip = listing.status && listing.status !== 'active';
  const chipLabel = showStatusChip ? (STATUS_LABELS[status] ?? status) : listing.cargo_type;
  const chipClass = showStatusChip ? `chip chip-status-${esc(status)}` : 'chip chip-status-active';

  el.innerHTML = `
    <div class="card-body-row">
      ${thumb}
      <div class="card-body-content">
        <div class="card-top-row">
          <span class="${chipClass}">${esc(chipLabel)}</span>
          <span class="caption-text">${esc(formatDateTime(listing.date_time))}</span>
        </div>
        <div class="card-route">${esc(listing.from)} <span style="color:var(--text-muted);font-weight:400;">→</span> ${esc(listing.to)}</div>
        <div class="card-note-preview small-text">${showStatusChip ? esc(listing.cargo_type) + ' · ' : ''}${esc(listing.note_preview)}</div>
      </div>
    </div>
  `;

  return el;
}

export function createOfferCard(offer) {
  const el = document.createElement('div');
  el.className = 'offer-card';
  el.dataset.id = offer.id;

  const initials = (offer.driver_name ?? '').split(' ').map((p) => p[0]).join('').slice(0, 2);

  el.innerHTML = `
    <div style="display:flex;align-items:center;gap:12px;">
      <div class="offer-card-avatar">${esc(initials)}</div>
      <div style="flex:1">
        <div class="body-text" style="font-weight:600">${esc(offer.driver_name)}</div>
        <div class="small-text" style="color:var(--text-muted)">${esc(offer.vehicle)}</div>
      </div>
      <div style="text-align:right">
        <div class="h3 tabular-nums">${esc(offer.price)} AZN</div>
        <div class="small-text" style="color:var(--text-muted)">${esc(offer.arrival)}</div>
      </div>
    </div>
  `;

  return el;
}
