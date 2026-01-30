#!/usr/bin/env bash

set -e

# Can be sourced (to use replace_version_number) or run standalone.
# Standalone usage: ./replace-version.sh [VERSION]
#   Run from the directory where {VERSION} should be replaced (e.g. plugin root or build/gitversion).
#   If VERSION is omitted, it is read from readme.txt in the current directory.

function replace_version_number() {

  # Function to traverse directories and replace {VERSION} with the real version number
  traverse_and_replace() {
    for file in "$1"/*; do
      if [ -d "$file" ]; then
        # Skip /vendor and /node_modules directories
        if [[ "$file" == */vendor ]] || [[ "$file" == */node_modules ]]; then
          continue
        fi
        # Recursively traverse directories
        traverse_and_replace "$file"
      elif [ -f "$file" ]; then
        # Replace {VERSION} with the real version number in files
        sed -i "s/{VERSION}/$VERSION/g" "$file"
      fi
    done
  }

  # Start traversal from the current working directory
  traverse_and_replace "."

  echo "Replaced {VERSION} with $VERSION in all files."
}

# Replaces {VERSION_ALWAYS_TOP} with current version. For use only in release process (e.g. build.sh).
# In repo files keep {VERSION_ALWAYS_TOP}; it is replaced only during build, not committed.
function replace_version_always_top() {

  traverse_and_replace_always_top() {
    for file in "$1"/*; do
      if [ -d "$file" ]; then
        if [[ "$file" == */vendor ]] || [[ "$file" == */node_modules ]]; then
          continue
        fi
        traverse_and_replace_always_top "$file"
      elif [ -f "$file" ]; then
        sed -i "s/{VERSION_ALWAYS_TOP}/$VERSION/g" "$file"
      fi
    done
  }

  traverse_and_replace_always_top "."

  echo "Replaced {VERSION_ALWAYS_TOP} with $VERSION in all files."
}

# When run directly (not sourced), get VERSION and run replace_version_number
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
  VERSION="${1:-}"
  if [ -z "$VERSION" ]; then
    if [ ! -f "readme.txt" ]; then
      echo "Error: readme.txt not found in current directory. Run from plugin root or pass VERSION as first argument." >&2
      exit 1
    fi
    VERSION=$(grep -oP '(?<=Stable tag: ).*' readme.txt)
  fi
  replace_version_number
fi
