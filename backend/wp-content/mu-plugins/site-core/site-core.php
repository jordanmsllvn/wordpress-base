<?php
/**
 * Site Core Bootstrap
 *
 * Boots Carbon Fields, loads module files, and runs one-time migrations.
 */




if (! defined('ABSPATH')) {
    exit;
}



if (! defined('SITE_CORE_DIR')) {
    define('SITE_CORE_DIR', __DIR__);
}

/**
 * Theme hard-lock for frontend requests.
 */
function site_core_forced_frontend_theme_slug(): string
{
    return 'headless';
}

add_action(
    'init',
    function (): void {
        if (is_admin()) {
            return;
        }

        $forced_theme = site_core_forced_frontend_theme_slug();
        $theme         = wp_get_theme($forced_theme);

        if (! $theme->exists()) {
            return;
        }

        add_filter(
            'pre_option_template',
            function ($pre_option) use ($forced_theme) {
                if (is_admin()) {
                    return $pre_option;
                }

                return $forced_theme;
            },
            1
        );

        add_filter(
            'pre_option_stylesheet',
            function ($pre_option) use ($forced_theme) {
                if (is_admin()) {
                    return $pre_option;
                }

                return $forced_theme;
            },
            1
        );

        add_filter(
            'pre_option_current_theme',
            function ($pre_option) use ($theme) {
                if (is_admin()) {
                    return $pre_option;
                }

                return (string) $theme->get('Name');
            },
            1
        );
    },
    0
);



/**
 * Boot Carbon Fields after theme setup.
 */
add_action(
    'after_setup_theme',
    function () {
        if (class_exists('\Carbon_Fields\Carbon_Fields')) {
            \Carbon_Fields\Carbon_Fields::boot();
        }
    }
);

/**
 * Load Site Core modules.
 */
function site_core_load_modules()
{
    $modules = array(
        SITE_CORE_DIR . '/page-builder.php',
        SITE_CORE_DIR . '/navigation-links.php',
        SITE_CORE_DIR . '/hydrate-api.php',
        SITE_CORE_DIR . '/restrictions.php',
    );

    foreach ($modules as $module_path) {
        if (is_readable($module_path)) {
            require_once $module_path;
        }
    }
}
site_core_load_modules();

/**
 * Return migration file names already executed.
 *
 * @return array<string>
 */
function site_core_get_executed_migrations()
{
    $executed = get_option('site_core_executed_migrations', array());

    return is_array($executed) ? $executed : array();
}

/**
 * Run pending migration files once.
 */
function site_core_run_migrations()
{
    $migrations_dir = SITE_CORE_DIR . '/migrations';

    if (! is_dir($migrations_dir)) {
        return;
    }

    $migration_files = glob($migrations_dir . '/*.php');

    if (empty($migration_files) || ! is_array($migration_files)) {
        return;
    }

    natsort($migration_files);

    $executed = site_core_get_executed_migrations();

    foreach ($migration_files as $migration_path) {
        $migration_name = basename($migration_path);

        if (in_array($migration_name, $executed, true)) {
            continue;
        }

        try {
            $migration = include $migration_path;

            if (is_callable($migration)) {
                call_user_func($migration);
            }

            $executed[] = $migration_name;
        } catch (Throwable $throwable) {
            error_log(sprintf('Site Core migration failed (%s): %s', $migration_name, $throwable->getMessage()));
        }
    }

    update_option('site_core_executed_migrations', array_values(array_unique($executed)), false);
}
add_action('init', 'site_core_run_migrations', 5);
