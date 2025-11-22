/**
 * cms/assets/js/main.js
 *
 * Minimal CMS JavaScript.
 *
 * Features:
 *   - Auto-generate slug from title field
 *   - Mobile sidebar toggle
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

(function () {
    'use strict';

    // -----------------------------------------------------------------------
    // Slug auto-generation
    // -----------------------------------------------------------------------
    const titleInput = document.getElementById('title');
    const slugInput  = document.getElementById('slug');

    if (titleInput && slugInput) {
        let slugEdited = slugInput.value.trim() !== '';

        titleInput.addEventListener('input', function () {
            if (slugEdited) return;
            slugInput.value = slugify(titleInput.value);
        });

        slugInput.addEventListener('input', function () {
            slugEdited = this.value.trim() !== '';
        });
    }

    function slugify(text) {
        return text
            .toLowerCase()
            .replace(/[^a-z0-9\s\-]/g, '')
            .replace(/[\s\-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    // -----------------------------------------------------------------------
    // Mobile sidebar toggle
    // -----------------------------------------------------------------------
    const sidebar    = document.querySelector('.sidebar');
    const toggleBtn  = document.querySelector('.sidebar-toggle');

    if (sidebar && toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            const expanded = sidebar.classList.contains('open');
            toggleBtn.setAttribute('aria-expanded', String(expanded));
        });
    }
})();
