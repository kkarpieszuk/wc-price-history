#!/usr/bin/env bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/replace-version.sh"

VERSION="${VERSION:-$(grep -oP '(?<=Stable tag: ).*' readme.txt | tr -d '[:space:]')}"
export VERSION

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
wp i18n make-pot . languages/wc-price-history.pot --allow-root 2>/dev/null || wp i18n make-pot . languages/wc-price-history.pot
replace_version_number
replace_version_always_top
