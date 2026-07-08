(function () {
    'use strict';

    // Node redaktoru: node_type dəyişəndə uyğun sahələr göstərilsin.
    function initNodeTypeFields() {
        var select = document.getElementById('node_type');
        if (!select) return;

        var groups = document.querySelectorAll('.field-group');

        function apply() {
            var type = select.value;
            groups.forEach(function (group) {
                var types = (group.getAttribute('data-types') || '').split(',');
                if (types.indexOf(type) !== -1) {
                    group.classList.add('visible');
                } else {
                    group.classList.remove('visible');
                }
            });
        }

        select.addEventListener('change', apply);
        apply();
    }

    // Düymə formu: button_type=url olanda target_node gizlədilsin, əksinə url gizlədilsin.
    function initButtonTypeFields() {
        var select = document.getElementById('button_type');
        if (!select) return;

        var targetGroup = document.querySelector('.btn-target-node');
        var urlGroup = document.querySelector('.btn-target-url');

        function apply() {
            var isUrl = select.value === 'url';
            if (targetGroup) targetGroup.style.display = isUrl ? 'none' : 'flex';
            if (urlGroup) urlGroup.style.display = isUrl ? 'flex' : 'none';
        }

        select.addEventListener('change', apply);
        apply();
    }

    // Flash mesajları avtomatik gizlət.
    function initFlashAutoHide() {
        var flashes = document.querySelectorAll('.flash');
        flashes.forEach(function (flash) {
            setTimeout(function () {
                flash.style.transition = 'opacity 0.4s ease';
                flash.style.opacity = '0';
                setTimeout(function () { flash.remove(); }, 400);
            }, 4000);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initNodeTypeFields();
        initButtonTypeFields();
        initFlashAutoHide();
    });
})();
