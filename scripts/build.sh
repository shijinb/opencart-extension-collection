#!/usr/bin/env bash
# Packages each extension as dist/<name>.ocmod.zip for Extensions > Installer.
# A package contains install.xml (when the extension has one) and the upload/ folder.
# SQL files are not included: OpenCart 3 installer does not run them (see each extension's README).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
rm -rf "$ROOT/dist" && mkdir -p "$ROOT/dist"

for dir in "$ROOT"/extensions/*/; do
  name="$(basename "$dir")"
  files=()
  [ -f "$dir/install.xml" ] && files+=(install.xml)
  [ -d "$dir/upload" ] && files+=(upload)
  (cd "$dir" && zip -qrX "$ROOT/dist/$name.ocmod.zip" "${files[@]}" -x '*.DS_Store')
  echo "dist/$name.ocmod.zip"
done
