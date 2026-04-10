<?php
/**
 * File: footer.php
 * What it does: Short description of the file's purpose.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

    </div><!-- /.container -->
</main><!-- /.site-main -->

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> <?= e(getSetting('site_name', CMS_NAME)) ?></p>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
