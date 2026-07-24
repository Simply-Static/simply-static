#!/usr/bin/env bash

set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$repo_root"

if [[ $# -ne 1 ]]; then
	echo "Usage: $0 <release-tag>" >&2
	exit 1
fi

release_version="${1#v}"

if [[ ! "$release_version" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
	echo "Release tag must be a semantic version such as 3.8.5 (an optional v prefix is allowed)." >&2
	exit 1
fi

plugin_header_version="$(awk '/^[[:space:]]*\*[[:space:]]*Version:/ { print $3; exit }' simply-static.php)"
plugin_constant_version="$(awk -F"'" '/SIMPLY_STATIC_VERSION/ { print $4; exit }' simply-static.php)"
stable_tag="$(awk '/^Stable tag:/ { print $3; exit }' readme.txt)"
admin_package_version="$(node -p "require('./src/admin/package.json').version")"

assert_version() {
	local label="$1"
	local actual="$2"

	if [[ "$actual" != "$release_version" ]]; then
		echo "$label is '$actual'; expected '$release_version' from the release tag." >&2
		exit 1
	fi
}

assert_version "Plugin header version" "$plugin_header_version"
assert_version "SIMPLY_STATIC_VERSION" "$plugin_constant_version"
assert_version "readme.txt stable tag" "$stable_tag"
assert_version "Admin package version" "$admin_package_version"

echo "Release version $release_version is consistent."
