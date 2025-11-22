/**
 * calendar/assets/js/main.js
 *
 * Client-side interactions for the Calendar module.
 *
 *   - Mobile navigation toggle
 *   - Auto-forward end datetime when start changes
 *   - Copy-to-clipboard for token URLs
 *   - Event modal (populated entirely server-side via data attributes
 *     or a lightweight fetch to event.php?id=X&json=1)
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // Mobile navigation toggle
    // -----------------------------------------------------------------------
    const navToggle = document.querySelector('.nav-toggle');
    const primaryNav = document.querySelector('.primary-nav');

    if (navToggle && primaryNav) {
        navToggle.addEventListener('click', () => {
            const expanded = navToggle.getAttribute('aria-expanded') === 'true';
            navToggle.setAttribute('aria-expanded', String(!expanded));
            primaryNav.classList.toggle('is-open', !expanded);
        });
    }

    // -----------------------------------------------------------------------
    // Datetime pickers: auto-advance end time when start changes
    // -----------------------------------------------------------------------
    const startInput = document.getElementById('start_datetime');
    const endInput   = document.getElementById('end_datetime');

    if (startInput && endInput) {
        startInput.addEventListener('change', () => {
            if (!startInput.value) return;
            // If end is blank or earlier than start, set end = start + 1 hour
            const startMs = new Date(startInput.value).getTime();
            const endMs   = endInput.value ? new Date(endInput.value).getTime() : 0;

            if (!endInput.value || endMs < startMs) {
                const newEnd = new Date(startMs + 60 * 60 * 1000);
                // Format as "YYYY-MM-DDTHH:MM"
                const pad = n => String(n).padStart(2, '0');
                endInput.value = newEnd.getFullYear()    + '-' +
                                 pad(newEnd.getMonth() + 1) + '-' +
                                 pad(newEnd.getDate())   + 'T' +
                                 pad(newEnd.getHours())  + ':' +
                                 pad(newEnd.getMinutes());
            }
        });
    }

    // -----------------------------------------------------------------------
    // Copy-to-clipboard for token / sync URLs
    // -----------------------------------------------------------------------
    window.copyToClipboard = function (el) {
        const text = el.textContent.trim();
        if (!navigator.clipboard) {
            // Fallback for older browsers
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity  = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        } else {
            navigator.clipboard.writeText(text);
        }
        const original = el.title;
        el.title = 'Copied!';
        el.style.borderColor = 'var(--clr-accent)';
        setTimeout(() => {
            el.title = original;
            el.style.borderColor = '';
        }, 1500);
    };

    // -----------------------------------------------------------------------
    // Event modal
    // -----------------------------------------------------------------------
    const modal    = document.getElementById('event-modal');
    const modalClose = document.getElementById('modal-close');

    if (modal && modalClose) {
        // Close on backdrop click
        modal.querySelector('.modal__backdrop').addEventListener('click', closeModal);
        modalClose.addEventListener('click', closeModal);

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.hidden) closeModal();
        });
    }

    /**
     * Opens the event modal and populates it with data from a fetch.
     *
     * @param {number} eventId
     * @param {string} baseUrl  SITE_URL (injected as data-base-url on cal-grid)
     */
    function openEventModal(eventId, baseUrl) {
        if (!modal) return;

        fetch(baseUrl + '/event.php?id=' + encodeURIComponent(eventId) + '&json=1')
            .then(r => r.ok ? r.json() : null)
            .then(data => {
                if (!data) return;

                document.getElementById('modal-title').textContent        = data.title      || '';
                document.getElementById('modal-datetime').textContent      = data.datetime   || '';
                document.getElementById('modal-desc').textContent          = data.description || '';

                const locEl = document.getElementById('modal-location');
                if (data.location) {
                    locEl.textContent = '📍 ' + data.location;
                    locEl.hidden = false;
                } else {
                    locEl.hidden = true;
                }

                document.getElementById('modal-edit-link').href   = baseUrl + '/edit_event.php?id=' + eventId;
                document.getElementById('modal-detail-link').href = baseUrl + '/event.php?id=' + eventId;

                modal.hidden = false;
                modal.querySelector('.modal__close').focus();
            })
            .catch(() => {
                // Fallback: navigate directly to event page
                window.location.href = baseUrl + '/event.php?id=' + eventId;
            });
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
    }

    // -----------------------------------------------------------------------
    // Wire up calendar event chip clicks to open modal
    // -----------------------------------------------------------------------
    const calGrid = document.querySelector('.cal-grid');

    if (calGrid) {
        const baseUrl = calGrid.dataset.baseUrl || '';

        calGrid.addEventListener('click', (e) => {
            const chip = e.target.closest('.cal-event-chip');
            if (!chip) return;

            // Extract event ID from the href: /event.php?id=X
            const href  = chip.getAttribute('href') || '';
            const match = href.match(/[?&]id=(\d+)/);
            if (!match) return;

            e.preventDefault();
            openEventModal(parseInt(match[1], 10), baseUrl);
        });
    }

    // -----------------------------------------------------------------------
    // Flash message auto-dismiss (3 seconds)
    // -----------------------------------------------------------------------
    const flash = document.querySelector('.alert--success');
    if (flash) {
        setTimeout(() => {
            flash.style.transition = 'opacity .4s ease';
            flash.style.opacity    = '0';
            setTimeout(() => flash.remove(), 400);
        }, 3000);
    }

})();
