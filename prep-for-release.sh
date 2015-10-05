#!/bin/bash

# This script copies files for the plugin into a new directory intended for
# release at https://plugins.svn.wordpress.org/.

# Files are copied from the trunk and assets directories. In the newly created
# directory, all files in trunk/static-files are removed, and files use
# for testing the plugin are deleted.

# current directory (auto-detected)
CURRENT_DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

# destination svn directory
DEST_DIR=$CURRENT_DIR/../svn-wordpress-org

# copy assets and trunk directories
mkdir -p $DEST_DIR/assets
mkdir -p $DEST_DIR/trunk
cp -R $CURRENT_DIR/assets $DEST_DIR
cp -R $CURRENT_DIR/trunk $DEST_DIR

# clear out the static-files directory
find $DEST_DIR/trunk/static-files/ -maxdepth 1 -type f -exec rm {} \;

# remove test files/directories
rm $DEST_DIR/trunk/phpunit.xml
rm $DEST_DIR/trunk/.travis.yml
rm $DEST_DIR/trunk/.php-version
rm -rf $DEST_DIR/trunk/tests
rm -rf $DEST_DIR/trunk/bin
