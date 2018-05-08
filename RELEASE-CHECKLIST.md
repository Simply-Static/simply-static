# Checklist to follow to ship a new version of the plugin to SVN

1. Run `phpunit` and check that everything passes
2. Ensure there are no `error_log` or `var_dump` or `console.log` in code
3. Update `Version` comment in `simply-static.php`
4. Update `const VERSION` in `includes/class-simply-static.php`
5. In `readme.txt`:
    - Update Stable tag
    - Update the Changelog with the new version and a comment on the changes
6. Validate readme: https://wordpress.org/plugins/about/validator/
7. Run `grunt` to generate i18n files

## In git

1. Commit
2. Tag, .e.g:
```
git tag -a v1.0.1
```
When prompted for a commit message, add the notes from the Changelog.
3. Push
```
git push
git push --tag
```
4. Create zip archive for svn
```
git archive --format=zip -o ../svn-wordpress-org/latest.zip HEAD
```

## In svn

1. Switch to svn directory
```
cd ~/Dropbox/Projects/Simply-Static/svn-wordpress-org/
```
2. Remove the existing trunk directory
(This ensures that any deleted files are caught and removed in the commit.)
```
rm -rf trunk
```
3. Unzip into trunk
```
unzip latest.zip -d trunk && rm latest.zip
```
4. Check for any files that need to be added manually
```
svn st
svn add my_new_file.php
svn rm my_old_file.php
```
Or just add/remove in bulk:
```
svn add --force * --auto-props --parents --depth infinity -q
svn status | grep '^\!' | sed 's/! *//' | xargs -I% svn rm %

```
5. Commit
```
svn commit
```
When prompted for a commit message, add the notes from the Changelog.
6. Tag
```
svn copy trunk tags/1.0.1
svn commit -m 'v1.0.1'
```

## Mailchimp

Send out the mailing! Congrats!
