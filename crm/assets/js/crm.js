/* crm/assets/js/crm.js */
(function () {
    'use strict';

    /* ── Sidebar mobile toggle ─────────────────────────────── */
    var toggle = document.querySelector('.crm-topbar__toggle');
    var sidebar = document.querySelector('.sidebar');
    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar--open');
        });
    }

    /* ── Confirm-on-delete links ───────────────────────────── */
    // Already handled inline via onclick="return confirm(…)" on individual links,
    // but this catches any data-confirm="…" attributes added dynamically.
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]');
        if (!el) return;
        var msg = el.getAttribute('data-confirm') || 'Are you sure?';
        if (!window.confirm(msg)) e.preventDefault();
    });

    /* ── Auto-dismiss flash alerts after 6 s ───────────────── */
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity .5s';
            alert.style.opacity    = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 6000);
    });

    /* ── Tag input — comma-separated, show as pills ─────────── */
    var tagInputs = document.querySelectorAll('.crm-tag-input');
    tagInputs.forEach(function (input) {
        var container = document.createElement('div');
        container.className = 'crm-tag-pills';
        input.parentNode.insertBefore(container, input);
        input.style.marginTop = '0.5rem';

        function renderPills() {
            var tags = input.value.split(',').map(function (t) { return t.trim(); }).filter(Boolean);
            container.innerHTML = '';
            tags.forEach(function (tag) {
                var pill = document.createElement('span');
                pill.className   = 'tag-pill';
                pill.textContent = tag;
                container.appendChild(pill);
            });
        }

        input.addEventListener('input', renderPills);
        renderPills();
    });

})();
