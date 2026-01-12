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
