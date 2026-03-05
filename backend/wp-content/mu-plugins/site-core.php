<?php
/**
 * Plugin Name: Site Core Loader (MU)
 * Description: Loads the Site Core must-use plugin from the site-core directory.
 * Author: Site Core
 */

if (! defined('ABSPATH')) {
    exit;
}

$site_core_bootstrap = __DIR__ . '/site-core/site-core.php';

if (is_readable($site_core_bootstrap)) {
    require_once $site_core_bootstrap;
}
