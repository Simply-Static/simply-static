# Releasing Simply Static

Publishing a normal GitHub release runs `.github/workflows/release.yml`. The
workflow verifies the version, runs the PHP and React test suites, rebuilds both
React applications, creates a production-only plugin ZIP, publishes the same
files to WordPress.org, and attaches the ZIP and SHA-256 checksum to the GitHub
release.

Prereleases do not deploy to WordPress.org.

## One-time GitHub setup

1. Create a GitHub Actions environment named `wordpress.org`.
2. Add these environment secrets:
   - `SVN_USERNAME`: the case-sensitive WordPress.org username of a committer
     for the `simply-static` plugin.
   - `SVN_PASSWORD`: that account's
     [SVN-specific password](https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/#your-account).
3. Optionally protect the `wordpress.org` environment with required reviewers.
   Approval happens before credentials are made available and before anything
   is deployed.

Use an SVN-specific password rather than the account's main password. The
workflow grants write access to the GitHub token only in the deployment job;
the build and dependency-installation job is read-only.

If WordPress.org's optional
[Release Confirmation](https://developer.wordpress.org/plugins/wordpress-org/release-confirmation-emails/)
feature is enabled for the plugin, WordPress.org will still require its separate
email/dashboard confirmation after the SVN deployment.

## Publish a release

1. Update the version in `simply-static.php` (the plugin header and
   `SIMPLY_STATIC_VERSION`), `readme.txt` (`Stable tag`), and
   `src/admin/package.json`.
2. Commit the generated React build directories.
3. Create and publish a non-prerelease GitHub release whose tag is the same
   semantic version, for example `3.8.5` (a `v3.8.5` tag is also accepted).
4. Watch the **Release** workflow. A failure before the deploy step does not
   change WordPress.org.

The version consistency check can also be run locally:

```bash
./scripts/validate-release-version.sh 3.8.5
```

To reproduce and inspect the production package locally:

```bash
npm ci --prefix src/admin
npm run build --prefix src/admin
npm ci --prefix assets/install-plugins
npm run build --prefix assets/install-plugins
./scripts/build-release.sh
unzip -l build/release/simply-static.zip
```

The `.distignore` file is the source of truth for production exclusions. The
ZIP contains the PHP runtime, translations, views, static assets, and the two
React `build` directories. It excludes Git/CI metadata, tests, React source and
configuration, package manifests, Composer development files, `vendor`,
`node_modules`, coverage output, and source maps.
