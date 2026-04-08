<?php
/**
 * functions.php
 *
 * Global helper functions used across the portfolio blog.
 * All database queries use PDO prepared statements to prevent SQL injection.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// --------------------------------------------------------------------------
// mbstring compatibility shim
// --------------------------------------------------------------------------
// Some environments (minimal PHP builds) may not have the mbstring
// extension enabled which results in fatal "undefined function" errors
// for `mb_strlen`, `mb_substr`, etc. Provide tiny UTF-8-aware fallbacks
// so the app still runs in development. Installing/enabling the PHP
// `mbstring` extension is still recommended for production.
if (!extension_loaded('mbstring')) {
    if (!function_exists('mb_strlen')) {
        function mb_strlen(string $s, string $encoding = 'UTF-8'): int
        {
            if ($encoding === '8bit') {
                return strlen($s);
            }
            if ($s === '') {
                return 0;
            }
            preg_match_all('/./us', $s, $m);
            return count($m[0]);
        }
    }

    if (!function_exists('mb_substr')) {
        function mb_substr(string $s, int $start, ?int $length = null, string $encoding = 'UTF-8'): string
        {
            if ($encoding === '8bit') {
                return $length === null ? substr($s, $start) : substr($s, $start, $length);
            }
            if ($s === '') {
                return '';
            }
            preg_match_all('/./us', $s, $m);
            $arr = $m[0];
            if ($start < 0) {
                $start = count($arr) + $start;
            }
            if ($length === null) {
                return implode('', array_slice($arr, $start));
            }
            return implode('', array_slice($arr, $start, $length));
        }
    }

    if (!function_exists('mb_strtolower')) {
        function mb_strtolower(string $s, string $encoding = 'UTF-8'): string
        {
            // Best-effort fallback; for full Unicode casing enable mbstring.
            return strtolower($s);
        }
    }

    if (!function_exists('mb_strtoupper')) {
        function mb_strtoupper(string $s, string $encoding = 'UTF-8'): string
        {
            return strtoupper($s);
        }
    }
}

// ===========================================================================
// String / Output helpers
// ===========================================================================

/**
 * Escapes a string for safe HTML output.
 *
 * Wraps htmlspecialchars() with UTF-8 encoding and ENT_QUOTES so both
 * single and double quotes are escaped, preventing XSS.
 *
 * @param string $string  Raw input string.
 *
 * @return string  HTML-safe string.
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Converts a plain text string into a URL-friendly slug.
 *
 * Lowercases the string, replaces spaces and non-alphanumeric characters
 * with hyphens, then trims leading/trailing hyphens.
 *
 * @param string $text  Input text (e.g. post title).
 *
 * @return string  URL slug (e.g. "my-first-post").
 */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Generates an excerpt from a block of HTML content.
 *
 * Strips HTML tags, decodes entities, then truncates to $length characters,
 * appending an ellipsis if the content was truncated.
 *
 * @param string $htmlContent  Full HTML post content.
 * @param int    $length       Maximum character length of the excerpt.
 *
 * @return string  Plain-text excerpt.
 */
function makeExcerpt(string $htmlContent, int $length = 200): string
{
    $plain = html_entity_decode(strip_tags($htmlContent), ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', trim($plain));

    if (mb_strlen($plain) <= $length) {
        return $plain;
    }

    return mb_substr($plain, 0, $length) . '…';
}

/**
 * Formats a UTC date string for human-readable display.
 *
 * @param string $dateString  Date string stored in the database (SQLite datetime).
 * @param string $format      PHP date() format string.
 *
 * @return string  Formatted date string.
 */
function formatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Redirects the browser to the given URL and terminates execution.
 *
 * @param string $url  Destination URL (absolute or relative).
 *
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Generates a CSRF token for the current session and stores it.
 *
 * If a token already exists in the session it is returned unchanged so
 * that forms rendered multiple times in one request share the same token.
 *
 * @return string  A 64-character hex CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates the CSRF token submitted with a POST request.
 *
 * Compares the submitted token against the session token using a
 * timing-safe comparison to prevent timing attacks.
 *
 * @param string $submittedToken  The token value from the POST form field.
 *
 * @return bool  TRUE if the token is valid, FALSE otherwise.
 */
function validateCsrf(string $submittedToken): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return hash_equals($sessionToken, $submittedToken);
}

// ===========================================================================
// Post queries
// ===========================================================================

/**
 * Retrieves a paginated list of published posts with author and category info.
 *
 * Joins posts with users and categories so each returned row contains all
 * data needed to render a post card without additional queries.
 *
 * @param int $page    Current page number (1-based).
 * @param int $perPage Number of posts per page.
 *
 * @return array<int, array<string, mixed>>  Array of post rows.
 */
