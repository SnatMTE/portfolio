/**
 * main.js
 *
 * Client-side enhancements for the portfolio blog.
 * No external dependencies – plain ES6.
 *
 * Features:
 *   - Mobile navigation toggle
 *   - Flash message auto-dismiss
 *   - Active nav link highlighting
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // Mobile navigation toggle
    // -----------------------------------------------------------------------

    /**
     * Initialises the hamburger / nav-toggle button for mobile viewports.
     *
     * Toggles the `is-open` class on `.primary-nav` and updates the
     * `aria-expanded` attribute on the toggle button for accessibility.
     *
     * @returns {void}
     */
    function initNavToggle() {
        const toggle = document.querySelector('.nav-toggle');
        const nav    = document.querySelector('.primary-nav');

        if (!toggle || !nav) return;

        toggle.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(isOpen));
        });

        // Close nav when a link inside it is clicked (single-page navigation)
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // -----------------------------------------------------------------------
    // Flash / alert message auto-dismiss
    // -----------------------------------------------------------------------

    /**
     * Automatically fades out and removes `.alert` elements after a delay.
     *
     * Each alert is given an `opacity` CSS transition; after the animation
     * completes the element is removed from the DOM entirely.
     *
     * @param {number} delay   Time in ms before the fade begins  (default: 4000).
     * @param {number} fadeDur Duration of the fade in ms         (default: 600).
     *
     * @returns {void}
     */
    function initAlertAutoDismiss(delay, fadeDur) {
        delay   = delay   || 4000;
        fadeDur = fadeDur || 600;

        document.querySelectorAll('.alert').forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity ' + fadeDur + 'ms ease';
                alert.style.opacity    = '0';

                setTimeout(function () {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, fadeDur);
            }, delay);
        });
    }

    // -----------------------------------------------------------------------
    // Highlight the current page's nav link
    // -----------------------------------------------------------------------

    /**
     * Adds an `aria-current="page"` attribute to any navigation anchor whose
     * href matches the current page URL.
     *
     * @returns {void}
     */
    function highlightActiveNav() {
        const currentPath = window.location.pathname;

        document.querySelectorAll('.primary-nav a').forEach(function (link) {
            try {
                const linkUrl  = new URL(link.href, window.location.origin);
                if (linkUrl.pathname === currentPath) {
                    link.setAttribute('aria-current', 'page');
                    link.style.color       = 'var(--clr-accent)';
                    link.style.borderColor = 'var(--clr-accent)';
                }
            } catch (e) {
                // Ignore malformed URLs
            }
        });
    }

    // -----------------------------------------------------------------------
    // Confirm destructive actions (admin delete buttons)
    // -----------------------------------------------------------------------

    /**
     * Attaches a confirmation dialog to all elements with the
     * `data-confirm` attribute before allowing the default action.
     *
     * Usage in HTML:
     *   <a href="/delete?id=1" data-confirm="Delete this post?">Delete</a>
     *
     * @returns {void}
     */
    function initConfirmActions() {
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                const message = el.getAttribute('data-confirm') || 'Are you sure?';
                if (!window.confirm(message)) {
                    e.preventDefault();
                }
            });
        });
    }

    // -----------------------------------------------------------------------
    // Boot
    // -----------------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', function () {
        initNavToggle();
        initAlertAutoDismiss();
        highlightActiveNav();
        initConfirmActions();
    });

}());
