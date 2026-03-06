#!/usr/bin/env bash
set -e

WP_CLI=${WP_CLI:-wp}
ADMIN_USERNAME=${WP_ADMIN_USERNAME:-admin}
ADMIN_PASSWORD=${WP_ADMIN_PASSWORD:-Admin123!}

if $WP_CLI user get "$ADMIN_USERNAME" --field=ID >/dev/null 2>&1; then
  $WP_CLI user update "$ADMIN_USERNAME" --user_pass="$ADMIN_PASSWORD" --skip-email >/dev/null
  echo "Set password for '$ADMIN_USERNAME' to '$ADMIN_PASSWORD'."
  exit 0
fi

ADMIN_USER_ID=$($WP_CLI user list --role=administrator --field=ID --format=ids --number=1 | awk 'NR==1 { print $1 }')

if [ -z "$ADMIN_USER_ID" ]; then
  echo "WordPress is not installed yet or no admin users found. Skipping password set."
  exit 0
fi

$WP_CLI user update "$ADMIN_USER_ID" --user_pass="$ADMIN_PASSWORD" --skip-email >/dev/null
echo "Set password for admin user ID '$ADMIN_USER_ID' to '$ADMIN_PASSWORD'."
