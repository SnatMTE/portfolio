<?php
/**
 * functions.php
 *
 * Global helper functions for the Forum.
 * All database queries use PDO prepared statements to prevent SQL injection.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// mbstring compatibility shim
// ---------------------------------------------------------------------------
if (!extension_loaded('mbstring')) {
    if (!function_exists('mb_strlen')) {
        
        /**
         * mb_strlen — Short description of the function's behaviour.
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
         * mb_substr — Short description of the function's behaviour.
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
            if ($start < 0) {
                $start = count($arr) + $start;
            }
            return $length === null
                ? implode('', array_slice($arr, $start))
                : implode('', array_slice($arr, $start, $length));
        }
    }
    if (!function_exists('mb_strtolower')) {
        
        /**
         * mb_strtolower — Short description of the function's behaviour.
         *
         * @param string $s
         * @param string $encoding
         * @return string
         */
        function mb_strtolower(string $s, string $encoding = 'UTF-8'): string
        {
            return strtolower($s);
        }
    }
}

// ===========================================================================
// String / Output helpers
// ===========================================================================

/**
 * Escapes a string for safe HTML output, preventing XSS.
 *
 * @param string $string  Raw input.
 * @return string  HTML-safe string.
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Converts a string into a URL-friendly slug.
 *
 * @param string $text  Input text.
 * @return string  Slug.
 */
function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
    $text = preg_replace('/[\s\-]+/', '-', $text);
    return trim($text, '-');
}

/**
 * Formats a UTC date string for human-readable display.
 *
 * @param string $dateString  SQLite datetime string.
 * @param string $format      PHP date() format.
 * @return string
 */
function formatDate(string $dateString, string $format = 'j F Y'): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format($format);
}

/**
 * Formats a date string as "1 January 2025 at 3:45pm".
 *
 * @param string $dateString  SQLite datetime string.
 * @return string
 */
function formatDateTime(string $dateString): string
{
    return formatDate($dateString, 'j F Y \a\t g:ia');
}

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

/**
 * Truncates a string (stripping HTML) to the given character length.
 *
 * @param string $text    Input text (may contain HTML).
 * @param int    $length  Max characters.
 * @return string
 */
function truncate(string $text, int $length = 120): string
{
    $plain = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
    $plain = preg_replace('/\s+/', ' ', trim($plain));
    if (mb_strlen($plain) <= $length) {
        return $plain;
    }
    return mb_substr($plain, 0, $length) . '...';
}

// ===========================================================================
// Avatar / Gravatar helpers
// ===========================================================================

/**
 * Returns a Gravatar URL for the given email address.
 *
 * @param string|null $email   User email address (may be null).
 * @param int         $size    Pixel size (square).
 * @param string      $default Default image type (identicon, mm, etc.).
 * @param string      $rating  Gravatar rating (g, pg, r, x).
 * @return string
 */
function gravatar_url(?string $email, int $size = 80, string $default = 'identicon', string $rating = 'g'): string
{
    $hash = md5(strtolower(trim((string) $email)));
    return 'https://www.gravatar.com/avatar/' . $hash
         . '?s=' . (int) $size
         . '&d=' . rawurlencode($default)
         . '&r=' . rawurlencode($rating);
}

// ===========================================================================
// CSRF Protection
// ===========================================================================

/**
 * Returns (and generates if needed) the session CSRF token.
 *
 * @return string  Hex-encoded token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifies the CSRF token from POST data. Terminates with 403 on failure.
 *
 * @return void
 */
function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

// ===========================================================================
// Flash messages
// ===========================================================================

/**
 * Stores a one-time flash message in the session.
 *
 * @param string $message  Message text.
 * @param string $type     'success' | 'error' | 'info'
 * @return void
 */
