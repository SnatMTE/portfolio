<?php
/**
 * cms/functions.php
 *
 * Convenience wrapper: loads all CMS core helpers in one require.
 * Any CMS page that needs auth + helpers can just do:
 *   require_once __DIR__ . '/functions.php';
 *
 * @author  Snat
 * @link    https://terra.me.uk
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/auth.php';
require_once __DIR__ . '/core/helpers.php';
