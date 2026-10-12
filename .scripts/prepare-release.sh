#!/usr/bin/env bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/replace-version.sh"

VERSION="${VERSION:-$(grep -oP '(?<=Stable tag: ).*' readme.txt | tr -d '[:space:]')}"
export VERSION

# Resolve WP-CLI: prefer `wp` on PATH, otherwise download the official phar.
# setup-php's tools: wp-cli can fail silently ("Could not setup wp-cli") while
# still exiting 0, so deploy CI must not rely on that alone.
ensure_wp_cli() {
	if command -v wp >/dev/null 2>&1; then
		WP=(wp)
		return 0
	fi

	local wp_phar="${TMPDIR:-/tmp}/wp-cli.phar"
	echo "wp not found on PATH; downloading WP-CLI phar to ${wp_phar}"
	curl -fsSL -o "$wp_phar" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x "$wp_phar"
	WP=(php "$wp_phar")
}

ensure_wp_cli

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
"${WP[@]}" i18n make-pot . languages/wc-price-history.pot --allow-root 2>/dev/null || "${WP[@]}" i18n make-pot . languages/wc-price-history.pot
replace_version_number
replace_version_always_top