function flashMessage(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieves and clears the stored flash message.
 *
 * @return array{message: string, type: string}|null
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ===========================================================================
// Authentication helpers
// ===========================================================================

/**
 * Returns true if a user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

/**
 * Returns the currently logged-in user's data, or null.
 *
 * Caches the result for the duration of the request.
 *
 * @return array<string, mixed>|null
 */
function currentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $user = null;
    if ($user !== null) {
        return $user;
    }

    // CMS mode: return session-based data and ensure a shadow record exists
    // in the forum DB for FK constraints (threads/posts).
    if (defined('CMS_ROOT')) {
        $user = [
            'id'         => (int) $_SESSION['user_id'],
            'username'   => $_SESSION['username'] ?? 'User',
            'email'      => '',
            'bio'        => '',
            'created_at' => '',
            'role'       => $_SESSION['role'] ?? 'user',
        ];
        _ensureForumUserRecord($user['id'], $user['username'], $user['role']);
        return $user;
    }

    // Standalone mode: query the forum DB.
    $stmt = getDB()->prepare(
        "SELECT u.id, u.username, u.email, u.bio, u.created_at, r.name AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id"
    );
    $stmt->execute([':id' => (int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

/**
 * Ensures a shadow user record exists in the forum DB for FK constraints.
 * Only used in CMS mode. Silently no-ops on failure.
 *
 * @param int    $userId
 * @param string $username
 * @param string $role
 * @return void
 */
function _ensureForumUserRecord(int $userId, string $username, string $role): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }
    $ensured = true;
    try {
        $db     = getDB();
        $roleId = $role === 'admin' ? 1 : 2;
        $stmt = $db->prepare(
            "INSERT OR IGNORE INTO users (id, username, email, password_hash, role_id)
             VALUES (:id, :username, :email, '', :role_id)"
        );
        $stmt->execute([
            ':id'      => $userId,
            ':username'=> $username,
            ':email'   => 'cms-user-' . $userId . '@cms.local',
            ':role_id' => $roleId,
        ]);
    } catch (\Exception $e) {
        // Non-fatal: FK errors may surface later but we handle gracefully.
    }
}

/**
 * Returns true if the current user has the admin role.
 *
 * @return bool
 */
function isAdmin(): bool
{
    // CMS mode: trust the role stored in the session.
    if (defined('CMS_ROOT')) {
        return ($_SESSION['role'] ?? '') === 'admin';
    }
    $user = currentUser();
    return $user !== null && $user['role'] === 'admin';
}

/**
 * Redirects to the login page if the visitor is not authenticated.
 *
 * @return void
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        flashMessage('You must be logged in to do that.', 'error');
        $loginUrl = defined('CMS_URL') ? CMS_URL . '/login.php' : SITE_URL . '/login.php';
        redirect($loginUrl);
    }
}

/**
 * Terminates with 403 if the visitor is not an administrator.
 *
 * @return void
 */
function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        exit('Access denied.');
    }
}

// ===========================================================================
// Categories
// ===========================================================================

/**
 * Returns all categories with thread and post counts and last activity.
 *
 * @return array<int, array<string, mixed>>
 */
function getCategories(): array
{
    return getDB()->query(
        "SELECT c.*,
                COUNT(DISTINCT t.id) AS thread_count,
                COUNT(DISTINCT p.id) AS post_count,
                MAX(p.created_at)    AS last_post_at,
                (SELECT t2.title
                 FROM threads t2
                 JOIN posts p2 ON p2.thread_id = t2.id AND p2.is_deleted = 0
                 WHERE t2.category_id = c.id
                 ORDER BY p2.created_at DESC LIMIT 1) AS last_thread_title,
                (SELECT t2.slug
                 FROM threads t2
                 JOIN posts p2 ON p2.thread_id = t2.id AND p2.is_deleted = 0
                 WHERE t2.category_id = c.id
                 ORDER BY p2.created_at DESC LIMIT 1) AS last_thread_slug,
                (SELECT u2.username
                 FROM users u2
                 JOIN posts p2 ON p2.user_id = u2.id AND p2.is_deleted = 0
                 JOIN threads t2 ON t2.id = p2.thread_id
                 WHERE t2.category_id = c.id
                 ORDER BY p2.created_at DESC LIMIT 1) AS last_poster
         FROM categories c
         LEFT JOIN threads t ON t.category_id = c.id
         LEFT JOIN posts   p ON p.thread_id   = t.id AND p.is_deleted = 0
         GROUP BY c.id
         ORDER BY c.display_order ASC, c.name ASC"
    )->fetchAll();
}