function getPosts(int $page = 1, int $perPage = POSTS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt = getDB()->prepare("
        SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        LEFT JOIN users      u ON u.id = p.author_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'published'
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Returns the total count of published posts (used for pagination).
 *
 * @return int  Total number of published posts.
 */
function countPosts(): int
{
    return (int) getDB()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
}

/**
 * Retrieves a single post by its numeric ID.
 *
 * Includes joined author and category data. Returns NULL if not found.
 *
 * @param int $id  Post ID.
 *
 * @return array<string, mixed>|null  Post row or NULL if not found.
 */
function getPostById(int $id): ?array
{
    $stmt = getDB()->prepare("
        SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        LEFT JOIN users      u ON u.id = p.author_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retrieves a single published post by its URL slug.
 *
 * @param string $slug  URL slug of the post.
 *
 * @return array<string, mixed>|null  Post row or NULL if not found.
 */
function getPostBySlug(string $slug): ?array
{
    $stmt = getDB()->prepare("
        SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        LEFT JOIN users      u ON u.id = p.author_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.slug = :slug AND p.status = 'published'
    ");
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Retrieves published posts filtered by category slug with pagination.
 *
 * @param string $categorySlug  The category's URL slug.
 * @param int    $page          Current page number (1-based).
 * @param int    $perPage       Posts per page.
 *
 * @return array<int, array<string, mixed>>  Array of matching post rows.
 */
function getPostsByCategory(string $categorySlug, int $page = 1, int $perPage = POSTS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt = getDB()->prepare("
        SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        LEFT JOIN users      u ON u.id = p.author_id
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'published' AND c.slug = :slug
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':slug',   $categorySlug, PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $perPage,      PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,       PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Returns the count of published posts belonging to a given category.
 *
 * @param string $categorySlug  The category's URL slug.
 *
 * @return int  Total matching post count.
 */
function countPostsByCategory(string $categorySlug): int
{
    $stmt = getDB()->prepare("
        SELECT COUNT(*)
        FROM posts p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'published' AND c.slug = :slug
    ");
    $stmt->execute([':slug' => $categorySlug]);
    return (int) $stmt->fetchColumn();
}

/**
 * Retrieves published posts that have a given tag, with pagination.
 *
 * @param string $tagSlug  The tag's URL slug.
 * @param int    $page     Current page number (1-based).
 * @param int    $perPage  Posts per page.
 *
 * @return array<int, array<string, mixed>>  Array of matching post rows.
 */
function getPostsByTag(string $tagSlug, int $page = 1, int $perPage = POSTS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt = getDB()->prepare("
        SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        LEFT JOIN users      u  ON u.id  = p.author_id
        LEFT JOIN categories c  ON c.id  = p.category_id
        INNER JOIN post_tags pt ON pt.post_id = p.id
        INNER JOIN tags      t  ON t.id  = pt.tag_id
        WHERE p.status = 'published' AND t.slug = :slug
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':slug',   $tagSlug, PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Returns the count of published posts associated with a given tag.
 *
 * @param string $tagSlug  The tag's URL slug.
 *
 * @return int  Total matching post count.
 */
function countPostsByTag(string $tagSlug): int
{
    $stmt = getDB()->prepare("
        SELECT COUNT(*)
        FROM posts p
        INNER JOIN post_tags pt ON pt.post_id = p.id
        INNER JOIN tags      t  ON t.id = pt.tag_id
        WHERE p.status = 'published' AND t.slug = :slug
    ");
    $stmt->execute([':slug' => $tagSlug]);
    return (int) $stmt->fetchColumn();
}

/**
 * Performs a full-text search across post titles and content.
 *
 * Uses LIKE with a sanitised search term. Returns only published posts.
 *
 * @param string $query    The raw search string entered by the user.
 * @param int    $page     Current page number (1-based).
 * @param int    $perPage  Posts per page.
 *
 * @return array<int, array<string, mixed>>  Array of matching post rows.
 */
function searchPosts(string $query, int $page = 1, int $perPage = POSTS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $like   = '%' . $query . '%';
    $stmt = getDB()->prepare("
        SELECT p.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
        FROM posts p
        LEFT JOIN users      u ON u.id = p.author_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.status = 'published'
          AND (p.title LIKE :like OR p.content LIKE :like2)
        ORDER BY p.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':like',   $like,    PDO::PARAM_STR);
    $stmt->bindValue(':like2',  $like,    PDO::PARAM_STR);
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Returns the count of published posts matching a search query.
 *
 * @param string $query  The raw search string.
 *
 * @return int  Total matching post count.
 */
function countSearchPosts(string $query): int
{
    $like = '%' . $query . '%';
    $stmt = getDB()->prepare("
        SELECT COUNT(*)
        FROM posts
        WHERE status = 'published'
          AND (title LIKE :like OR content LIKE :like2)
    ");
    $stmt->bindValue(':like',  $like, PDO::PARAM_STR);
    $stmt->bindValue(':like2', $like, PDO::PARAM_STR);
    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

// ===========================================================================
// Tag queries
// ===========================================================================

/**
 * Retrieves all tags associated with a given post.
 *
 * @param int $postId  The post's numeric ID.
 *
 * @return array<int, array<string, mixed>>  Array of tag rows (id, name, slug).
 */
function getTagsForPost(int $postId): array
{
    $stmt = getDB()->prepare("
        SELECT t.id, t.name, t.slug
        FROM tags t
        INNER JOIN post_tags pt ON pt.tag_id = t.id
        WHERE pt.post_id = :post_id
        ORDER BY t.name
    ");
    $stmt->execute([':post_id' => $postId]);
    return $stmt->fetchAll();
}

/**
 * Retrieves all tags ordered alphabetically.
 *
 * @return array<int, array<string, mixed>>  Array of all tag rows.
 */
function getAllTags(): array
{
    return getDB()->query("SELECT * FROM tags ORDER BY name")->fetchAll();
}

/**
 * Retrieves all categories ordered alphabetically.
 *
 * @return array<int, array<string, mixed>>  Array of all category rows.
 */
function getAllCategories(): array
{
    return getDB()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

/**
 * Replaces all tag associations for a post with a new set of tag IDs.
 *
 * Deletes the existing post_tags rows for the given post then inserts
 * fresh rows for each tag ID in the supplied array.  Uses INSERT OR IGNORE
 * so any FK violations are silently discarded rather than throwing.
 *
 * @param int   $postId  The post's numeric ID.
 * @param int[] $tagIds  Array of tag IDs to associate with the post.
 *
 * @return void
 */
function syncPostTags(int $postId, array $tagIds): void
{
    $db = getDB();
    $db->prepare("DELETE FROM post_tags WHERE post_id = :post_id")->execute([':post_id' => $postId]);

    if (empty($tagIds)) {
        return;
    }

    $insert = $db->prepare("INSERT OR IGNORE INTO post_tags (post_id, tag_id) VALUES (:post_id, :tag_id)");
    foreach ($tagIds as $tagId) {
        $insert->execute([':post_id' => $postId, ':tag_id' => $tagId]);
    }
}

// ===========================================================================
// Pagination helpers
// ===========================================================================

/**
 * Calculates the total number of pages given a total item count and page size.
 *
 * @param int $total    Total number of items.
 * @param int $perPage  Items per page.
 *
 * @return int  Total page count (minimum 1).
 */
function totalPages(int $total, int $perPage = POSTS_PER_PAGE): int
{
    return max(1, (int) ceil($total / $perPage));
}

/**
 * Builds an array of pagination data for rendering page links.
 *
 * Returns the current page, total pages, and boolean flags for whether
 * previous/next pages exist.
 *
 * @param int    $currentPage  The currently displayed page.
 * @param int    $totalItems   Total items across all pages.
 * @param string $baseUrl      Base URL to prepend to page query strings.
 * @param int    $perPage      Items per page.
 *
 * @return array{current: int, total: int, hasPrev: bool, hasNext: bool, baseUrl: string}
 */
function buildPagination(int $currentPage, int $totalItems, string $baseUrl, int $perPage = POSTS_PER_PAGE): array
{
    $totalPgs = totalPages($totalItems, $perPage);
    return [
        'current' => $currentPage,
        'total'   => $totalPgs,
        'hasPrev' => $currentPage > 1,
        'hasNext' => $currentPage < $totalPgs,
        'baseUrl' => $baseUrl,
    ];
}

// ===========================================================================
// File upload helpers
// ===========================================================================

/**
 * Handles a featured image upload for a blog post.
 *
 * Validates the file type and size, generates a unique filename, then
 * moves the file to the assets/images/uploads/ directory.
 *
 * Allowed MIME types: image/jpeg, image/png, image/gif, image/webp.
 * Maximum file size: 5 MB.
 *
 * @param array<string, mixed> $fileInput  A single entry from $_FILES (e.g. $_FILES['featured_image']).
 *
 * @return string|null  The stored filename (relative to assets/images/uploads/) on success, or NULL on failure.
 */
function handleImageUpload(array $fileInput): ?string
{
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize     = 5 * 1024 * 1024; // 5 MB

    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($fileInput['size'] > $maxSize) {
        return null;
    }

    // Verify MIME type from file contents, not the browser-supplied value
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileInput['tmp_name']);

    if (!in_array($mimeType, $allowedMime, true)) {
        return null;
    }

    $ext      = pathinfo($fileInput['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    $destDir  = ROOT_PATH . '/assets/images/uploads/';

    if (!is_dir($destDir)) {
        mkdir($destDir, 0750, true);
    }

    if (!move_uploaded_file($fileInput['tmp_name'], $destDir . $filename)) {
        return null;
    }

    return $filename;
}
