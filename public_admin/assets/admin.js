// Birlikdə Getdik — admin app shell (vanilla JS)
(function () {
    'use strict';

    var lent = document.getElementById('admin-live-lent');
    if (!lent || typeof EventSource === 'undefined') {
        return;
    }

    var labels = {
        new_owner: function (d) { return 'Yeni qeydiyyat: ' + d.full_name + ' (' + d.phone + ')'; },
        house_pending: function (d) { return 'Təsdiqə göndərildi: "' + d.title + '"'; },
        payment_ok: function (d) { return 'Ödəniş alındı: ' + d.amount + ' AZN (owner #' + d.owner_id + ')'; },
    };

    function addEntry(text) {
        var empty = lent.querySelector('.admin-live-lent__empty');
        if (empty) {
            empty.remove();
        }
        var li = document.createElement('li');
        li.className = 'admin-live-lent__item';
        var time = document.createElement('span');
        time.className = 'admin-live-lent__time';
        time.textContent = new Date().toLocaleTimeString('az-AZ', { hour: '2-digit', minute: '2-digit' });
        var msg = document.createElement('span');
        msg.textContent = text;
        li.appendChild(time);
        li.appendChild(msg);
        lent.insertBefore(li, lent.firstChild);

        while (lent.children.length > 20) {
            lent.removeChild(lent.lastChild);
        }
    }

    var es = new EventSource(lent.getAttribute('data-sse-url'));
    Object.keys(labels).forEach(function (eventType) {
        es.addEventListener(eventType, function (e) {
            var data = JSON.parse(e.data);
            addEntry(labels[eventType](data));
        });
    });
    es.onerror = function () {
        // EventSource brauzer tərəfindən avtomatik reconnect edir.
    };
})();
