<?php
/**
 * functions.php
 *
 * Global helper functions used across the portfolio store.
 * All database queries use PDO prepared statements to prevent SQL injection.
 *
 * Sections
 * --------
 *   1. mbstring compatibility shim
 *   2. String / output helpers
 *   3. Routing helpers
 *   4. Session / CSRF helpers
 *   5. Flash messages
 *   6. Cart helpers
 *   7. Product queries
 *   8. Category queries
 *   9. Order queries
 *  10. User / auth queries
 *  11. Currency helpers
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// 1. mbstring compatibility shim
// ---------------------------------------------------------------------------
if (!extension_loaded('mbstring')) {
    if (!function_exists('mb_strlen')) {
        
        /**
         * UTF-8-aware strlen fallback (mbstring not available).
         *
         * @param string $s
         * @param string $encoding
         * @return int
         */
        function mb_strlen(string $s, string $encoding = 'UTF-8'): int
        {
            if ($s === '') return 0;
            preg_match_all('/./us', $s, $m);
            return count($m[0]);
        }
    }
    if (!function_exists('mb_substr')) {
        
        /**
         * UTF-8-aware substr fallback (mbstring not available).
         *
         * @param string $s
         * @param int $start
         * @param ?int $length
         * @param string $encoding
         * @return string
         */
        function mb_substr(string $s, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
        {
            if ($s === '') return '';
            preg_match_all('/./us', $s, $m);
            $arr = $m[0];
            if ($start < 0) $start = count($arr) + $start;
            return $length === null
                ? implode('', array_slice($arr, $start))
                : implode('', array_slice($arr, $start, $length));
        }
    }
    if (!function_exists('mb_strtolower')) {
        
        /**
         * Best-effort strtolower fallback for UTF-8 strings.
         *
         * @param string $s
         * @param string $encoding
         * @return string
         */
        function mb_strtolower(string $s, string $encoding = 'UTF-8'): string
        { return strtolower($s); }
    }
}

// ===========================================================================
// 2. String / output helpers
// ===========================================================================

/**
 * Escapes a string for safe HTML output (prevents XSS).
 *
 * @param string $string  Raw input.
 * @return string  HTML-safe string.
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Converts a string to a URL-friendly slug.
 *
 * @param string $text  Input text.
 * @return string  URL slug.
 */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Truncates HTML content to a plain-text excerpt.
 *
 * @param string $htmlContent  HTML content.
 * @param int    $length       Max character length.
 * @return string  Plain-text excerpt.
 */
function makeExcerpt(string $htmlContent, int $length = 160): string
{
    $plain = html_entity_decode(strip_tags($htmlContent), ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', trim($plain));
    if (mb_strlen($plain) <= $length) return $plain;
    return mb_substr($plain, 0, $length) . '…';
}

/**
 * Formats a UTC date string for human-readable display.
 *
 * @param string $dateString  SQLite datetime string.
 * @param string $format      PHP date() format.
 * @return string  Formatted date.
 */
function formatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

// ===========================================================================
// 3. Routing helpers
// ===========================================================================

/**
 * Redirects the browser to the given URL and terminates execution.
 *
 * @param string $url  Destination URL.
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

// ===========================================================================
// 4. Session / CSRF helpers
// ===========================================================================

/**
 * Generates (or reuses) a CSRF token for the current session.
 *
 * @return string  64-character hex CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a submitted CSRF token against the session token.
 *
 * @param string $submittedToken  Token from the POST form field.
 * @return bool  TRUE if valid.
 */
function validateCsrf(string $submittedToken): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return $sessionToken !== '' && hash_equals($sessionToken, $submittedToken);
}

// ===========================================================================
// 5. Flash messages
// ===========================================================================

/**
 * Stores a one-time message in the session for display on the next load.
 *
 * @param string $message  The message text.
 * @param string $type     'success' | 'error' | 'info'.
 * @return void
 */
