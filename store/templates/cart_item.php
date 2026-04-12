<?php
/**
 * templates/cart_item.php
 *
 * Renders a single row in the cart table.
 * Expects $item to be an enriched cart row from getCartItems().
 *
 * Required keys: id, name, image, price, qty, line_total
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */
?>
<tr class="cart-item">
    <td class="cart-item__image">
        <?php
            $imgFile = 'placeholder.svg';
            if (!empty($item['image'])) {
                $candidate = ROOT_PATH . '/assets/images/' . basename($item['image']);
                if (file_exists($candidate)) {
                    $imgFile = basename($item['image']);
                }
            }
            $imgUrl = SITE_URL . '/assets/images/' . $imgFile;
        ?>
        <img
            src="<?= e($imgUrl) ?>"
            alt="<?= e($item['name']) ?>"
            width="80"
            height="60"
            loading="lazy"
        >
    </td>

    <td class="cart-item__name">
        <a href="<?= SITE_URL ?>/product.php?id=<?= (int) $item['id'] ?>">
            <?= e($item['name']) ?>
        </a>
    </td>

    <td class="cart-item__price"><?= formatPrice((float) $item['price']) ?></td>

    <td class="cart-item__qty">
        <form method="post" action="<?= SITE_URL ?>/cart.php" class="qty-form">
            <input type="hidden" name="csrf_token"  value="<?= csrfToken() ?>">
            <input type="hidden" name="action"      value="update">
            <input type="hidden" name="product_id"  value="<?= (int) $item['id'] ?>">
            <input
                type="number"
                name="qty"
                value="<?= (int) $item['qty'] ?>"
                min="0"
                max="<?= (int) $item['stock'] ?>"
                class="qty-input"
                aria-label="Quantity for <?= e($item['name']) ?>"
            >
            <button type="submit" class="btn btn--sm btn--outline">Update</button>
        </form>
    </td>

    <td class="cart-item__total"><?= formatPrice((float) $item['line_total']) ?></td>

    <td class="cart-item__remove">
        <form method="post" action="<?= SITE_URL ?>/cart.php">
            <input type="hidden" name="csrf_token"  value="<?= csrfToken() ?>">
            <input type="hidden" name="action"      value="remove">
            <input type="hidden" name="product_id"  value="<?= (int) $item['id'] ?>">
            <button type="submit" class="btn btn--sm btn--danger" aria-label="Remove <?= e($item['name']) ?> from cart">
                &times;
            </button>
        </form>
    </td>
</tr>
