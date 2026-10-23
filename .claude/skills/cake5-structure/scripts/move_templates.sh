#!/usr/bin/env bash
# Move CakePHP 3 templates to the CakePHP 4/5 layout, keeping git history.
#
#   src/Template/**        -> templates/**
#   Layout/ Element/ Email/ Cell/ Plugin/  -> layout/ element/ email/ cell/ plugin/
#   Error/ stays capitalised: it is ErrorController's template path (templates/Error/error400.php)
#   *.ctp                  -> *.php
#
# Usage: bash move_templates.sh <project-path> [--dry-run]
# Safe on case-insensitive filesystems (Windows/macOS): case-only renames go via a temp name.
set -euo pipefail

root="${1:?usage: move_templates.sh <project-path> [--dry-run]}"
dry="${2:-}"
cd "$root"

run() {
  if [ "$dry" = "--dry-run" ]; then echo "+ $*"; else "$@"; fi
}

git rev-parse --is-inside-work-tree >/dev/null 2>&1 || { echo "not a git work tree: $root" >&2; exit 1; }

# 1. src/Template -> templates
if [ -d src/Template ]; then
  if [ -e templates ]; then
    echo "templates/ already exists; merge src/Template manually" >&2; exit 1
  fi
  run git mv src/Template templates
elif [ ! -d templates ]; then
  echo "no src/Template or templates/ directory found" >&2; exit 1
fi

# In --dry-run the move above didn't happen; inspect the source tree instead.
tpl="templates"; [ "$dry" = "--dry-run" ] && [ -d src/Template ] && tpl="src/Template"

# 2. special folders to lowercase (top level and inside plugin overrides).
#    NOT Error: CakePHP 5 renders error pages from templates/Error/ (case matters on Linux).
for name in Layout Element Email Cell Plugin; do
  lower="$(echo "$name" | tr '[:upper:]' '[:lower:]')"
  while IFS= read -r dir; do
    parent="$(dirname "$dir")"
    target="$parent/$lower"
    [ "$dir" = "$target" ] && continue
    run git mv "$dir" "$parent/${lower}__tmp"
    run git mv "$parent/${lower}__tmp" "$target"
  done < <(find "$tpl" -type d -name "$name" | sort -r)
done

# 3. .ctp -> .php
count=0
while IFS= read -r -d '' file; do
  run git mv "$file" "${file%.ctp}.php"
  count=$((count + 1))
done < <(find "$tpl" -name '*.ctp' -print0)

echo "done: $count .ctp file(s) renamed. Next: set App.paths.templates => [ROOT . DS . 'templates' . DS] in config/app.php"
