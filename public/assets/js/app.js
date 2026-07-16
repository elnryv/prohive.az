// Birlikdə Getdik — app shell (vanilla JS, tək fayl)
(function () {
    'use strict';

    // ---------- Service Worker qeydiyyatı (bölmə 11.2) ----------
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function () {
                // sw.js əlçatan deyilsə səssizcə davam edir
            });
        });
    }

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

    // ---------- Ev sahibi: foto yükləmə/sil/üz qabığı (bölmə 7.3 addım 4) ----------
    var photoGrid = document.getElementById('photo-grid');
    var photoInput = document.getElementById('photo-input');

    function updateSubmitButtonState() {
        var submitBtn = document.getElementById('submit-for-approval-btn');
        if (!submitBtn || !photoGrid) {
            return;
        }
        var photoCount = photoGrid.querySelectorAll('.photo-tile img').length;
        submitBtn.disabled = photoCount < 4;
    }

    function makePhotoTile(data, grid) {
        var tile = document.createElement('div');
        tile.className = 'photo-tile';
        tile.setAttribute('data-photo-id', data.id);

        var media = document.createElement(data.is_video ? 'video' : 'img');
        media.src = data.url;
        if (data.is_video) {
            media.muted = true;
        }
        tile.appendChild(media);

        if (data.is_cover) {
            var coverBadge = document.createElement('span');
            coverBadge.className = 'badge badge--verified photo-tile__cover-badge';
            coverBadge.textContent = grid.getAttribute('data-label-cover') || '';
            tile.appendChild(coverBadge);
        }

        var pendingBadge = document.createElement('span');
        pendingBadge.className = 'badge badge--warn photo-tile__pending-badge';
        pendingBadge.textContent = grid.getAttribute('data-label-pending') || '';
        tile.appendChild(pendingBadge);

        var actions = document.createElement('div');
        actions.className = 'photo-tile__actions';
        if (!data.is_video) {
            var coverBtn = document.createElement('button');
            coverBtn.type = 'button';
            coverBtn.className = 'photo-set-cover';
            coverBtn.setAttribute('data-photo-id', data.id);
            coverBtn.textContent = grid.getAttribute('data-label-cover') || '';
            actions.appendChild(coverBtn);
        }
        var delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'photo-delete';
        delBtn.setAttribute('data-photo-id', data.id);
        delBtn.textContent = grid.getAttribute('data-label-delete') || '';
        actions.appendChild(delBtn);
        tile.appendChild(actions);

        return tile;
    }

    if (photoInput && photoGrid) {
        photoInput.addEventListener('change', function () {
            var file = photoInput.files[0];
            if (!file) {
                return;
            }
            var formData = new FormData();
            formData.append('file', file);
            formData.append('csrf_token', photoGrid.getAttribute('data-csrf'));

            fetch(photoGrid.getAttribute('data-upload-url'), { method: 'POST', body: formData })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (!data.ok) {
                        alert(data.error || 'Yükləmə uğursuz oldu.');
                        return;
                    }
                    photoGrid.appendChild(makePhotoTile(data, photoGrid));
                    updateSubmitButtonState();
                    photoInput.value = '';
                })
                .catch(function () {
                    alert('Yükləmə uğursuz oldu.');
                    photoInput.value = '';
                });
        });

        photoGrid.addEventListener('click', function (e) {
            var delBtn = e.target.closest('.photo-delete');
            var coverBtn = e.target.closest('.photo-set-cover');
            var csrf = photoGrid.getAttribute('data-csrf');
            var baseUrl = photoGrid.getAttribute('data-upload-url');

            if (delBtn) {
                var photoId = delBtn.getAttribute('data-photo-id');
                fetch(baseUrl + '/' + photoId + '/sil', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=' + encodeURIComponent(csrf),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            var tile = photoGrid.querySelector('[data-photo-id="' + photoId + '"]');
                            if (tile) {
                                tile.remove();
                            }
                            updateSubmitButtonState();
                        }
                    });
            }

            if (coverBtn) {
                var coverId = coverBtn.getAttribute('data-photo-id');
                fetch(baseUrl + '/' + coverId + '/cover', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'csrf_token=' + encodeURIComponent(csrf),
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            photoGrid.querySelectorAll('.photo-tile__cover-badge').forEach(function (b) { b.remove(); });
                            var tile = photoGrid.querySelector('[data-photo-id="' + coverId + '"]');
                            if (tile) {
                                var badge = document.createElement('span');
                                badge.className = 'badge badge--verified photo-tile__cover-badge';
                                badge.textContent = photoGrid.getAttribute('data-label-cover') || '';
                                tile.insertBefore(badge, tile.firstChild.nextSibling);
                            }
                        }
                    });
            }
        });

        updateSubmitButtonState();
    }

    // ---------- Ev sahibi: təqvim (bölmə 7.4) ----------
    var ownerCal = document.getElementById('owner-cal');
    if (ownerCal) {
        ownerCal.addEventListener('click', function (e) {
            var day = e.target.closest('.cal-day');
            if (!day || day.disabled) {
                return;
            }
            var csrf = ownerCal.getAttribute('data-csrf');
            fetch(ownerCal.getAttribute('data-toggle-url'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=' + encodeURIComponent(csrf) + '&date=' + encodeURIComponent(day.getAttribute('data-date')),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        day.classList.toggle('is-busy', data.busy);
                    }
                });
        });
    }

    var rangeForm = document.getElementById('calendar-range-form');
    if (rangeForm) {
        rangeForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var busy = (e.submitter && e.submitter.getAttribute('data-busy')) || '1';
            var from = rangeForm.querySelector('[name="from"]').value;
            var to = rangeForm.querySelector('[name="to"]').value;
            if (!from || !to) {
                return;
            }
            var csrf = rangeForm.getAttribute('data-csrf');
            fetch(rangeForm.getAttribute('data-range-url'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=' + encodeURIComponent(csrf) + '&from=' + encodeURIComponent(from)
                    + '&to=' + encodeURIComponent(to) + '&busy=' + encodeURIComponent(busy),
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        window.location.reload();
                    }
                });
        });
    }

    // ---------- Ev sahibi: canlı SSE (bölmə 11.4) ----------
    var toastContainer = document.getElementById('toast-container');
    if (toastContainer && typeof EventSource !== 'undefined') {
        function showToast(message) {
            var toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            toastContainer.appendChild(toast);
            setTimeout(function () {
                toast.classList.add('toast--visible');
            }, 10);
            setTimeout(function () {
                toast.classList.remove('toast--visible');
                setTimeout(function () { toast.remove(); }, 300);
            }, 5000);
        }

        function bumpStat(houseId, statKey) {
            var row = document.querySelector('.owner-house-row[data-house-id="' + houseId + '"]');
            if (!row) {
                return;
            }
            var el = row.querySelector('.stat-value[data-stat="' + statKey + '"]');
            if (el) {
                el.textContent = String((parseInt(el.textContent, 10) || 0) + 1);
                el.closest('.stats-mini').hidden = false;
            }
        }

        function updateHouseStatus(houseId, status, reason) {
            var list = document.getElementById('owner-house-list');
            var row = document.querySelector('.owner-house-row[data-house-id="' + houseId + '"]');
            if (!row || !list) {
                return;
            }
            var badge = row.querySelector('[data-status-badge]');
            if (badge) {
                badge.className = 'badge badge--house-' + status;
                badge.textContent = status === 'approved'
                    ? list.getAttribute('data-label-approved')
                    : list.getAttribute('data-label-rejected');
            }
            var reasonEl = row.querySelector('[data-reject-reason]');
            if (reasonEl) {
                if (status === 'rejected' && reason) {
                    reasonEl.textContent = (list.getAttribute('data-label-reject-reason') || '%s').replace('%s', reason);
                    reasonEl.hidden = false;
                } else {
                    reasonEl.hidden = true;
                }
            }
        }

        var es = new EventSource(toastContainer.getAttribute('data-sse-url'));
        es.addEventListener('view', function (e) {
            var data = JSON.parse(e.data);
            bumpStat(data.house_id, 'views');
        });
        es.addEventListener('wa_click', function (e) {
            var data = JSON.parse(e.data);
            bumpStat(data.house_id, 'wa_clicks');
        });
        es.addEventListener('house_approved', function (e) {
            var data = JSON.parse(e.data);
            updateHouseStatus(data.house_id, 'approved', null);
            showToast((toastContainer.getAttribute('data-t-house-approved') || '').replace('%s', data.title));
        });
        es.addEventListener('house_rejected', function (e) {
            var data = JSON.parse(e.data);
            updateHouseStatus(data.house_id, 'rejected', data.reason);
            showToast((toastContainer.getAttribute('data-t-house-rejected') || '').replace('%s', data.title));
        });
        es.addEventListener('payment_ok', function () {
            showToast(toastContainer.getAttribute('data-t-payment-ok') || '');
            setTimeout(function () { window.location.reload(); }, 3000);
        });
        es.onerror = function () {
            // EventSource brauzer tərəfindən avtomatik reconnect edir; əlavə iş lazım deyil.
        };
    }
})();

