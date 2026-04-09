<?php
/**
 * templates/product_card.php
 *
 * Renders a single product card for use in the product grid.
 * Expects the variable $product to be in scope before inclusion.
 *
 * Required keys: id, name, slug, price, short_desc, image, stock, status
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */
?>
<article class="product-card">
    <a href="<?= SITE_URL ?>/product.php?id=<?= (int) $product['id'] ?>" class="product-card__image-link">
        <?php if (!empty($product['image'])): ?>
            <img
                src="<?= SITE_URL ?>/assets/images/<?= e($product['image']) ?>"
                alt="<?= e($product['name']) ?>"
                class="product-card__image"
                loading="lazy"
                width="400"
                height="300"
            >
        <?php else: ?>
            <div class="product-card__image product-card__image--placeholder" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
        <?php endif; ?>
    </a>

    <div class="product-card__body">
        <?php if (!empty($product['category_name'])): ?>
            <span class="product-card__category"><?= e($product['category_name']) ?></span>
        <?php endif; ?>

        <h2 class="product-card__title">
            <a href="<?= SITE_URL ?>/product.php?id=<?= (int) $product['id'] ?>">
                <?= e($product['name']) ?>
            </a>
        </h2>

        <?php if (!empty($product['short_desc'])): ?>
            <p class="product-card__desc"><?= e($product['short_desc']) ?></p>
        <?php endif; ?>

        <div class="product-card__footer">
            <span class="product-card__price"><?= formatPrice((float) $product['price']) ?></span>

            <?php if ((int) $product['stock'] === 0): ?>
                <span class="badge badge--out">Out of stock</span>
            <?php else: ?>
                <form method="post" action="<?= SITE_URL ?>/cart.php" class="add-to-cart-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="action"     value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="hidden" name="qty"        value="1">
                    <button type="submit" class="btn btn--primary btn--sm">Add to cart</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</article>
