export function createListingCard(listing) {
  const el = document.createElement('div');
  el.className = `listing-card status-${listing.status ?? 'active'}`;
  el.dataset.id = listing.id;

  el.innerHTML = `
    <div class="card-top-row">
      <span class="chip active">${listing.cargo_type ?? ''}</span>
      <span class="caption-text">${listing.date_time ?? ''}</span>
    </div>
    <div class="card-route">${listing.from ?? ''} → ${listing.to ?? ''}</div>
    <div class="card-note-preview small-text">${listing.note_preview ?? ''}</div>
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
      <div class="offer-card-avatar">${initials}</div>
      <div style="flex:1">
        <div class="body-text" style="font-weight:600">${offer.driver_name ?? ''}</div>
        <div class="small-text" style="color:var(--text-muted)">${offer.vehicle ?? ''}</div>
      </div>
      <div style="text-align:right">
        <div class="h3 tabular-nums">${offer.price ?? ''} AZN</div>
        <div class="small-text" style="color:var(--text-muted)">${offer.arrival ?? ''}</div>
      </div>
    </div>
  `;

  return el;
}
