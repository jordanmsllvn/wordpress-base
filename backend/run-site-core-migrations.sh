#!/usr/bin/env bash
set -euo pipefail

WP_CLI_COMMAND=${WP_CLI:-"wp"}
COMMAND=${1:-latest}

read -r -a WP_CLI_ARGS <<< "$WP_CLI_COMMAND"

case "$COMMAND" in
  up)
    "${WP_CLI_ARGS[@]}" eval '
if (!function_exists("site_core_migrate_up")) {
  WP_CLI::error("site_core_migrate_up() is not available. Ensure the Site Core mu-plugin is loaded.");
}

try {
  $migration = site_core_migrate_up();

  if (null === $migration) {
    WP_CLI::success("No pending Site Core migrations.");
  } else {
    WP_CLI::success(sprintf("Applied Site Core migration: %s", $migration));
  }
} catch (Throwable $throwable) {
  WP_CLI::error($throwable->getMessage());
}
'
    ;;
  down)
    "${WP_CLI_ARGS[@]}" eval '
if (!function_exists("site_core_migrate_down")) {
  WP_CLI::error("site_core_migrate_down() is not available. Ensure the Site Core mu-plugin is loaded.");
}

try {
  $migration = site_core_migrate_down();

  if (null === $migration) {
    WP_CLI::success("No applied Site Core migrations to roll back.");
  } else {
    WP_CLI::success(sprintf("Rolled back Site Core migration: %s", $migration));
  }
} catch (Throwable $throwable) {
  WP_CLI::error($throwable->getMessage());
}
'
    ;;
  latest)
    "${WP_CLI_ARGS[@]}" eval '
if (!function_exists("site_core_migrate_latest")) {
  WP_CLI::error("site_core_migrate_latest() is not available. Ensure the Site Core mu-plugin is loaded.");
}

try {
  $migrations = site_core_migrate_latest();

  if (empty($migrations)) {
    WP_CLI::success("No pending Site Core migrations.");
  } else {
    WP_CLI::success(sprintf("Applied Site Core migrations: %s", implode(", ", $migrations)));
  }
} catch (Throwable $throwable) {
  WP_CLI::error($throwable->getMessage());
}
'
    ;;
  full-rollback)
    "${WP_CLI_ARGS[@]}" eval '
if (!function_exists("site_core_migrate_full_rollback")) {
  WP_CLI::error("site_core_migrate_full_rollback() is not available. Ensure the Site Core mu-plugin is loaded.");
}

try {
  $migrations = site_core_migrate_full_rollback();

  if (empty($migrations)) {
    WP_CLI::success("No applied Site Core migrations to roll back.");
  } else {
    WP_CLI::success(sprintf("Rolled back Site Core migrations: %s", implode(", ", $migrations)));
  }
} catch (Throwable $throwable) {
  WP_CLI::error($throwable->getMessage());
}
'
    ;;
  *)
    echo "Unknown migration command: $COMMAND" >&2
    echo "Expected one of: up, down, latest, full-rollback" >&2
    exit 1
    ;;
esac
