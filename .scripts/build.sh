#!/usr/bin/env bash

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/replace-version.sh"

# take the branch name from the command line argument. If not specified, default to develop.
BRANCH=${1:-develop}

# Prompt to confirm the branch name before continuing.
read -r -p "$(echo -e '\e[1;31mBuild will be made from branch: '"$BRANCH"'. Continue? \e[0m [y/N]')" response
case "$response" in
    [yY][eE][sS]|[yY])
        echo "Continuing..."
        ;;
    *)
        exit 1
        ;;
esac

git stash
git checkout $BRANCH
git pull

# Get the plugin version from the readme.txt file.
VERSION=$(grep -oP '(?<=Stable tag: ).*' readme.txt)

# Prompt to confirm the version number before continuing.
read -r -p "$(echo -e '\e[1;31mPlugin version: '"$VERSION"'. Continue? \e[0m [y/N]')" response
case "$response" in
    [yY][eE][sS]|[yY])
        echo "Continuing..."
        ;;
    *)
        exit 1
        ;;
esac

# make build directory and copy all files to it.
rm -rf build
mkdir -p build/gitversion
rsync -av --exclude='build' --exclude-from='.distignore' . build/gitversion/

# Go to the build directory.
cd build/gitversion

# Run shared release preparation (composer, pot, version replace).
rm -rf vendor
bash "$SCRIPT_DIR/prepare-release.sh"

# create a zip file.
zip -r ../wc-price-history.zip .

cd ..

# Checkout svn repository
svn checkout https://plugins.svn.wordpress.org/wc-price-history/ svn-checkout

# Copy files to svn repository
cp -r gitversion/* svn-checkout/trunk/

cd svn-checkout

svn status

# for each file in svn status marked with ? add it to svn
for file in $(svn status | grep '?' | awk '{print $2}'); do
  echo "Adding $file to svn"
  svn add $file
done

svn status

# wait for user input
read -r -p "$(echo -e '\e[1;31mCommit changes to WordPress.org? \e[0m [y/N]')" response
case "$response" in
    [yY][eE][sS]|[yY])
        echo "Continuing..."
        ;;
    *)
        exit 1
        ;;
esac

svn ci -m "Pushing $VERSION to the trunk"

svn cp trunk tags/$VERSION

svn ci -m "Tagging and releasing $VERSION"

