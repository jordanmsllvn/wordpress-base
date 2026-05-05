<?php
/**
 * Site Core Bootstrap
 *
 * Boots Carbon Fields, loads module files, and defines Site Core migrations.
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
    $modules = array_merge(
        array(
            SITE_CORE_DIR . '/support/page-builder.php',
            SITE_CORE_DIR . '/support/page-hydration.php',
        ),
        site_core_get_module_paths('components/reusable/*.php'),
        site_core_get_module_paths('components/*.php'),
        site_core_get_module_paths('content-types/*.php'),
        site_core_get_module_paths('fields/*.php'),
        site_core_get_module_paths('api/*.php'),
        site_core_get_module_paths('admin/*.php')
    );

    foreach ($modules as $module_path) {
        if (is_readable($module_path)) {
            require_once $module_path;
        }
    }
}
site_core_load_modules();

/**
 * Return sorted module paths for a directory pattern.
 *
 * @return array<int, string>
 */
function site_core_get_module_paths(string $pattern)
{
    $module_paths = glob(SITE_CORE_DIR . '/' . $pattern);

    if (! is_array($module_paths)) {
        return array();
    }

    sort($module_paths);

    return $module_paths;
}

/**
 * Return migration file names already executed in apply order.
 *
 * @return array<string>
 */
function site_core_get_executed_migrations()
{
    $executed = get_option('site_core_executed_migrations', array());

    if (! is_array($executed)) {
        return array();
    }

    return array_values(array_map('strval', $executed));
}

/**
 * Persist the ordered list of applied migrations.
 *
 * @param array<string> $executed
 */
function site_core_set_executed_migrations(array $executed)
{
    update_option('site_core_executed_migrations', array_values(array_unique($executed)), false);
}

/**
 * Return available migration files keyed by migration name.
 *
 * @return array<string, string>
 */
function site_core_get_migration_paths()
{
    $migrations_dir = SITE_CORE_DIR . '/migrations';

    if (! is_dir($migrations_dir)) {
        return array();
    }

    $migration_files = glob($migrations_dir . '/*.php');

    if (empty($migration_files) || ! is_array($migration_files)) {
        return array();
    }

    natsort($migration_files);

    $migration_paths = array();

    foreach ($migration_files as $migration_path) {
        $migration_paths[basename($migration_path)] = $migration_path;
    }

    return $migration_paths;
}

/**
 * Normalize a migration file into an up/down callable pair.
 *
 * @param mixed  $migration
 * @return array{up: callable, down: callable|null}
 */
function site_core_normalize_migration_definition($migration, string $migration_name)
{
    if (is_callable($migration)) {
        return array(
            'up' => $migration,
            'down' => null,
        );
    }

    if (! is_array($migration)) {
        throw new RuntimeException(
            sprintf('Site Core migration "%s" must return a callable or an array with up/down callables.', $migration_name)
        );
    }

    $up = $migration['up'] ?? null;
    $down = $migration['down'] ?? null;

    if (! is_callable($up)) {
        throw new RuntimeException(
            sprintf('Site Core migration "%s" must define a callable "up" migration.', $migration_name)
        );
    }

    if (null !== $down && ! is_callable($down)) {
        throw new RuntimeException(
            sprintf('Site Core migration "%s" must define a callable "down" migration when present.', $migration_name)
        );
    }

    return array(
        'up' => $up,
        'down' => $down,
    );
}

/**
 * Load a single migration definition from disk.
 *
 * @return array{up: callable, down: callable|null}
 */
function site_core_get_migration_definition(string $migration_name)
{
    $migration_paths = site_core_get_migration_paths();

    if (! isset($migration_paths[$migration_name])) {
        throw new RuntimeException(sprintf('Site Core migration "%s" was not found.', $migration_name));
    }

    $migration = include $migration_paths[$migration_name];

    return site_core_normalize_migration_definition($migration, $migration_name);
}

/**
 * Return pending migration names in apply order.
 *
 * @return array<string>
 */
function site_core_get_pending_migrations()
{
    $executed = site_core_get_executed_migrations();
    $pending = array();

    foreach (array_keys(site_core_get_migration_paths()) as $migration_name) {
        if (! in_array($migration_name, $executed, true)) {
            $pending[] = $migration_name;
        }
    }

    return $pending;
}

/**
 * Apply a specific migration.
 */
function site_core_apply_migration(string $migration_name)
{
    $executed = site_core_get_executed_migrations();

    if (in_array($migration_name, $executed, true)) {
        return;
    }

    $migration = site_core_get_migration_definition($migration_name);
    call_user_func($migration['up']);

    $executed[] = $migration_name;
    site_core_set_executed_migrations($executed);
}

/**
 * Roll back a specific migration.
 */
function site_core_rollback_migration(string $migration_name)
{
    $executed = site_core_get_executed_migrations();

    if (! in_array($migration_name, $executed, true)) {
        return;
    }

    $migration = site_core_get_migration_definition($migration_name);

    if (! is_callable($migration['down'])) {
        throw new RuntimeException(
            sprintf('Site Core migration "%s" does not define a down migration.', $migration_name)
        );
    }

    call_user_func($migration['down']);

    $executed = array_values(
        array_filter(
            $executed,
            static function ($executed_migration) use ($migration_name) {
                return $executed_migration !== $migration_name;
            }
        )
    );

    site_core_set_executed_migrations($executed);
}

/**
 * Ensure every migration in a rollback set supports down().
 *
 * @param array<string> $migration_names
 */
function site_core_assert_migrations_are_reversible(array $migration_names)
{
    foreach ($migration_names as $migration_name) {
        $migration = site_core_get_migration_definition($migration_name);

        if (! is_callable($migration['down'])) {
            throw new RuntimeException(
                sprintf('Site Core migration "%s" cannot be rolled back because it has no down migration.', $migration_name)
            );
        }
    }
}

/**
 * Apply the next pending migration.
 *
 * @return string|null
 */
function site_core_migrate_up()
{
    $pending = site_core_get_pending_migrations();

    if (empty($pending)) {
        return null;
    }

    $migration_name = $pending[0];
    site_core_apply_migration($migration_name);

    return $migration_name;
}

/**
 * Apply all pending migrations.
 *
 * @return array<string>
 */
function site_core_migrate_latest()
{
    $applied = array();

    foreach (site_core_get_pending_migrations() as $migration_name) {
        site_core_apply_migration($migration_name);
        $applied[] = $migration_name;
    }

    return $applied;
}

/**
 * Roll back the latest applied migration.
 *
 * @return string|null
 */
function site_core_migrate_down()
{
    $executed = site_core_get_executed_migrations();

    if (empty($executed)) {
        return null;
    }

    $migration_name = end($executed);

    if (! is_string($migration_name) || '' === $migration_name) {
        return null;
    }

    site_core_rollback_migration($migration_name);

    return $migration_name;
}

/**
 * Roll back every applied migration in reverse order.
 *
 * @return array<string>
 */
function site_core_migrate_full_rollback()
{
    $executed = array_reverse(site_core_get_executed_migrations());

    if (empty($executed)) {
        return array();
    }

    site_core_assert_migrations_are_reversible($executed);

    $rolled_back = array();

    foreach ($executed as $migration_name) {
        site_core_rollback_migration($migration_name);
        $rolled_back[] = $migration_name;
    }

    return $rolled_back;
}

/**
 * Backward-compatible alias for applying all pending migrations.
 *
 * @return array<string>
 */
function site_core_run_migrations()
{
    return site_core_migrate_latest();
}
