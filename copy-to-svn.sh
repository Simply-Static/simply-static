# copy assets
rm -rf svn-wordpress-org/assets/
mkdir svn-wordpress-org/assets/
cp -R assets/* svn-wordpress-org/assets

# copy codebase
rm -rf svn-wordpress-org/trunk/
mkdir svn-wordpress-org/trunk/
cp -R simply-static/* svn-wordpress-org/trunk

# remove debug files
rm -f svn-wordpress-org/trunk/debug.txt
rm -f svn-wordpress-org/trunk/debug.zip
rm -f svn-wordpress-org/trunk/debug.html

# remove static files
rm -rf svn-wordpress-org/trunk/static-files/
mkdir svn-wordpress-org/trunk/static-files/