<?php
/**
 * calendar/functions.php
 *
 * Global helper functions for the Calendar module.
 * All database queries use PDO prepared statements.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';

// ---------------------------------------------------------------------------
// mbstring compatibility shim
// ---------------------------------------------------------------------------
if (!extension_loaded('mbstring')) {
    if (!function_exists('mb_strlen')) {
        /**
         * UTF-8-aware strlen fallback for environments without mbstring.
         *
         * @param string $s        Input string.
         * @param string $enc      Character encoding (ignored; UTF-8 assumed).
         * @return int  Character count.
         */
        function mb_strlen(string $s, string $enc = 'UTF-8'): int
        {
            if ($s === '') return 0;
            preg_match_all('/./us', $s, $m);
            return count($m[0]);
        }
    }
    if (!function_exists('mb_substr')) {
        /**
         * UTF-8-aware substr fallback for environments without mbstring.
         *
         * @param string   $s      Input string.
         * @param int      $start  Start position (negative counts from end).
         * @param int|null $len    Maximum number of characters; null returns the rest.
         * @param string   $enc    Character encoding (ignored; UTF-8 assumed).
         * @return string  Extracted substring.
         */
        function mb_substr(string $s, int $start, ?int $len = null, string $enc = 'UTF-8'): string
        {
            if ($s === '') return '';
            preg_match_all('/./us', $s, $m);
            $arr = $m[0];
            if ($start < 0) $start = count($arr) + $start;
            return implode('', $len === null ? array_slice($arr, $start) : array_slice($arr, $start, $len));
        }
    }
}

// ---------------------------------------------------------------------------
// Output / security helpers
// ---------------------------------------------------------------------------

/**
 * HTML-encodes a value for safe output.
 *
 * @param mixed $val  Value to encode.
 * @return string
 */
