<?php
/**
 * cms/admin/auth.php
 *
 * Bootstraps every CMS admin page.
 * Requires authentication and loads all helpers.
 *
 * @author  M. Terra Ellis
 * @link    https://terra.me.uk
 */

require_once dirname(__DIR__) . '/functions.php';

requireCMSAuth();
