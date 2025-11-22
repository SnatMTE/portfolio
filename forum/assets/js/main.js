/**
 * main.js - Forum JavaScript
 *
 * Handles:
 *   - Mobile navigation toggle
 *   - Auto-dismiss flash messages
 *   - Confirm-before-delete form submissions (backup for browsers without onclick)
 *   - Smooth scroll to post anchors
 *   - Character counter for reply/post textareas
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
    const headerAuth = document.querySelector('.header-auth');

    if (navToggle && primaryNav) {
        navToggle.addEventListener('click', function () {
            const isOpen = primaryNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', String(isOpen));

            // Also show auth links inside the open menu on mobile
            if (headerAuth) {
                headerAuth.classList.toggle('is-open', isOpen);
            }

            // Prevent scrolling when nav is open
            document.body.style.overflow = isOpen ? 'hidden' : '';
        });

        // Close nav on ESC key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && primaryNav.classList.contains('is-open')) {
                primaryNav.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });

        // Close nav when a link is clicked
        primaryNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                primaryNav.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });
    }

    // -----------------------------------------------------------------------
    // Auto-dismiss flash / alert messages after 5 seconds
    // -----------------------------------------------------------------------
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        // Only auto-dismiss success and info alerts, not errors
        if (!alert.classList.contains('alert--error')) {
            setTimeout(function () {
                alert.style.transition = 'opacity .4s ease, max-height .4s ease';
                alert.style.opacity = '0';
                alert.style.maxHeight = '0';
                alert.style.overflow = 'hidden';
                alert.style.marginBottom = '0';
                alert.style.padding = '0';
                setTimeout(function () { alert.remove(); }, 420);
            }, 5000);
        }
    });

    // -----------------------------------------------------------------------
    // Character counter for textareas with maxlength
    // -----------------------------------------------------------------------
    document.querySelectorAll('textarea[maxlength]').forEach(function (textarea) {
        const maxLen = parseInt(textarea.getAttribute('maxlength'), 10);
        if (!maxLen) return;

        const counter = document.createElement('small');
        counter.className = 'char-counter';
        counter.setAttribute('aria-live', 'polite');
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);

        function updateCounter() {
            const remaining = maxLen - textarea.value.length;
            counter.textContent = remaining + ' characters remaining';
            counter.style.color = remaining < 200 ? '#f97316' : '#6b7280';
        }

        textarea.addEventListener('input', updateCounter);
        updateCounter();
    });

    // -----------------------------------------------------------------------
    // Scroll to reply anchor if present in URL hash on page load
    // -----------------------------------------------------------------------
    if (window.location.hash && window.location.hash.startsWith('#post-')) {
        const target = document.querySelector(window.location.hash);
        if (target) {
            setTimeout(function () {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        }
    }

    // -----------------------------------------------------------------------
    // Thread form: focus on title field on page load
    // -----------------------------------------------------------------------
    const titleInput = document.querySelector('.thread-form #title');
    if (titleInput && !titleInput.value) {
        titleInput.focus();
    }

}());
