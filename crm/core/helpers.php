<?php
/**
 * crm/core/helpers.php
 *
 * Shared utility functions used throughout the CRM module.
 * Provides activity logging, pagination, date formatting, CSV export,
 * tag management, and entity-lookup helpers.
 */

// ---------------------------------------------------------------------------
// Activity logging
// ---------------------------------------------------------------------------

/**
 * Appends a record to the CRM audit trail.
 *
 * @param string $action      Short verb: 'created', 'updated', 'deleted', etc.
 * @param string $entityType  Entity class: 'customer', 'lead', 'task', etc.
 * @param int    $entityId    Primary key of the affected row.
 * @param string $description Human-readable summary for the activity feed.
 * @return void
 */
function crmLogActivity(string $action, string $entityType, int $entityId, string $description = ''): void
{
    $userId = (int) ($_SESSION['user_id'] ?? 0);
    // IP is recorded for audit purposes — not exposed publicly.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';

    $stmt = getCRMDB()->prepare(
        "INSERT INTO crm_activity_log (user_id, action, entity_type, entity_id, description, ip_address)
         VALUES (:uid, :action, :etype, :eid, :desc, :ip)"
    );
    $stmt->execute([
        ':uid'    => $userId,
        ':action' => $action,
        ':etype'  => $entityType,
        ':eid'    => $entityId,
        ':desc'   => $description,
        ':ip'     => $ip,
    ]);
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

/**
 * Calculates simple pagination metadata.
 *
 * @param int $total    Total number of records.
 * @param int $perPage  Records per page.
 * @param int $current  Current page number (1-based).
 * @return array{total: int, per_page: int, current: int, pages: int, offset: int}
 */
function crmPaginate(int $total, int $perPage = 25, int $current = 1): array
{
    $pages   = max(1, (int) ceil($total / $perPage));
    $current = max(1, min($current, $pages));
    return [
        'total'    => $total,
        'per_page' => $perPage,
        'current'  => $current,
        'pages'    => $pages,
        'offset'   => ($current - 1) * $perPage,
    ];
}

/**
 * Renders a simple next/previous pagination bar.
 * Returns an HTML string ready for echo.
 *
 * @param array  $pag   Output of crmPaginate().
 * @param string $base  Base URL — query string will be appended.
 * @param array  $extra Additional query params to preserve across pages.
 * @return string
 */
function crmPaginationHtml(array $pag, string $base, array $extra = []): string
{
    if ($pag['pages'] <= 1) {
        return '';
    }

    $html = '<nav class="crm-pagination" aria-label="Pagination"><ul>';

    for ($i = 1; $i <= $pag['pages']; $i++) {
        $params  = array_merge($extra, ['page' => $i]);
        $url     = $base . '?' . http_build_query($params);
        $active  = $i === $pag['current'] ? ' class="crm-pagination__item--active"' : '';
        $html   .= "<li{$active}><a href=\"" . htmlspecialchars($url, ENT_QUOTES) . "\">{$i}</a></li>";
    }

    $html .= '</ul></nav>';
    return $html;
}

// ---------------------------------------------------------------------------
// Date / time helpers
// ---------------------------------------------------------------------------

/**
 * Formats a UTC SQLite datetime for display.
 *
 * @param string $dateStr  Raw datetime string from the database.
 * @param string $format   PHP date format string.
 * @return string
 */
function crmFormatDate(string $dateStr, string $format = 'j M Y'): string
{
    if (empty($dateStr)) {
        return '—';
    }
    try {
        $dt = new DateTimeImmutable($dateStr, new DateTimeZone('UTC'));
        return $dt->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($dateStr, ENT_QUOTES);
    }
}

/**
 * Returns a human-friendly "time ago" string.
 *
 * @param string $dateStr  UTC datetime string.
 * @return string
 */
function crmTimeAgo(string $dateStr): string
{
    if (empty($dateStr)) {
        return '—';
    }
    try {
        $then = new DateTimeImmutable($dateStr, new DateTimeZone('UTC'));
        $now  = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $diff = $now->getTimestamp() - $then->getTimestamp();

        if ($diff < 60)      return 'just now';
        if ($diff < 3600)    return floor($diff / 60) . 'm ago';
        if ($diff < 86400)   return floor($diff / 3600) . 'h ago';
        if ($diff < 604800)  return floor($diff / 86400) . 'd ago';
        return crmFormatDate($dateStr);
    } catch (Exception $e) {
        return htmlspecialchars($dateStr, ENT_QUOTES);
    }
}

// ---------------------------------------------------------------------------
// Tag helpers
// ---------------------------------------------------------------------------

/**
 * Retrieves all tags for a given entity.
 *
 * @param string $type  Entity type: 'customer', 'lead', etc.
 * @param int    $id    Entity primary key.
 * @return array<array{id: int, name: string, colour: string}>
 */
function crmGetTags(string $type, int $id): array
{
    $stmt = getCRMDB()->prepare(
        "SELECT t.id, t.name, t.colour
         FROM crm_tags t
         JOIN crm_taggables tg ON tg.tag_id = t.id
         WHERE tg.taggable_type = :type AND tg.taggable_id = :id
         ORDER BY t.name ASC"
    );
    $stmt->execute([':type' => $type, ':id' => $id]);
    return $stmt->fetchAll();
}

/**
 * Replaces all tags on an entity with a new set by name.
 * Creates missing tags on the fly.
 *
 * @param string        $type     Entity type.
 * @param int           $entityId Entity primary key.
 * @param array<string> $names    Tag names to apply.
 * @return void
 */
function crmSetTags(string $type, int $entityId, array $names): void
{
    $db = getCRMDB();

    // Remove existing assignments before re-inserting.
    $db->prepare(
        "DELETE FROM crm_taggables WHERE taggable_type = :type AND taggable_id = :id"
    )->execute([':type' => $type, ':id' => $entityId]);

    foreach ($names as $name) {
        $name = trim($name);
        if ($name === '') {
            continue;
        }

        // Insert tag if new; ignore if already exists.
        $db->prepare(
            "INSERT OR IGNORE INTO crm_tags (name) VALUES (:name)"
        )->execute([':name' => $name]);

        $tagId = (int) $db->prepare(
            "SELECT id FROM crm_tags WHERE name = :name"
        )->execute([':name' => $name]) ? $db->query("SELECT id FROM crm_tags WHERE name = " . $db->quote($name))->fetchColumn() : 0;

        if ($tagId > 0) {
            $db->prepare(
                "INSERT OR IGNORE INTO crm_taggables (tag_id, taggable_type, taggable_id)
                 VALUES (:tid, :type, :eid)"
            )->execute([':tid' => $tagId, ':type' => $type, ':eid' => $entityId]);
        }
    }
}

// ---------------------------------------------------------------------------
// CSV export
// ---------------------------------------------------------------------------

/**
 * Streams a 2D array as a CSV download.
 * Exits after sending — call at the very end of a controller action.
 *
 * @param array<array<string|int|float>> $rows     Data rows (first row = headers).
 * @param string                          $filename Suggested download filename.
 * @return never
 */
function crmExportCsv(array $rows, string $filename = 'export.csv'): never
{
    // Sanitise filename to prevent header injection.
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $fp = fopen('php://output', 'wb');
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

// ---------------------------------------------------------------------------
// Dashboard stats
// ---------------------------------------------------------------------------

/**
 * Returns aggregate counts used on the CRM dashboard.
 *
 * @return array<string, int>
 */
function getCRMStats(): array
{
    $db = getCRMDB();
    return [
        'total_customers'  => (int) $db->query("SELECT COUNT(*) FROM crm_customers")->fetchColumn(),
        'active_customers' => (int) $db->query("SELECT COUNT(*) FROM crm_customers WHERE status = 'active'")->fetchColumn(),
        'total_leads'      => (int) $db->query("SELECT COUNT(*) FROM crm_leads")->fetchColumn(),
        'open_leads'       => (int) $db->query("SELECT COUNT(*) FROM crm_leads WHERE stage NOT IN ('won','lost')")->fetchColumn(),
        'total_companies'  => (int) $db->query("SELECT COUNT(*) FROM crm_companies")->fetchColumn(),
        'tasks_today'      => (int) $db->query(
            "SELECT COUNT(*) FROM crm_tasks
             WHERE date(due_date) = date('now') AND status NOT IN ('completed','cancelled')"
        )->fetchColumn(),
        'tasks_overdue'    => (int) $db->query(
            "SELECT COUNT(*) FROM crm_tasks
             WHERE due_date < datetime('now') AND status NOT IN ('completed','cancelled')"
        )->fetchColumn(),
        'unread_messages'  => (int) $db->prepare(
            "SELECT COUNT(*) FROM crm_messages WHERE recipient_id = :uid AND is_read = 0"
        )->execute([':uid' => (int) ($_SESSION['user_id'] ?? 0)]) ?
            $db->prepare("SELECT COUNT(*) FROM crm_messages WHERE recipient_id = :uid AND is_read = 0")
               ->execute([':uid' => (int) ($_SESSION['user_id'] ?? 0)]) ? 0 : 0
            : 0,
    ];
}

/**
 * Returns unread message count for the current user.
 *
 * @return int
 */
function crmUnreadMessageCount(): int
{
    $uid = (int) ($_SESSION['user_id'] ?? 0);
    if ($uid === 0) {
        return 0;
    }
    $stmt = getCRMDB()->prepare(
        "SELECT COUNT(*) FROM crm_messages WHERE recipient_id = :uid AND is_read = 0"
    );
    $stmt->execute([':uid' => $uid]);
    return (int) $stmt->fetchColumn();
}

// ---------------------------------------------------------------------------
// Lead stage display helpers
// ---------------------------------------------------------------------------

/**
 * Returns a human-readable label for a lead stage key.
 *
 * @param string $stage  Stage key from the leads table.
 * @return string
 */
function crmLeadStageLabel(string $stage): string
{
    return match($stage) {
        'new'          => 'New',
        'contacted'    => 'Contacted',
        'qualified'    => 'Qualified',
        'proposal'     => 'Proposal',
        'negotiation'  => 'Negotiation',
        'won'          => 'Won',
        'lost'         => 'Lost',
        default        => ucfirst($stage),
    };
}

/**
 * Returns a badge CSS modifier class for a lead stage.
 *
 * @param string $stage
 * @return string
 */
function crmLeadStageBadge(string $stage): string
{
    return match($stage) {
        'new'         => 'badge--info',
        'contacted'   => 'badge--info',
        'qualified'   => 'badge--warning',
        'proposal'    => 'badge--warning',
        'negotiation' => 'badge--warning',
        'won'         => 'badge--success',
        'lost'        => 'badge--danger',
        default       => 'badge--muted',
    };
}

/**
 * Returns a badge CSS modifier class for a task/customer status.
 *
 * @param string $status
 * @return string
 */
function crmStatusBadge(string $status): string
{
    return match($status) {
        'active', 'completed', 'won'            => 'badge--success',
        'pending', 'in_progress', 'prospect'    => 'badge--warning',
        'cancelled', 'lost', 'churned'          => 'badge--danger',
        'inactive'                              => 'badge--muted',
        default                                 => 'badge--muted',
    };
}

// ---------------------------------------------------------------------------
// User lookup (bridging to CMS users table)
// ---------------------------------------------------------------------------

/**
 * Returns all CMS users available as CRM assignees.
 * Uses the CMS database connection, not the CRM one.
 *
 * @return array<array{id: int, username: string, email: string}>
 */
function crmGetUsers(): array
{
    $stmt = getCMSDB()->query(
        "SELECT u.id, u.username, u.email
         FROM users u
         ORDER BY u.username ASC"
    );
    return $stmt->fetchAll();
}

/**
 * Returns a username string for a given CMS user ID.
 *
 * @param int $userId
 * @return string
 */
function crmUsername(int $userId): string
{
    if ($userId <= 0) {
        return '—';
    }
    static $cache = [];
    if (!isset($cache[$userId])) {
        $stmt = getCMSDB()->prepare("SELECT username FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $cache[$userId] = $stmt->fetchColumn() ?: '—';
    }
    return (string) $cache[$userId];
}

// ---------------------------------------------------------------------------
// Flash messages — thin wrapper re-using CMS flash functions
// ---------------------------------------------------------------------------

/**
 * Stores a one-time flash message for the next page load.
 * Re-uses the CMS flash mechanism so the same session key is used.
 *
 * @param string $message
 * @param string $type  'success'|'error'|'info'
 * @return void
 */
function crmFlash(string $message, string $type = 'success'): void
{
    // cmsFlashMessage is already loaded from the CMS core.
    cmsFlashMessage($message, $type);
}

/**
 * Retrieves and clears the pending flash message.
 *
 * @return array{message: string, type: string}|null
 */
function crmGetFlash(): ?array
{
    return cmsGetFlash();
}

// ---------------------------------------------------------------------------
// Redirect helper
// ---------------------------------------------------------------------------

/**
 * Issues an HTTP redirect to a CRM-relative or absolute URL.
 * Reuses the CMS redirect() helper if available.
 *
 * @param string $url
 * @return never
 */
function crmRedirect(string $url): never
{
    if (function_exists('redirect')) {
        redirect($url);
    }
    header('Location: ' . $url);
    exit;
}

// ---------------------------------------------------------------------------
// CSRF passthrough — re-use the CMS implementation
// ---------------------------------------------------------------------------

/**
 * Returns the CSRF token string (alias for cmsCsrfToken).
 *
 * @return string
 */
function crmCsrfToken(): string
{
    return cmsCsrfToken();
}

/**
 * Validates a submitted CSRF token (alias for cmsValidateCsrf).
 *
 * @param string $token  Value from the submitted form.
 * @return bool
 */
function crmValidateCsrf(string $token): bool
{
    return cmsValidateCsrf($token);
}
