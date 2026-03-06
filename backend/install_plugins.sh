#!/usr/bin/env bash
set -e

WP_CLI=${WP_CLI:-wp}
WP_ROOT=${WP_ROOT:-$(pwd)}
WP_PLUGINS_DIR=${WP_PLUGINS_DIR:-"$WP_ROOT/wp-content/plugins"}

PLUGINS=(
  "zip:carbon-fields-plugin:https://carbonfields.net/zip/v3.6.6/"
  "contact-form-7:5.9.8"
)

show_tool_install_hint() {
  local tool=$1

  if command -v apt-get >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • Ubuntu/Debian: sudo apt-get update && sudo apt-get install -y unzip"
        ;;
      curl|wget)
        echo "  • Ubuntu/Debian: sudo apt-get update && sudo apt-get install -y $tool"
        ;;
      *)
        echo "  • Ubuntu/Debian: use your package manager to install $tool"
        ;;
    esac
  elif command -v dnf >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • Fedora/RHEL (dnf): sudo dnf install -y unzip"
        ;;
      curl|wget)
        echo "  • Fedora/RHEL (dnf): sudo dnf install -y $tool"
        ;;
      *)
        echo "  • Fedora/RHEL (dnf): use your package manager to install $tool"
        ;;
    esac
  elif command -v yum >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • RHEL/CentOS (yum): sudo yum install -y unzip"
        ;;
      curl|wget)
        echo "  • RHEL/CentOS (yum): sudo yum install -y $tool"
        ;;
      *)
        echo "  • RHEL/CentOS (yum): use your package manager to install $tool"
        ;;
    esac
  elif command -v zypper >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • openSUSE: sudo zypper install -y unzip"
        ;;
      curl|wget)
        echo "  • openSUSE: sudo zypper install -y $tool"
        ;;
      *)
        echo "  • openSUSE: use your package manager to install $tool"
        ;;
    esac
  elif command -v pacman >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • Arch/Manjaro: sudo pacman -S --noconfirm unzip"
        ;;
      curl|wget)
        echo "  • Arch/Manjaro: sudo pacman -S --noconfirm $tool"
        ;;
      *)
        echo "  • Arch/Manjaro: use your package manager to install $tool"
        ;;
    esac
  elif command -v apk >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • Alpine: sudo apk add unzip"
        ;;
      curl|wget)
        echo "  • Alpine: sudo apk add $tool"
        ;;
      *)
        echo "  • Alpine: use your package manager to install $tool"
        ;;
    esac
  elif command -v brew >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • macOS: brew install unzip"
        ;;
      curl|wget)
        echo "  • macOS: brew install $tool"
        ;;
      *)
        echo "  • macOS: brew install $tool"
        ;;
    esac
  elif command -v xbps-install >/dev/null 2>&1; then
    case "$tool" in
      unzip)
        echo "  • Void Linux: sudo xbps-install -S unzip"
        ;;
      curl|wget)
        echo "  • Void Linux: sudo xbps-install -S $tool"
        ;;
      *)
        echo "  • Void Linux: use your package manager to install $tool"
        ;;
    esac
  else
    echo "  • Install $tool using your OS package manager."
  fi
}

show_toolset_install_hint() {
  local package_list="$1"

  if command -v apt-get >/dev/null 2>&1; then
    echo "  • Ubuntu/Debian: sudo apt-get update && sudo apt-get install -y $package_list"
  elif command -v dnf >/dev/null 2>&1; then
    echo "  • Fedora/RHEL (dnf): sudo dnf install -y $package_list"
  elif command -v yum >/dev/null 2>&1; then
    echo "  • RHEL/CentOS (yum): sudo yum install -y $package_list"
  elif command -v zypper >/dev/null 2>&1; then
    echo "  • openSUSE: sudo zypper install -y $package_list"
  elif command -v pacman >/dev/null 2>&1; then
    echo "  • Arch/Manjaro: sudo pacman -S --noconfirm $package_list"
  elif command -v apk >/dev/null 2>&1; then
    echo "  • Alpine: sudo apk add $package_list"
  elif command -v brew >/dev/null 2>&1; then
    echo "  • macOS: brew install $package_list"
  elif command -v xbps-install >/dev/null 2>&1; then
    echo "  • Void Linux: sudo xbps-install -S $package_list"
  else
    echo "  • Use your OS package manager to install: $package_list"
  fi
}

ensure_tool() {
  local tool=$1

  if ! command -v "$tool" >/dev/null 2>&1; then
    echo "✗ $tool is required to install zip plugins."
    echo "  Install it first:"
    show_tool_install_hint "$tool"
    return 1
  fi
  return 0
}

