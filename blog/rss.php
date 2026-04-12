<?php
/**
 * rss.php
 *
 * Generates an RSS 2.0 feed of the most recent published blog posts.
 *
 * Outputs a valid XML document with UTF-8 encoding.  The feed includes
 * the 20 most recently published posts, each with:
 *   - Title, link (clean URL), description (excerpt), author, pubDate,
 *     category, and a unique GUID.
 *
 * Sends the correct Content-Type header (`application/rss+xml`) so feed
 * readers can parse it without configuration.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/functions.php';

/**
 * Formats a UTC date string as an RFC 2822 date for RSS <pubDate>.
 *
 * RSS 2.0 requires dates in RFC 822 / RFC 2822 format
 * (e.g. "Wed, 08 Apr 2026 12:00:00 +0000").
 *
 * @param string $dateString  SQLite datetime string (e.g. "2026-04-08 12:00:00").
 *
 * @return string  RFC 2822-formatted date string.
 */
function rssDate(string $dateString): string
{
    $dt = new DateTime($dateString, new DateTimeZone('UTC'));
    return $dt->format(DateTime::RSS);
}

// Fetch the 20 most recent published posts
$posts = getPosts(1, 20);

// Output RSS feed
header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><?= e(SITE_NAME) ?></title>
        <link><?= SITE_URL ?></link>
        <description><?= e(SITE_TAGLINE) ?></description>
        <language>en-gb</language>
        <lastBuildDate><?= rssDate(date('Y-m-d H:i:s')) ?></lastBuildDate>
        <atom:link href="<?= SITE_URL ?>/rss" rel="self" type="application/rss+xml"/>

        <?php foreach ($posts as $post): ?>
            <item>
                <title><?= e($post['title']) ?></title>
                <link><?= SITE_URL ?>/post/<?= e($post['slug']) ?></link>
                <description><?= e($post['excerpt']) ?></description>
                <author><?= e(DEFAULT_AUTHOR) ?></author>
                <?php if (!empty($post['category_name'])): ?>
                    <category><?= e($post['category_name']) ?></category>
                <?php endif; ?>
                <pubDate><?= rssDate($post['created_at']) ?></pubDate>
                <guid isPermaLink="true"><?= SITE_URL ?>/post/<?= e($post['slug']) ?></guid>
            </item>
        <?php endforeach; ?>
    </channel>
</rss>