function e(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


/**
 * Redirects the browser and halts execution.
 *
 * @param string $url
 * @return never
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Generates (or returns) the CSRF token stored in the session.
 *
 * @return string  64-char hex token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['cal_csrf'])) {
        $_SESSION['cal_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['cal_csrf'];
}

/**
 * Validates a submitted CSRF token against the session token.
 *
 * @param string $submitted
 * @return bool
 */
function validateCsrf(string $submitted): bool
{
    $token = $_SESSION['cal_csrf'] ?? '';
    return $token !== '' && hash_equals($token, $submitted);
}

// ---------------------------------------------------------------------------
// Flash messages
// ---------------------------------------------------------------------------

/**
 * Stores a one-time flash message in the session.
 *
 * @param string $msg
 * @param string $type  'success' | 'error'
 */
function flashMessage(string $msg, string $type = 'success'): void
{
    $_SESSION['cal_flash'] = ['message' => $msg, 'type' => $type];
}

/**
 * Retrieves and clears the stored flash message.
 *
 * @return array{message:string, type:string}|null
 */
function getFlash(): ?array
{
    if (!empty($_SESSION['cal_flash'])) {
        $flash = $_SESSION['cal_flash'];
        unset($_SESSION['cal_flash']);
        return $flash;
    }
    return null;
}

/**
 * Renders the flash message HTML if one is present.
 */
function renderFlash(): void
{
    $flash = getFlash();
    if ($flash !== null) {
        $cls = $flash['type'] === 'error' ? 'alert--error' : 'alert--success';
        echo '<div class="alert ' . $cls . '" role="alert">' . e($flash['message']) . '</div>';
    }
}

// ---------------------------------------------------------------------------
// Date helpers
// ---------------------------------------------------------------------------

/**
 * Formats a datetime string for human-readable display.
 *
 * @param string $dt      SQLite datetime string.
 * @param string $format  PHP date() format.
 * @return string
 */
function formatDate(string $dt, string $format = 'j F Y'): string
{
    return (new DateTime($dt))->format($format);
}

/**
 * Formats a datetime string for a datetime-local HTML input.
 *
 * @param string $dt
 * @return string  e.g. "2026-04-15T10:00"
 */
function toInputDatetime(string $dt): string
{
    return (new DateTime($dt))->format('Y-m-d\TH:i');
}

/**
 * Formats a datetime string as a human-friendly short form.
 *
 * @param string $dt
 * @return string  e.g. "15 Apr 2026, 10:00"
 */
function formatDatetime(string $dt): string
{
    return (new DateTime($dt))->format('j M Y, H:i');
}

// ---------------------------------------------------------------------------
// Event CRUD
// ---------------------------------------------------------------------------

/**
 * Returns a paginated list of all events, ordered by start time.
 *
 * @param int $page
 * @param int $perPage
 * @return array<int, array<string, mixed>>
 */
function getEvents(int $page = 1, int $perPage = EVENTS_PER_PAGE): array
{
    $offset = ($page - 1) * $perPage;
    $stmt   = getDB()->prepare(
        "SELECT * FROM cal_events ORDER BY start_datetime ASC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Returns the total number of events.
 *
 * @return int
 */
function countEvents(): int
{
    return (int) getDB()->query("SELECT COUNT(*) FROM cal_events")->fetchColumn();
}

/**
 * Returns a single event by ID, or null if not found.
 *
 * @param int $id
 * @return array<string, mixed>|null
 */
function getEvent(int $id): ?array
{
    $stmt = getDB()->prepare("SELECT * FROM cal_events WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Returns all events that start within a given calendar month.
 *
 * @param int $year   e.g. 2026
 * @param int $month  1–12
 * @return array<int, array<string, mixed>>
 */
function getEventsByMonth(int $year, int $month): array
{
    $start  = sprintf('%04d-%02d-01 00:00:00', $year, $month);
    $nextY  = $month === 12 ? $year + 1 : $year;
    $nextM  = $month === 12 ? 1 : $month + 1;
    $end    = sprintf('%04d-%02d-01 00:00:00', $nextY, $nextM);

    $stmt = getDB()->prepare(
        "SELECT * FROM cal_events
         WHERE start_datetime >= :start AND start_datetime < :end
         ORDER BY start_datetime ASC"
    );
    $stmt->execute([':start' => $start, ':end' => $end]);
    return $stmt->fetchAll();
}

/**
 * Returns all public events ordered by start time (used for sync feed).
 *
 * @return array<int, array<string, mixed>>
 */
function getPublicEvents(): array
{
    return getDB()
        ->query("SELECT * FROM cal_events WHERE is_public = 1 ORDER BY start_datetime ASC")
        ->fetchAll();
}

/**
 * Returns all events (for admin export).
 *
 * @return array<int, array<string, mixed>>
 */
function getAllEvents(): array
{
    return getDB()
        ->query("SELECT * FROM cal_events ORDER BY start_datetime ASC")
        ->fetchAll();
}

/**
 * Returns the N nearest upcoming events from now.
 *
 * @param int $limit
 * @return array<int, array<string, mixed>>
 */
function getUpcomingEvents(int $limit = 5): array
{
    $now  = date('Y-m-d H:i:s');
    $stmt = getDB()->prepare(
        "SELECT * FROM cal_events WHERE start_datetime >= :now ORDER BY start_datetime ASC LIMIT :lim"
    );
    $stmt->bindValue(':now', $now);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Creates a new event and returns its new ID.
 *
 * @param array<string, mixed> $data
 * @return int  New event ID.
 */
function createEvent(array $data): int
{
    $stmt = getDB()->prepare(
        "INSERT INTO cal_events
             (user_id, title, description, start_datetime, end_datetime, location, is_public)
         VALUES
             (:user_id, :title, :description, :start_datetime, :end_datetime, :location, :is_public)"
    );
    $stmt->execute([
        ':user_id'        => $data['user_id']        ?? null,
        ':title'          => $data['title'],
        ':description'    => $data['description']    ?? '',
        ':start_datetime' => $data['start_datetime'],
        ':end_datetime'   => $data['end_datetime'],
        ':location'       => $data['location']       ?? '',
        ':is_public'      => isset($data['is_public']) ? (int) $data['is_public'] : 1,
    ]);
    return (int) getDB()->lastInsertId();
}

/**
 * Updates an existing event.
 *
 * @param int                  $id
 * @param array<string, mixed> $data
 */
function updateEvent(int $id, array $data): void
{
    $stmt = getDB()->prepare(
        "UPDATE cal_events
         SET title          = :title,
             description    = :description,
             start_datetime = :start_datetime,
             end_datetime   = :end_datetime,
             location       = :location,
             is_public      = :is_public,
             updated_at     = datetime('now')
         WHERE id = :id"
    );
    $stmt->execute([
        ':title'          => $data['title'],
        ':description'    => $data['description']    ?? '',
        ':start_datetime' => $data['start_datetime'],
        ':end_datetime'   => $data['end_datetime'],
        ':location'       => $data['location']       ?? '',
        ':is_public'      => isset($data['is_public']) ? (int) $data['is_public'] : 1,
        ':id'             => $id,
    ]);
}

/**
 * Deletes an event by ID.
 *
 * @param int $id
 */
function deleteEvent(int $id): void
{
    $stmt = getDB()->prepare("DELETE FROM cal_events WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

// ---------------------------------------------------------------------------
// Sync token helpers
// ---------------------------------------------------------------------------

/**
 * Creates a new sync token and returns the raw token string.
 *
 * @param int|null $userId
 * @param string   $label
 * @return string  48-char hex token.
 */
function createSyncToken(?int $userId, string $label = 'My Calendar'): string
{
    $token = bin2hex(random_bytes(24));
    $stmt  = getDB()->prepare(
        "INSERT INTO cal_tokens (user_id, token, label) VALUES (:user_id, :token, :label)"
    );
    $stmt->execute([':user_id' => $userId, ':token' => $token, ':label' => $label]);
    return $token;
}

/**
 * Returns true if the token exists and is active.
 *
 * @param string $token
 * @return bool
 */
function validateSyncToken(string $token): bool
{
    $stmt = getDB()->prepare(
        "SELECT id FROM cal_tokens WHERE token = :token AND is_active = 1"
    );
    $stmt->execute([':token' => $token]);
    return $stmt->fetchColumn() !== false;
}

/**
 * Returns all active sync tokens.
 *
 * @return array<int, array<string, mixed>>
 */
function getSyncTokens(): array
{
    return getDB()
        ->query("SELECT * FROM cal_tokens WHERE is_active = 1 ORDER BY created_at DESC")
        ->fetchAll();
}

/**
 * Deactivates a sync token by ID.
 *
 * @param int $id
 */
function revokeSyncToken(int $id): void
{
    $stmt = getDB()->prepare("UPDATE cal_tokens SET is_active = 0 WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

// ---------------------------------------------------------------------------
// Auth helpers (standalone mode only)
// ---------------------------------------------------------------------------

/**
 * Returns the currently logged-in admin user, or null.
 * Supports both CMS (`user_id`) and standalone admin (`admin_id`) sessions.
 *
 * @return array<string, mixed>|null
 */
function currentUser(): ?array
{
    if (defined('CMS_ROOT')) {
        // CMS mode: user_id set by CMS auth
        $id = (int) ($_SESSION['user_id'] ?? 0);
        if ($id === 0) return null;
        return [
            'id'       => $id,
            'username' => $_SESSION['username'] ?? '',
            'email'    => $_SESSION['email']    ?? '',
            'role'     => $_SESSION['role']     ?? 'user',
        ];
    }

    // Standalone mode
    $id = (int) ($_SESSION['admin_id'] ?? 0);
    if ($id === 0) return null;

    $stmt = getDB()->prepare("SELECT id, username, email FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Returns true when an admin session is active.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    if (defined('CMS_ROOT')) {
        return !empty($_SESSION['user_id']);
    }
    return !empty($_SESSION['admin_id']);
}
