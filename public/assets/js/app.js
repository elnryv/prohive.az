// Birlikdə Getdik — app shell (vanilla JS, tək fayl)
(function () {
    'use strict';

    // ---------- "Daha çox göstər" (AJAX pagination, bölmə 6.3) ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#load-more');
        if (!btn) {
            return;
        }
        e.preventDefault();
        var baseUrl = btn.getAttribute('data-base-url');
        var query = btn.getAttribute('data-query') || '';
        var page = btn.getAttribute('data-next-page');
        var sep = query ? '&' : '';
        var url = baseUrl + '?' + query + sep + 'page=' + encodeURIComponent(page);

        btn.disabled = true;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var tmp = document.createElement('div');
                tmp.innerHTML = html;

                var newItems = tmp.querySelectorAll('#house-grid > *');
                var grid = document.getElementById('house-grid');
                if (grid) {
                    newItems.forEach(function (item) { grid.appendChild(item); });
                }

                var newBtn = tmp.querySelector('#load-more');
                if (newBtn) {
                    btn.replaceWith(newBtn);
                } else {
                    btn.remove();
                }
            })
            .catch(function () {
                btn.disabled = false;
            });
    });

    // ---------- Axtarış/filtr formlarında checkout >= checkin+1 ----------
    document.addEventListener('change', function (e) {
        if (!(e.target.matches('input[name="checkin"]'))) {
            return;
        }
        var form = e.target.closest('form');
        if (!form) {
            return;
        }
        var checkout = form.querySelector('input[name="checkout"]');
        if (checkout && e.target.value) {
            var next = new Date(e.target.value);
            next.setDate(next.getDate() + 1);
            checkout.min = next.toISOString().slice(0, 10);
        }
    });

    // ---------- WhatsApp əlaqə axını (bölmə 6.4.1) ----------
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('#wa-contact-btn');
        if (!btn) {
            return;
        }

        var houseId = btn.getAttribute('data-house-id');
        var title = btn.getAttribute('data-title') || '';
        var region = btn.getAttribute('data-region') || '';
        var phone = btn.getAttribute('data-phone') || '';
        var houseUrl = btn.getAttribute('data-house-url') || '';
        var checkin = btn.getAttribute('data-checkin') || '';
        var checkout = btn.getAttribute('data-checkout') || '';
        var guests = btn.getAttribute('data-guests') || '';

        var about = (btn.getAttribute('data-t-about') || '')
            .replace('{title}', title)
            .replace('{region}', region);
        var line1 = (btn.getAttribute('data-t-greeting') || '') + ' ' + about;

        var parts = [];
        if (checkin && checkout) {
            var range = formatDayMonth(checkin) + ' – ' + formatDayMonth(checkout);
            parts.push((btn.getAttribute('data-t-dates') || '').replace('{range}', range));
        }
        if (guests) {
            parts.push((btn.getAttribute('data-t-guests') || '').replace('{n}', guests));
        }
        var line2 = parts.join(' | ');

        var line3 = (btn.getAttribute('data-t-via') || '').replace('{link}', houseUrl);

        var message = [line1, line2, line3].filter(Boolean).join('\n');
        var waUrl = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);

        var trackUrl = btn.getAttribute('data-track-url');
        if (trackUrl && houseId) {
            try {
                fetch(trackUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ house_id: parseInt(houseId, 10) }),
                    keepalive: true,
                });
            } catch (err) {
                // sayğac uğursuz olsa belə istifadəçi WhatsApp-a getməlidir
            }
        }

        window.location.href = waUrl;
    });

    function formatDayMonth(isoDate) {
        var parts = isoDate.split('-');
        if (parts.length !== 3) {
            return isoDate;
        }
        return parts[2] + '.' + parts[1];
    }

    // ---------- Qalereya nöqtələri (sadə scroll-sync) ----------
    document.querySelectorAll('.gallery__track').forEach(function (track) {
        var dots = track.parentElement.querySelectorAll('.gallery__dot');
        if (!dots.length) {
            return;
        }
        track.addEventListener('scroll', function () {
            var index = Math.round(track.scrollLeft / track.clientWidth);
            dots.forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
            });
        });
    });
})();