function flashMessage(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieves and clears the stored flash message.
 *
 * @return array{message: string, type: string}|null  Flash data or NULL.
 */
function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/**
 * Renders the flash message as an HTML alert div (if one exists).
 *
 * @return void
 */
function renderFlash(): void
{
    $flash = getFlash();
    if ($flash === null) return;
    $type = in_array($flash['type'], ['success', 'error', 'info'], true) ? $flash['type'] : 'info';
    echo '<div class="alert alert--' . $type . '" role="alert">' . e($flash['message']) . '</div>';
}

// ===========================================================================
// 6. Cart helpers  (session-based, no login required)
// ===========================================================================

/**
 * Returns the current cart from the session.
 *
 * Cart structure: [ product_id (int) => quantity (int), ... ]
 *
 * @return array<int, int>
 */
function getCart(): array
{
    return $_SESSION['cart'] ?? [];
}

/**
 * Adds a product to the cart or increases its quantity.
 *
 * Validates that the requested quantity does not exceed available stock.
 *
 * @param int $productId  Product ID.
 * @param int $qty        Quantity to add (default 1).
 * @return bool  TRUE on success, FALSE if product not found or insufficient stock.
 */
function addToCart(int $productId, int $qty = 1): bool
{
    if ($qty < 1) return false;

    $product = getProductById($productId);
    if ($product === null || $product['status'] !== 'active') return false;

    $cart    = getCart();
    $current = $cart[$productId] ?? 0;
    $newQty  = $current + $qty;

    if ($product['stock'] >= 0 && $newQty > $product['stock']) {
        $newQty = $product['stock'];
    }

    if ($newQty < 1) return false;

    $cart[$productId]  = $newQty;
    $_SESSION['cart']  = $cart;
    return true;
}

/**
 * Sets the quantity of a cart item directly.
 *
 * Setting qty to 0 removes the item from the cart.
 *
 * @param int $productId  Product ID.
 * @param int $qty        New quantity.
 * @return void
 */
function updateCartItem(int $productId, int $qty): void
{
    $cart = getCart();
    if ($qty <= 0) {
        unset($cart[$productId]);
    } else {
        $product = getProductById($productId);
        if ($product !== null && $product['stock'] >= 0) {
            $qty = min($qty, $product['stock']);
        }
        $cart[$productId] = $qty;
    }
    $_SESSION['cart'] = $cart;
}

/**
 * Removes a product from the cart entirely.
 *
 * @param int $productId  Product ID.
 * @return void
 */
function removeFromCart(int $productId): void
{
    $cart = getCart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

/**
 * Empties the cart completely.
 *
 * @return void
 */
function clearCart(): void
{
    $_SESSION['cart'] = [];
}

/**
 * Returns the total number of items (sum of quantities) in the cart.
 *
 * @return int
 */
function getCartCount(): int
{
    return array_sum(getCart());
}

/**
 * Returns enriched cart rows (product data + quantity + line total).
 *
 * Fetches each product from the database so prices are always server-side.
 * Items for which the product no longer exists are silently skipped.
 *
 * @return array<int, array<string, mixed>>  Array of enriched cart rows.
 */
function getCartItems(): array
{
    $cart  = getCart();
    $items = [];

    foreach ($cart as $productId => $qty) {
        $product = getProductById((int) $productId);
        if ($product === null) continue;

        $lineTotal = $product['price'] * $qty;
        $items[]   = array_merge($product, [
            'qty'        => $qty,
            'line_total' => $lineTotal,
        ]);
    }

    return $items;
}

/**
 * Calculates the cart grand total from the database (never trusts client values).
 *
 * @return float  Total price of all items in the cart.
 */
function getCartTotal(): float
{
    $total = 0.0;
    foreach (getCartItems() as $item) {
        $total += $item['line_total'];
    }
    return round($total, 2);
}

// ===========================================================================
// 7. Product queries
// ===========================================================================

/**
 * Returns all active products, optionally filtered by category or search term.
 *
 * @param int         $page       Page number (1-based).
 * @param int         $perPage    Rows per page.
 * @param int|null    $categoryId Filter by category ID.
 * @param string|null $search     Filter by name / description keyword.
 * @return array<int, array<string, mixed>>
 */
function getProducts(
    int    $page       = 1,
    int    $perPage    = PRODUCTS_PER_PAGE,
    ?int   $categoryId = null,
    ?string $search    = null
): array {
    $offset = ($page - 1) * $perPage;
    $where  = ["p.status = 'active'"];
    $params = [];

    if ($categoryId !== null) {
        $where[]               = 'p.category_id = :cat';
        $params[':cat']        = $categoryId;
    }

    if ($search !== null && $search !== '') {
        $where[]               = "(p.name LIKE :q OR p.short_desc LIKE :q OR p.description LIKE :q)";
        $params[':q']          = '%' . $search . '%';
    }

    $whereClause = implode(' AND ', $where);

    $stmt = getDB()->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM   products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE  {$whereClause}
        ORDER  BY p.created_at DESC
        LIMIT  :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Returns the total count of active products (used for pagination).
 *
 * @param int|null    $categoryId  Filter by category.
 * @param string|null $search      Filter by keyword.
 * @return int
 */
function countProducts(?int $categoryId = null, ?string $search = null): int
{
    $where  = ["p.status = 'active'"];
    $params = [];

    if ($categoryId !== null) {
        $where[]        = 'p.category_id = :cat';
        $params[':cat'] = $categoryId;
    }
    if ($search !== null && $search !== '') {
        $where[]      = "(p.name LIKE :q OR p.short_desc LIKE :q OR p.description LIKE :q)";
        $params[':q'] = '%' . $search . '%';
    }

    $whereClause = implode(' AND ', $where);
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM products p WHERE {$whereClause}");
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

/**
 * Retrieves a single product by its numeric ID.
 *
 * @param int $id  Product ID.
 * @return array<string, mixed>|null  Product row or NULL.
 */
function getProductById(int $id): ?array
{
    $stmt = getDB()->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM   products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE  p.id = :id
        LIMIT  1
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retrieves a single product by its slug.
 *
 * @param string $slug  URL slug.
 * @return array<string, mixed>|null
 */
function getProductBySlug(string $slug): ?array
{
    $stmt = getDB()->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM   products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE  p.slug = :slug AND p.status = 'active'
        LIMIT  1
    ");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Returns all products (including inactive) for admin management.
 *
 * @return array<int, array<string, mixed>>
 */
function getAllProductsAdmin(): array
{
    return getDB()->query("
        SELECT p.*, c.name AS category_name
        FROM   products p
        LEFT JOIN categories c ON c.id = p.category_id
        ORDER  BY p.created_at DESC
    ")->fetchAll();
}

/**
 * Inserts a new product and returns its new ID.
 *
 * @param array<string, mixed> $data  Validated product fields.
 * @return int  New product ID.
 */
function createProduct(array $data): int
{
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO products (name, slug, description, short_desc, price, stock, image, category_id, status)
        VALUES (:name, :slug, :description, :short_desc, :price, :stock, :image, :category_id, :status)
    ");
    $stmt->execute([
        ':name'        => $data['name'],
        ':slug'        => $data['slug'],
        ':description' => $data['description'],
        ':short_desc'  => $data['short_desc'],
        ':price'       => $data['price'],
        ':stock'       => $data['stock'],
        ':image'       => $data['image'] ?? null,
        ':category_id' => $data['category_id'] ?? null,
        ':status'      => $data['status'] ?? 'active',
    ]);
    return (int) $db->lastInsertId();
}

/**
 * Updates an existing product by ID.
 *
 * @param int                  $id    Product ID.
 * @param array<string, mixed> $data  Validated product fields.
 * @return void
 */
function updateProduct(int $id, array $data): void
{
    $stmt = getDB()->prepare("
        UPDATE products
        SET    name        = :name,
               slug        = :slug,
               description = :description,
               short_desc  = :short_desc,
               price       = :price,
               stock       = :stock,
               image       = :image,
               category_id = :category_id,
               status      = :status,
               updated_at  = datetime('now')
        WHERE  id = :id
    ");
    $stmt->execute([
        ':name'        => $data['name'],
        ':slug'        => $data['slug'],
        ':description' => $data['description'],
        ':short_desc'  => $data['short_desc'],
        ':price'       => $data['price'],
        ':stock'       => $data['stock'],
        ':image'       => $data['image'] ?? null,
        ':category_id' => $data['category_id'] ?? null,
        ':status'      => $data['status'] ?? 'active',
        ':id'          => $id,
    ]);
}

/**
 * Deletes a product by ID.
 *
 * @param int $id  Product ID.
 * @return void
 */
function deleteProduct(int $id): void
{
    $stmt = getDB()->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

/**
 * Generates a unique slug for a product name.
 *
 * Appends a numeric suffix if the base slug is already taken.
 *
 * @param string   $name       Product name.
 * @param int|null $excludeId  ID to exclude when editing (avoids self-collision).
 * @return string  Unique slug.
 */
function uniqueProductSlug(string $name, ?int $excludeId = null): string
{
    $base   = slugify($name);
    $slug   = $base;
    $suffix = 1;

    while (true) {
        $stmt = getDB()->prepare("SELECT id FROM products WHERE slug = :slug LIMIT 1");
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row || ($excludeId !== null && (int) $row['id'] === $excludeId)) {
            break;
        }
        $slug = $base . '-' . (++$suffix);
    }

    return $slug;
}

// ===========================================================================
// 8. Category queries
// ===========================================================================

/**
 * Returns all product categories.
 *
 * @return array<int, array<string, mixed>>
 */
function getAllCategories(): array
{
    return getDB()->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

/**
 * Returns a single category by ID.
 *
 * @param int $id  Category ID.
 * @return array<string, mixed>|null
 */
function getCategoryById(int $id): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Returns a single category by slug.
 *
 * @param string $slug  Category slug.
 * @return array<string, mixed>|null
 */
function getCategoryBySlug(string $slug): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM categories WHERE slug = :slug LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ===========================================================================
// 9. Order queries
// ===========================================================================

/**
 * Creates a new order and its line items in a transaction.
 *
 * Recalculates totals server-side from the database—never trusts the
 * client-submitted total.
 *
 * @param string               $name   Customer name.
 * @param string               $email  Customer email.
 * @param array<int, array<string, mixed>> $items  Enriched cart items (from getCartItems()).
 * @return int  New order ID.
 */
function createOrder(string $name, string $email, array $items): int
{
    $db = getDB();

    // Recalculate total from database prices
    $total = 0.0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['qty'];
    }
    $total = round($total, 2);

    $db->beginTransaction();

    try {
        $stmt = $db->prepare("
            INSERT INTO orders (customer_name, customer_email, total, status, payment_provider)
            VALUES (:name, :email, :total, 'pending', 'paypal')
        ");
        $stmt->execute([
            ':name'  => $name,
            ':email' => $email,
            ':total' => $total,
        ]);
        $orderId = (int) $db->lastInsertId();

        $itemStmt = $db->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, price, quantity)
            VALUES (:order_id, :product_id, :product_name, :price, :quantity)
        ");

        foreach ($items as $item) {
            $itemStmt->execute([
                ':order_id'    => $orderId,
                ':product_id'  => $item['id'],
                ':product_name'=> $item['name'],
                ':price'       => $item['price'],
                ':quantity'    => $item['qty'],
            ]);

            // Decrement stock
            if ($item['stock'] >= 0) {
                $db->prepare("UPDATE products SET stock = MAX(0, stock - :qty) WHERE id = :id")
                   ->execute([':qty' => $item['qty'], ':id' => $item['id']]);
            }
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return $orderId;
}

/**
 * Retrieves a single order by ID, including its line items.
 *
 * @param int $id  Order ID.
 * @return array<string, mixed>|null  Order row with 'items' key, or NULL.
 */
function getOrderById(int $id): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $order = $stmt->fetch();
    if (!$order) return null;

    $itemsStmt = getDB()->prepare("SELECT * FROM order_items WHERE order_id = :id");
    $itemsStmt->execute([':id' => $id]);
    $order['items'] = $itemsStmt->fetchAll();

    return $order;
}

/**
 * Updates the payment status and payment ID for an order.
 *
 * @param int    $orderId      Order ID.
 * @param string $status       New status ('paid', 'cancelled', 'refunded').
 * @param string $paymentId    PayPal capture/transaction ID.
 * @param string $paymentDetail  JSON-encoded payment detail for auditing.
 * @return void
 */
function updateOrderPayment(int $orderId, string $status, string $paymentId, string $paymentDetail = ''): void
{
    $stmt = getDB()->prepare("
        UPDATE orders
        SET    status         = :status,
               payment_id     = :payment_id,
               payment_detail = :payment_detail,
               updated_at     = datetime('now')
        WHERE  id = :id
    ");
    $stmt->execute([
        ':status'         => $status,
        ':payment_id'     => $paymentId,
        ':payment_detail' => $paymentDetail,
        ':id'             => $orderId,
    ]);
}

/**
 * Returns all orders for the admin panel (most recent first).
 *
 * @param int $limit  Maximum rows to return.
 * @return array<int, array<string, mixed>>
 */
function getAllOrders(int $limit = 100): array
{
    $stmt = getDB()->prepare("
        SELECT * FROM orders ORDER BY created_at DESC LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// ===========================================================================
// 10. User / auth queries
// ===========================================================================

/**
 * Returns a user row by username.
 *
 * @param string $username  Username to look up.
 * @return array<string, mixed>|null
 */
function getUserByUsername(string $username): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

// ===========================================================================
// 11. Currency helpers
// ===========================================================================

/**
 * Formats a numeric price for display using the configured currency symbol.
 *
 * @param float $amount  Price amount.
 * @return string  e.g. "£9.99"
 */
function formatPrice(float $amount): string
{
    return CURRENCY_SYMBOL . number_format($amount, 2);
}