ensure_download_tool() {
  if command -v curl >/dev/null 2>&1 || command -v wget >/dev/null 2>&1; then
    return 0
  fi

  echo "✗ curl or wget is required to download zip plugins."
  echo "  Install at least one first:"
  show_tool_install_hint "curl"
  show_tool_install_hint "wget"
  return 1
}

check_zip_dependencies() {
  local has_zip_plugin=0
  local missing=0

  for entry in "${PLUGINS[@]}"; do
    if [[ "$entry" == zip:* ]]; then
      has_zip_plugin=1
      break
    fi
  done

  if [[ "$has_zip_plugin" -eq 0 ]]; then
    return 0
  fi

  echo "Checking dependencies for zip plugin installs:"

  if ! command -v unzip >/dev/null 2>&1; then
    echo "✗ Missing: unzip"
    echo "  Install it first:"
    show_tool_install_hint "unzip"
    missing=1
  fi

  if ! command -v curl >/dev/null 2>&1 && ! command -v wget >/dev/null 2>&1; then
    echo "✗ Missing: curl or wget (need at least one)"
    echo "  Install one of these first:"
    show_tool_install_hint "curl"
    show_tool_install_hint "wget"
    missing=1
  fi

  if [[ "$missing" -eq 1 ]]; then
    echo "  One-liner:"
    if command -v apt-get >/dev/null 2>&1 || command -v dnf >/dev/null 2>&1 || command -v yum >/dev/null 2>&1 || command -v zypper >/dev/null 2>&1 || command -v pacman >/dev/null 2>&1 || command -v apk >/dev/null 2>&1 || command -v brew >/dev/null 2>&1 || command -v xbps-install >/dev/null 2>&1; then
      show_toolset_install_hint "unzip curl wget"
    else
      echo "  • Use your OS package manager to install: unzip curl wget"
    fi
    return 1
  fi

  return 0
}

install_zip_plugin() {
  slug=$1
  zip_url=$2
  ensure_tool unzip >/dev/null 2>&1 || return 1
  ensure_download_tool || return 1

  tmp_dir=$(mktemp -d)
  zip_path="$tmp_dir/$slug.zip"
  extracted_path="$tmp_dir/extracted"
  target_path="$WP_PLUGINS_DIR/$slug"

  mkdir -p "$extracted_path"

  echo "→ Downloading $slug zip from $zip_url"
  if command -v curl >/dev/null 2>&1; then
    curl -fsSL "$zip_url" -o "$zip_path"
  elif command -v wget >/dev/null 2>&1; then
    wget -qO "$zip_path" "$zip_url"
  else
    echo "✗ curl or wget is required to download zip plugins"
    rm -rf "$tmp_dir"
    return 1
  fi

  echo "→ Extracting $slug zip"
  unzip -q "$zip_path" -d "$extracted_path"

  source_path="$extracted_path/$slug"
  if [[ ! -d "$source_path" ]]; then
    source_path=$(find "$extracted_path" -mindepth 1 -maxdepth 1 -type d | head -n 1)
  fi

  if [[ -z "$source_path" || ! -d "$source_path" ]]; then
    echo "✗ Could not find a plugin directory in zip for $slug"
    rm -rf "$tmp_dir"
    return 1
  fi

  rm -rf "$target_path"
  mkdir -p "$target_path"
  cp -a "$source_path"/. "$target_path"/

  rm -rf "$tmp_dir"
}

install_plugin() {
  slug=$1
  source=$2
  source_type=${3:-default}

  if $WP_CLI plugin is-installed "$slug" >/dev/null 2>&1; then
    echo "✓ $slug already installed"
    if ! $WP_CLI plugin is-active "$slug"; then
      echo "→ Activating $slug"
      $WP_CLI plugin activate "$slug"
    fi
  else
    echo "Installing $slug"

    if [[ "$source_type" == "zip" ]]; then
      install_zip_plugin "$slug" "$source"
      $WP_CLI plugin activate "$slug"
    elif [[ "$source" == http* ]]; then
      $WP_CLI plugin install "$source" --activate
    else
      $WP_CLI plugin install "$slug" --version="$source" --activate
    fi
  fi

}

if ! check_zip_dependencies; then
  exit 1
fi

for entry in "${PLUGINS[@]}"; do
  if [[ "$entry" == zip:* ]]; then
    parsed_entry=${entry#zip:}
    IFS=":" read -r slug source <<<"$parsed_entry"
    install_plugin "$slug" "$source" "zip"
  else
    IFS=":" read -r slug source <<<"$entry"
    install_plugin "$slug" "$source"
  fi
done
