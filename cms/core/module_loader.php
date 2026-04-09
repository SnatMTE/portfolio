<?php
/**
 * cms/core/module_loader.php
 *
 * Scans the CMS directory for installed modules.
 * A module is detected by the presence of its subdirectory inside CMS_ROOT.
 * Each module may optionally include a module.php that returns metadata.
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

/**
 * Returns an array of active module metadata arrays.
 *
 * Each entry contains at minimum:
 *   'key'        – directory name (e.g. 'blog')
 *   'name'       – display name (e.g. 'Blog')
 *   'path'       – absolute filesystem path
 *   'url'        – public URL to the module index
 *   'admin_link' – URL to the module admin panel
 *
 * @return array<int, array<string, mixed>>
 */
function getActiveModules(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $knownModules = ['blog', 'forum', 'store'];
    $active       = [];

    foreach ($knownModules as $mod) {
        $dir = CMS_ROOT . '/' . $mod;
        if (!is_dir($dir)) {
            continue;
        }

        $meta = [
            'key'        => $mod,
            'name'       => ucfirst($mod),
            'path'       => $dir,
            'url'        => SITE_URL . '/' . $mod . '/',
            'admin_link' => SITE_URL . '/' . $mod . '/admin/',
            'icon'       => defaultModuleIcon($mod),
            'description'=> '',
        ];

        $moduleFile = $dir . '/module.php';
        if (file_exists($moduleFile)) {
            $loaded = require $moduleFile;
            if (is_array($loaded)) {
                $meta = array_merge($meta, $loaded);
                // Always enforce correct path/key
                $meta['key']  = $mod;
                $meta['path'] = $dir;
            }
        }

        $active[] = $meta;
    }

    $cached = $active;
    return $active;
}

/**
 * Returns a default icon character for known module types.
 *
 * @param string $moduleKey
 * @return string
 */
function defaultModuleIcon(string $moduleKey): string
{
    return match ($moduleKey) {
        'blog'  => '&#128221;',
        'forum' => '&#128172;',
        'store' => '&#128722;',
        default => '&#128196;',
    };
}