// ---------- PWA quraşdırma təklifi (bölmə 11.3) ----------
(function () {
    var sheet = document.getElementById('install-sheet');
    var acceptBtn = document.getElementById('install-accept');
    var dismissBtn = document.getElementById('install-dismiss');
    if (!sheet || !acceptBtn || !dismissBtn) {
        return;
    }

    var DISMISS_KEY = 'getdik_install_dismissed_until';
    var VIEWS_KEY = 'getdik_page_views';
    var SEVEN_DAYS_MS = 7 * 24 * 60 * 60 * 1000;

    function isStandalone() {
        return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone === true;
    }

    function isDismissed() {
        try {
            var until = parseInt(localStorage.getItem(DISMISS_KEY) || '0', 10);
            return until > Date.now();
        } catch (e) {
            return false;
        }
    }

    if (isStandalone() || isDismissed()) {
        return;
    }

    function hide() {
        sheet.classList.remove('is-visible');
    }

    function show() {
        sheet.classList.add('is-visible');
    }

    var isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    var deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
    });

    acceptBtn.addEventListener('click', function () {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function () {
                deferredPrompt = null;
                hide();
            });
            return;
        }
        if (isIOS) {
            var iosText = document.getElementById('install-ios-body-text');
            var bodyEl = document.getElementById('install-sheet-body');
            if (iosText && bodyEl) {
                bodyEl.textContent = iosText.content ? iosText.content.textContent : iosText.textContent;
            }
            var actions = sheet.querySelector('.install-sheet__actions');
            if (actions) {
                actions.style.display = 'none';
            }
            return;
        }
        hide();
    });

    dismissBtn.addEventListener('click', function () {
        try {
            localStorage.setItem(DISMISS_KEY, String(Date.now() + SEVEN_DAYS_MS));
        } catch (e) {
            // localStorage əlçatan deyilsə sadəcə bu sessiyada gizlədilir
        }
        hide();
    });

    var mode = sheet.getAttribute('data-mode');
    var justRegistered = location.search.indexOf('xosgeldin=1') !== -1;

    if (mode === 'owner') {
        if (justRegistered) {
            setTimeout(show, 800);
        }
        return;
    }

    var views = 2;
    try {
        views = parseInt(sessionStorage.getItem(VIEWS_KEY) || '0', 10) + 1;
        sessionStorage.setItem(VIEWS_KEY, String(views));
    } catch (e) {
        // sessionStorage əlçatan deyilsə 2-ci baxış fərziyyəsi ilə davam edilir
    }
    if (views >= 2) {
        setTimeout(show, 800);
    }
})();
