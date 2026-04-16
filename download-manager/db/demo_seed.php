<?php
/**
 * db/demo_seed.php
 *
 * Seeds the Download Manager database with representative demo records.
 * Triggered by ?demo=1 in the query string or the presence of a DEMO file.
 *
 * Only inserts data when the dm_downloads table is empty, so repeated
 * requests do not create duplicates.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

/**
 * Inserts demo file records into dm_downloads.
 *
 * Creates a set of representative entries covering different file types,
 * categories, and visibility states. Safe to call multiple times.
 *
 * @param PDO $pdo  Active PDO connection.
 *
 * @return void
 */
function seedDemoDownloads(PDO $pdo): void
{
    // Already seeded — skip
    $count = (int) $pdo->query('SELECT COUNT(*) FROM dm_downloads')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $now   = gmdate('Y-m-d H:i:s');
    $files = [
        [
            'title'         => 'Getting Started Guide',
            'description'   => 'A comprehensive PDF guide covering installation, configuration, and first steps.',
            'file_path'     => 'demo_guide.pdf',
            'original_name' => 'getting-started-guide.pdf',
            'file_size'     => 2457600,
            'mime_type'     => 'application/pdf',
            'category'      => 'Documentation',
            'download_count'=> 142,
            'visibility'    => 'public',
        ],
        [
            'title'         => 'Source Code Archive v1.0',
            'description'   => 'Full source code bundle for release 1.0.0, including all modules.',
            'file_path'     => 'demo_src_v1.zip',
            'original_name' => 'source-v1.0.0.zip',
            'file_size'     => 8912742,
            'mime_type'     => 'application/zip',
            'category'      => 'Software',
            'download_count'=> 87,
            'visibility'    => 'public',
        ],
        [
            'title'         => 'Project Wallpaper Pack',
            'description'   => 'High-resolution wallpapers in PNG format. Suitable for desktop and presentation use.',
            'file_path'     => 'demo_wallpapers.zip',
            'original_name' => 'wallpaper-pack.zip',
            'file_size'     => 31457280,
            'mime_type'     => 'application/zip',
            'category'      => 'Media',
            'download_count'=> 34,
            'visibility'    => 'public',
        ],
        [
            'title'         => 'API Reference (CSV Export)',
            'description'   => 'Tabular export of the full API endpoint reference for offline use.',
            'file_path'     => 'demo_api.csv',
            'original_name' => 'api-reference.csv',
            'file_size'     => 48200,
            'mime_type'     => 'text/csv',
            'category'      => 'Documentation',
            'download_count'=> 19,
            'visibility'    => 'public',
        ],
        [
            'title'         => 'Internal Release Notes (v2 Beta)',
            'description'   => 'Private build notes and known issues for the v2 beta. Staff only.',
            'file_path'     => 'demo_beta_notes.txt',
            'original_name' => 'v2-beta-release-notes.txt',
            'file_size'     => 12800,
            'mime_type'     => 'text/plain',
            'category'      => 'Internal',
            'download_count'=> 5,
            'visibility'    => 'private',
        ],
        [
            'title'         => 'Logo Assets Pack',
            'description'   => 'Official logos and brand assets in SVG and PNG formats.',
            'file_path'     => 'demo_logos.zip',
            'original_name' => 'logo-assets.zip',
            'file_size'     => 5242880,
            'mime_type'     => 'application/zip',
            'category'      => 'Media',
            'download_count'=> 61,
            'visibility'    => 'public',
        ],
    ];

    $stmt = $pdo->prepare(
        "INSERT INTO dm_downloads
            (user_id, title, description, file_path, original_name, file_size,
             mime_type, category, download_count, visibility, created_at, updated_at)
         VALUES
            (NULL, :title, :description, :file_path, :original_name, :file_size,
             :mime_type, :category, :download_count, :visibility, :created_at, :updated_at)"
    );

    foreach ($files as $file) {
        $stmt->execute([
            ':title'          => $file['title'],
            ':description'    => $file['description'],
            ':file_path'      => $file['file_path'],
            ':original_name'  => $file['original_name'],
            ':file_size'      => $file['file_size'],
            ':mime_type'      => $file['mime_type'],
            ':category'       => $file['category'],
            ':download_count' => $file['download_count'],
            ':visibility'     => $file['visibility'],
            ':created_at'     => $now,
            ':updated_at'     => $now,
        ]);
    }
}
