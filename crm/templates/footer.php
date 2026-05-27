<?php
/**
 * crm/templates/footer.php
 *
 * CRM admin layout footer.
 * Closes the admin content wrapper and loads the CRM JavaScript.
 */
?>
    </div><!-- /.admin-content -->
</main><!-- /.admin-main -->

<?php if (!defined('CRM_STANDALONE')): ?>
<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
<?php endif; ?>
<script src="<?= CRM_URL ?>/assets/js/crm.js" defer></script>
</body>
</html>
