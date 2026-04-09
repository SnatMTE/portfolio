/**
 * main.js – Portfolio Online Store
 *
 * Progressive enhancements:
 *   1. Mobile navigation toggle
 *   2. Add-to-cart quantity spinner (increment / decrement)
 *   3. Auto-submit cart quantity update on change (debounced)
 *   4. Delete confirmation (secondary guard for no-JS-disabled forms)
 *   5. Flash message auto-dismiss
 *
 * Author: Snat · https://terra.me.uk
 */

(function () {
    'use strict';

    /* ------------------------------------------------------------------
       1. Mobile navigation toggle
       ------------------------------------------------------------------ */
    const toggle = document.querySelector('.nav-toggle');
    const nav    = document.querySelector('.primary-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            nav.classList.toggle('is-open', !expanded);
        });

        // Close nav when clicking outside
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                toggle.setAttribute('aria-expanded', 'false');
                nav.classList.remove('is-open');
            }
        });
    }

    /* ------------------------------------------------------------------
       2. Quantity spinner helpers
          Adds +/- buttons around each .qty-input that does not already
          have them. Works on product detail page and cart.
       ------------------------------------------------------------------ */
    function buildSpinner(input) {
        const wrapper = document.createElement('div');
        wrapper.className = 'qty-spinner';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        const btnDec = document.createElement('button');
        btnDec.type        = 'button';
        btnDec.textContent = '−';
        btnDec.className   = 'qty-spinner__btn';
        btnDec.setAttribute('aria-label', 'Decrease quantity');

        const btnInc = document.createElement('button');
        btnInc.type        = 'button';
        btnInc.textContent = '+';
        btnInc.className   = 'qty-spinner__btn';
        btnInc.setAttribute('aria-label', 'Increase quantity');

        wrapper.insertBefore(btnDec, input);
        wrapper.appendChild(btnInc);

        btnDec.addEventListener('click', () => {
            const val = parseInt(input.value, 10);
            const min = parseInt(input.min || '0', 10);
            if (val > min) input.value = val - 1;
            input.dispatchEvent(new Event('change'));
        });

        btnInc.addEventListener('click', () => {
            const val = parseInt(input.value, 10);
            const max = parseInt(input.max || '9999', 10);
            if (val < max) input.value = val + 1;
            input.dispatchEvent(new Event('change'));
        });
    }

    document.querySelectorAll('.qty-input').forEach((input) => {
        if (!input.closest('.qty-spinner')) {
            buildSpinner(input);
        }
    });

    /* ------------------------------------------------------------------
       3. Auto-submit cart update on quantity change (debounced 600ms)
       ------------------------------------------------------------------ */
    let debounceTimer = null;

    document.querySelectorAll('.qty-form .qty-input').forEach((input) => {
        input.addEventListener('change', () => {
            const form = input.closest('form');
            if (!form) return;
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => form.submit(), 600);
        });
    });

    /* ------------------------------------------------------------------
       4. Flash message auto-dismiss (after 5 seconds)
       ------------------------------------------------------------------ */
    document.querySelectorAll('.alert--success').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .4s ease, max-height .4s ease';
            alert.style.opacity    = '0';
            alert.style.maxHeight  = '0';
            alert.style.overflow   = 'hidden';
            setTimeout(() => alert.remove(), 450);
        }, 5000);
    });

    /* ------------------------------------------------------------------
       5. Image preview for file inputs in admin forms
       ------------------------------------------------------------------ */
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach((input) => {
        input.addEventListener('change', () => {
            const file = input.files[0];
            if (!file) return;

            let preview = input.parentNode.querySelector('.js-image-preview');
            if (!preview) {
                preview = document.createElement('img');
                preview.className = 'js-image-preview';
                preview.style.cssText = 'max-width:120px;max-height:90px;border-radius:6px;border:1px solid #e5e7eb;margin-top:.5rem;display:block;';
                input.parentNode.appendChild(preview);
            }

            const reader = new FileReader();
            reader.onload = (e) => { preview.src = e.target.result; };
            reader.readAsDataURL(file);
        });
    });

}());