/**
 * Returns a single category by its ID, or null.
 *
 * @param int $id  Category ID.
 * @return array<string, mixed>|null
 */
function getCategoryById(int $id): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Returns a single category by its slug, or null.
 *
 * @param string $slug  URL slug.
 * @return array<string, mixed>|null
 */
function getCategoryBySlug(string $slug): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM categories WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
    return $stmt->fetch() ?: null;
}

// ===========================================================================
// Threads
// ===========================================================================

/**
 * Returns a paginated list of threads for a category, sticky threads first.
 *
 * @param int $categoryId  Category ID.
 * @param int $page        Current page (1-based).
 * @param int $perPage     Threads per page.
 * @return array<int, array<string, mixed>>
 */
function getThreadsByCategory(int $categoryId, int $page = 1, int $perPage = THREADS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt   = getDB()->prepare(
        "SELECT t.*,
                u.username                                                  AS author_name,
                COUNT(p.id)                                                 AS post_count,
                MAX(p.created_at)                                           AS last_post_at,
                (SELECT u2.username
                 FROM posts p2
                 JOIN users u2 ON u2.id = p2.user_id
                 WHERE p2.thread_id = t.id AND p2.is_deleted = 0
                 ORDER BY p2.created_at DESC LIMIT 1)                      AS last_poster
         FROM threads t
         JOIN users u      ON u.id = t.user_id
         LEFT JOIN posts p ON p.thread_id = t.id AND p.is_deleted = 0
         WHERE t.category_id = :cid
         GROUP BY t.id
         ORDER BY t.is_sticky DESC, COALESCE(MAX(p.created_at), t.created_at) DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->execute([':cid' => $categoryId, ':limit' => $perPage, ':offset' => $offset]);
    return $stmt->fetchAll();
}

/**
 * Returns the total number of threads in a category.
 *
 * @param int $categoryId  Category ID.
 * @return int
 */
function countThreadsByCategory(int $categoryId): int
{
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM threads WHERE category_id = :cid");
    $stmt->execute([':cid' => $categoryId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returns a thread by its ID, joined with author and category data, or null.
 *
 * @param int $id  Thread ID.
 * @return array<string, mixed>|null
 */
function getThreadById(int $id): ?array
{
    $stmt = getDB()->prepare(
        "SELECT t.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug
         FROM threads t
         JOIN users u      ON u.id = t.user_id
         JOIN categories c ON c.id = t.category_id
         WHERE t.id = :id"
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Generates a unique slug for a thread title.
 *
 * @param string $title  Thread title.
 * @return string  Unique slug.
 */
function generateUniqueThreadSlug(string $title): string
{
    $base = slugify($title);
    if ($base === '') {
        $base = 'thread';
    }
    $slug = $base;
    $n    = 2;
    $stmt = getDB()->prepare("SELECT id FROM threads WHERE slug = :slug");
    do {
        $stmt->execute([':slug' => $slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $base . '-' . $n++;
    } while (true);
    return $slug;
}

/**
 * Increments the view counter for a thread.
 *
 * @param int $threadId  Thread ID.
 * @return void
 */
function incrementThreadViews(int $threadId): void
{
    $stmt = getDB()->prepare("UPDATE threads SET view_count = view_count + 1 WHERE id = :id");
    $stmt->execute([':id' => $threadId]);
}

// ===========================================================================
// Posts
// ===========================================================================

/**
 * Returns a paginated list of non-deleted posts for a thread, oldest first.
 *
 * @param int $threadId  Thread ID.
 * @param int $page      Current page (1-based).
 * @param int $perPage   Posts per page.
 * @return array<int, array<string, mixed>>
 */
function getPostsByThread(int $threadId, int $page = 1, int $perPage = POSTS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt   = getDB()->prepare(
        "SELECT p.*, u.username AS author_name, u.bio AS author_bio, u.created_at AS author_joined
         , u.email AS author_email
         FROM posts p
         JOIN users u ON u.id = p.user_id
         WHERE p.thread_id = :tid AND p.is_deleted = 0
         ORDER BY p.created_at ASC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->execute([':tid' => $threadId, ':limit' => $perPage, ':offset' => $offset]);
    return $stmt->fetchAll();
}

/**
 * Returns the total number of non-deleted posts in a thread.
 *
 * @param int $threadId  Thread ID.
 * @return int
 */
function countPostsByThread(int $threadId): int
{
    $stmt = getDB()->prepare(
        "SELECT COUNT(*) FROM posts WHERE thread_id = :tid AND is_deleted = 0"
    );
    $stmt->execute([':tid' => $threadId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returns the total post count for a given user (excluding deleted posts).
 *
 * @param int $userId  User ID.
 * @return int
 */
function getUserPostCount(int $userId): int
{
    $stmt = getDB()->prepare(
        "SELECT COUNT(*) FROM posts WHERE user_id = :uid AND is_deleted = 0"
    );
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

/**
 * Returns the total thread count for a given user.
 *
 * @param int $userId  User ID.
 * @return int
 */
function getUserThreadCount(int $userId): int
{
    $stmt = getDB()->prepare("SELECT COUNT(*) FROM threads WHERE user_id = :uid");
    $stmt->execute([':uid' => $userId]);
    return (int) $stmt->fetchColumn();
}

// ===========================================================================
// Create content
// ===========================================================================

/**
 * Creates a new thread and its opening post in a single transaction.
 *
 * @param int    $categoryId  Category ID.
 * @param int    $userId      Author's user ID.
 * @param string $title       Thread title (unsanitised - stored raw, escaped on output).
 * @param string $content     Opening post content.
 * @return int  The new thread's ID.
 */
function createThread(int $categoryId, int $userId, string $title, string $content): int
{
    $db   = getDB();
    $slug = generateUniqueThreadSlug($title);

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO threads (title, slug, category_id, user_id)
             VALUES (:title, :slug, :cid, :uid)"
        );
        $stmt->execute([
            ':title' => $title,
            ':slug'  => $slug,
            ':cid'   => $categoryId,
            ':uid'   => $userId,
        ]);
        $threadId = (int) $db->lastInsertId();

        $stmt = $db->prepare(
            "INSERT INTO posts (thread_id, user_id, content) VALUES (:tid, :uid, :content)"
        );
        $stmt->execute([':tid' => $threadId, ':uid' => $userId, ':content' => $content]);

        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        throw $e;
    }

    return $threadId;
}

/**
 * Creates a reply post and updates the thread's updated_at timestamp.
 *
 * @param int    $threadId  Thread ID.
 * @param int    $userId    Author's user ID.
 * @param string $content   Post content.
 * @return int  The new post's ID.
 */
function createPost(int $threadId, int $userId, string $content): int
{
    $db   = getDB();
    $stmt = $db->prepare(
        "INSERT INTO posts (thread_id, user_id, content) VALUES (:tid, :uid, :content)"
    );
    $stmt->execute([':tid' => $threadId, ':uid' => $userId, ':content' => $content]);
    $postId = (int) $db->lastInsertId();

    $db->prepare("UPDATE threads SET updated_at = datetime('now') WHERE id = :id")
       ->execute([':id' => $threadId]);

    return $postId;
}

// ===========================================================================
// Search
// ===========================================================================

/**
 * Searches thread titles and returns a paginated result set.
 *
 * @param string $query    Search term.
 * @param int    $page     Current page (1-based).
 * @param int    $perPage  Results per page.
 * @return array<int, array<string, mixed>>
 */
function searchThreads(string $query, int $page = 1, int $perPage = THREADS_PER_PAGE): array
{
    $offset  = ($page - 1) * $perPage;
    $pattern = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';
    $stmt    = getDB()->prepare(
        "SELECT t.*, u.username AS author_name, c.name AS category_name, c.slug AS category_slug,
                COUNT(p.id) AS post_count
         FROM threads t
         JOIN users u      ON u.id = t.user_id
         JOIN categories c ON c.id = t.category_id
         LEFT JOIN posts p ON p.thread_id = t.id AND p.is_deleted = 0
         WHERE t.title LIKE :q ESCAPE '\\'
         GROUP BY t.id
         ORDER BY t.updated_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->execute([':q' => $pattern, ':limit' => $perPage, ':offset' => $offset]);
    return $stmt->fetchAll();
}

/**
 * Returns the total number of threads matching a search query.
 *
 * @param string $query  Search term.
 * @return int
 */
function countSearchResults(string $query): int
{
    $pattern = '%' . str_replace(['%', '_'], ['\%', '\_'], $query) . '%';
    $stmt    = getDB()->prepare(
        "SELECT COUNT(*) FROM threads WHERE title LIKE :q ESCAPE '\\'"
    );
    $stmt->execute([':q' => $pattern]);
    return (int) $stmt->fetchColumn();
}

// ===========================================================================
// Users
// ===========================================================================

/**
 * Returns a user by ID (with role name), or null.
 *
 * @param int $id  User ID.
 * @return array<string, mixed>|null
 */
function getUserById(int $id): ?array
{
    $stmt = getDB()->prepare(
        "SELECT u.id, u.username, u.email, u.bio, u.created_at, r.name AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         WHERE u.id = :id"
    );
    $stmt->execute([':id' => $id]);
    return $stmt->fetch() ?: null;
}

/**
 * Returns a paginated list of all users (admin use).
 *
 * @param int $page     Current page (1-based).
 * @param int $perPage  Users per page.
 * @return array<int, array<string, mixed>>
 */
function getAllUsers(int $page = 1, int $perPage = 30): array
{
    $offset = ($page - 1) * $perPage;
    $stmt   = getDB()->prepare(
        "SELECT u.id, u.username, u.email, u.bio, u.created_at, r.name AS role
         FROM users u
         JOIN roles r ON r.id = u.role_id
         ORDER BY u.created_at DESC
         LIMIT :limit OFFSET :offset"
    );
    $stmt->execute([':limit' => $perPage, ':offset' => $offset]);
    return $stmt->fetchAll();
}

/**
 * Returns the total number of registered users.
 *
 * @return int
 */
function countUsers(): int
{
    return (int) getDB()->query("SELECT COUNT(*) FROM users")->fetchColumn();
}

// ===========================================================================
// Pagination
// ===========================================================================

/**
 * Builds a pagination navigation HTML string.
 *
 * @param int    $total       Total number of items.
 * @param int    $perPage     Items per page.
 * @param int    $currentPage Current page (1-based).
 * @param string $baseUrl     Base URL (without the page param).
 * @return string  HTML or empty string if pagination is not needed.
 */
function renderPagination(int $total, int $perPage, int $currentPage, string $baseUrl): string
{
    if ($perPage <= 0 || $total <= $perPage) {
        return '';
    }

    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages <= 1) {
        return '';
    }

    $sep  = str_contains($baseUrl, '?') ? '&' : '?';
    $html = '<nav class="pagination" aria-label="Pagination">';

    // Previous
    if ($currentPage > 1) {
        $html .= '<a href="' . e($baseUrl . $sep . 'page=' . ($currentPage - 1)) . '">&laquo; Previous</a>';
    } else {
        $html .= '<span class="disabled">&laquo; Previous</span>';
    }

    // Page numbers
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<a href="' . e($baseUrl . $sep . 'page=1') . '">1</a>';
        if ($start > 2) {
            $html .= '<span class="disabled">...</span>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . e($baseUrl . $sep . 'page=' . $i) . '">' . $i . '</a>';
        }
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="disabled">...</span>';
        }
        $html .= '<a href="' . e($baseUrl . $sep . 'page=' . $totalPages) . '">' . $totalPages . '</a>';
    }

    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . e($baseUrl . $sep . 'page=' . ($currentPage + 1)) . '">Next &raquo;</a>';
    } else {
        $html .= '<span class="disabled">Next &raquo;</span>';
    }

    $html .= '</nav>';
    return $html;
}
