<?php
/**
 * templates/footer.php
 *
 * Renders the shared store footer, closing HTML tags, and loads JS.
 * Included at the bottom of every public page.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */
?>
    </div><!-- /.container (opened in header.php) -->
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer-copy">
            &copy; <?= date('Y') ?> <a href="<?= SITE_URL ?>"><?= e(SITE_NAME) ?></a>.
            Built with PHP &amp; SQLite.
        </p>

        <nav class="footer-nav" aria-label="Footer navigation">
            <ul>
                <li><a href="<?= SITE_URL ?>">Shop</a></li>
                <li><a href="<?= SITE_URL ?>/cart.php">Cart</a></li>
                <li><a href="<?= SITE_URL ?>/admin/">Admin</a></li>
            </ul>
        </nav>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
