/**
 * main.js – Download Manager
 *
 * Handles:
 *   - Mobile navigation toggle
 *   - Drag-and-drop upload zone
 *   - File input label update on selection
 *
 * Author: Snat · https://terra.me.uk
 */

(function () {
    'use strict';

    // -------------------------------------------------------------------------
    // Mobile nav toggle
    // -------------------------------------------------------------------------
    const toggle = document.querySelector('.nav-toggle');
    const nav    = document.querySelector('.primary-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        // Close on outside click
        document.addEventListener('click', function (e) {
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // -------------------------------------------------------------------------
    // Upload zone — drag-and-drop styling and filename display
    // -------------------------------------------------------------------------
    const zone     = document.getElementById('upload-zone');
    const fileInput = document.getElementById('file');
    const fileName  = document.getElementById('upload-filename');

    if (zone && fileInput) {
        // Show selected filename when the user picks via browse
        fileInput.addEventListener('change', function () {
            if (fileInput.files && fileInput.files.length > 0) {
                showFileName(fileInput.files[0].name);
            }
        });

        // Drag-over visual feedback
        zone.addEventListener('dragover', function (e) {
            e.preventDefault();
            zone.classList.add('is-dragover');
        });

        zone.addEventListener('dragleave', function () {
            zone.classList.remove('is-dragover');
        });

        zone.addEventListener('drop', function (e) {
            e.preventDefault();
            zone.classList.remove('is-dragover');

            const files = e.dataTransfer && e.dataTransfer.files;
            if (files && files.length > 0) {
                // Assign the dropped file to the real input via DataTransfer
                try {
                    const dt = new DataTransfer();
                    dt.items.add(files[0]);
                    fileInput.files = dt.files;
                } catch (_) {
                    // DataTransfer not supported — graceful degradation
                }
                showFileName(files[0].name);
            }
        });
    }

    /**
     * Displays the chosen filename inside the upload zone.
     *
     * @param {string} name  Filename to display.
     */
    function showFileName(name) {
        if (fileName) {
            fileName.textContent = '📎 ' + name;
            fileName.removeAttribute('hidden');
        }
    }

    // -------------------------------------------------------------------------
    // Auto-dismiss alerts after 6 seconds
    // -------------------------------------------------------------------------
    document.querySelectorAll('.alert--success').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 6000);
    });

}());
