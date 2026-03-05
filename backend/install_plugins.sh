#!/usr/bin/env bash
set -e

WP_CLI=${WP_CLI:-wp}

PLUGINS=(
  "carbon-fields-plugin:https://github.com/htmlburger/carbon-fields-plugin/releases/download/v3.6.9/carbon-fields-plugin.zip"
  "contact-form-7:5.9.8"
)

install_plugin() {
  slug=$1
  source=$2

  if $WP_CLI plugin is-installed "$slug" >/dev/null 2>&1; then
    echo "✓ $slug already installed"
    if ! $WP_CLI plugin is-active "$slug"; then
      echo "→ Activating $slug"
      $WP_CLI plugin activate "$slug"
    fi
  else
    echo "Installing $slug"

    if [[ "$source" == http* ]]; then
      $WP_CLI plugin install "$source" --activate
    else
      $WP_CLI plugin install "$slug" --version="$source" --activate
    fi
  fi

}

for entry in "${PLUGINS[@]}"; do
  IFS=":" read -r slug source <<<"$entry"
  install_plugin "$slug" "$source"
done
