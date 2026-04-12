<?php
/**
 * templates/footer.php
 *
 * Renders the site footer, closes the main content wrapper, and loads JS.
 * Included at the bottom of every public page.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */
?>

    </div><!-- /.container (opened in header.php) -->
</main>

<footer class="site-footer">
    <div class="container footer-inner">
        <p class="footer-copy">
            &copy; <?= date('Y') ?> <a href="<?= SITE_URL ?>"><?= e(FORUM_NAME) ?></a>.
            Built with PHP &amp; SQLite.
        </p>
        <nav class="footer-nav" aria-label="Footer navigation">
            <ul>
                <li><a href="<?= SITE_URL ?>">Home</a></li>
                <li><a href="<?= SITE_URL ?>/search.php">Search</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="<?= SITE_URL ?>/profile.php?id=<?= (int) currentUser()['id'] ?>">Profile</a></li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= SITE_URL ?>/admin/">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js" defer></script>
</body>
</html>
