<?php
/**
 * store/db/demo_seed.php
 *
 * Seeds demo categories and products into an in-memory store database for screenshots.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

function seedDemoStore(PDO $pdo): void
{
    // Categories
    $pdo->exec("INSERT OR IGNORE INTO categories (id, name, slug, created_at) VALUES
        (1000, 'T-Shirts', 't-shirts', datetime('now', '-10 days')),
        (1001, 'Mugs', 'mugs', datetime('now', '-9 days'))");

    // Products
    $pdo->exec("INSERT OR IGNORE INTO products (id, name, slug, description, short_desc, price, stock, image, category_id, status, created_at) VALUES
        (1000, 'Classic Tee', 'classic-tee', 'A comfortable cotton t-shirt.', 'Soft cotton T-shirt', 19.99, 50, 'assets/images/classic-tee.jpg', 1000, 'active', datetime('now', '-3 days')),
        (1001, 'Logo Mug', 'logo-mug', 'Ceramic mug with logo.', '350ml ceramic mug', 9.50, 120, 'assets/images/logo-mug.jpg', 1001, 'active', datetime('now', '-2 days')),
        (1002, 'Limited Edition Tee', 'limited-tee', 'Limited run design.', 'Limited edition tee', 29.99, 10, NULL, 1000, 'active', datetime('now', '-1 days'))");
}
