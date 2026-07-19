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

if (defined('CMS_ROOT')) {
    require_once CMS_ROOT . '/core/polyfills.php';
    require_once CMS_ROOT . '/core/shared_helpers.php';
} else {
    require_once __DIR__ . '/../cms/core/polyfills.php';
}

// The following functions are provided by CMS core when in CMS mode:
// e(), slugify(), makeExcerpt(), formatDate(), redirect()

/**
 * Generates or reuses a CSRF token for the current session.
 *
 * @return string
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
 * @param string $submittedToken
 * @return bool
 */
function validateCsrf(string $submittedToken): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    return hash_equals($sessionToken, $submittedToken);
}

/**
 * Retrieves a paginated list of published posts with author and category info.
 *
 * @param int $page
 * @param int $perPage
 * @return array<int, array<string, mixed>>
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
 * Returns the total count of published posts.
 *
 * @return int
 */
function countPosts(): int
{
    return (int) getDB()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn();
}

/**
 * Retrieves a single post by ID with author and category data.
 *
 * @param int $id
 * @return array<string, mixed>|null
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
 * Retrieves a single published post by URL slug.
 *
 * @param string $slug
 * @return array<string, mixed>|null
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
 * Retrieves published posts by category with pagination.
 *
 * @param string $categorySlug
 * @param int $page
 * @param int $perPage
 * @return array<int, array<string, mixed>>
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
 * Returns the count of published posts in a given category.
 *
 * @param string $categorySlug
 * @return int
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
 * Retrieves published posts with a given tag and pagination.
 *
 * @param string $tagSlug
 * @param int $page
 * @param int $perPage
 * @return array<int, array<string, mixed>>
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
 * Returns the count of published posts matching a tag.
 *
 * @param string $tagSlug
 * @return int
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
 * @param string $query
 * @param int $page
 * @param int $perPage
 * @return array<int, array<string, mixed>>
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
 * @param string $query
 * @return int
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

/**
 * Retrieves all tags associated with a given post.
 *
 * @param int $postId
 * @return array<int, array<string, mixed>>
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
 * @return array<int, array<string, mixed>>
 */
function getAllTags(): array
{
    return getDB()->query("SELECT * FROM tags ORDER BY name")->fetchAll();
}

/**
 * Retrieves all categories ordered alphabetically.
 *
 * @return array<int, array<string, mixed>>
 */
function getAllCategories(): array
{
    return getDB()->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

/**
 * Replaces all tag associations for a post with a new set of tag IDs.
 *
 * @param int $postId
 * @param int[] $tagIds
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

/**
 * Calculates the total number of pages.
 *
 * @param int $total
 * @param int $perPage
 * @return int
 */
function totalPages(int $total, int $perPage = POSTS_PER_PAGE): int
{
    return max(1, (int) ceil($total / $perPage));
}

/**
 * Builds pagination data for rendering page navigation.
 *
 * @param int $currentPage
 * @param int $totalItems
 * @param string $baseUrl
 * @param int $perPage
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

/**
 * Handles a featured image upload for a blog post.
 * Validates file type, size, and extension, then moves to assets/images/uploads/.
 * Allowed types: JPEG, PNG, GIF, WebP. Maximum size: 5 MB.
 *
 * @param array<string, mixed> $fileInput
 * @return string|null The stored filename on success, or null on failure.
 */
function handleImageUpload(array $fileInput): ?string
{
    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    $maxSize = 5 * 1024 * 1024;

    if ($fileInput['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($fileInput['size'] > $maxSize || $fileInput['size'] === 0) {
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileInput['tmp_name']);

    if (!isset($allowedMime[$mimeType])) {
        return null;
    }

    $safeExt = $allowedMime[$mimeType];
    $filename = bin2hex(random_bytes(8)) . '.' . $safeExt;
    $destDir = ROOT_PATH . '/assets/images/uploads/';

    if (!is_dir($destDir)) {
        mkdir($destDir, 0750, true);
    }

    if (!move_uploaded_file($fileInput['tmp_name'], $destDir . $filename)) {
        return null;
    }

    return $filename;
}
