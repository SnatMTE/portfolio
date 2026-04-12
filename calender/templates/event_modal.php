<?php
/**
 * templates/event_modal.php
 *
 * Accessible modal overlay for quick event preview.
 * Populated via JavaScript when a calendar day cell is clicked.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */
?>
<div id="event-modal" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__box">
        <button class="modal__close" id="modal-close" aria-label="Close modal">&times;</button>

        <div class="modal__header">
            <h2 id="modal-title" class="modal__title">Event</h2>
        </div>

        <div class="modal__body">
            <p id="modal-datetime" class="modal__meta"></p>
            <p id="modal-location" class="modal__meta modal__location" hidden></p>
            <p id="modal-desc" class="modal__desc"></p>
        </div>

        <div class="modal__footer">
            <a id="modal-edit-link"   href="#" class="btn btn--outline btn--sm">Edit</a>
            <a id="modal-detail-link" href="#" class="btn btn--primary btn--sm">View Details</a>
        </div>
    </div>
</div>
