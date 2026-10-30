#!/bin/bash
#
# Deploy the latest code of a branch to this server.
#
# Usage: ./deploy.sh [branch]        (default: STAGING)
#   REMOTE=origin  git remote to pull from
#   WEB_SERVICE=httpd  service reloaded at the end (e.g. "php-fpm httpd" when PHP runs under FPM)
#
set -euo pipefail

# Everything runs inside main() so bash reads the whole script before
# `git reset` below can replace this file mid-run.
main() {
BRANCH=${1:-STAGING}
REMOTE=${REMOTE:-origin}
WEB_SERVICE=${WEB_SERVICE:-httpd}
APP_DIR=$(dirname "$(readlink -f "$0")")

trap 'echo "Deployment FAILED at line $LINENO (exit $?)" >&2' ERR

cd "$APP_DIR"
echo "Deploying $REMOTE/$BRANCH to $APP_DIR"

# Pull latest code. Untracked files (config/app_config.php, config/.env) are left alone.
echo "Pulling latest code..."
git fetch "$REMOTE" "$BRANCH"
git checkout -B "$BRANCH" "$REMOTE/$BRANCH"
git reset --hard "$REMOTE/$BRANCH"

# Install Composer dependencies (production only, optimized)
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Permissions: group read/write everywhere, setgid on folders so new files keep the group.
# X (capital) keeps the execute bit only on folders and files that already had it (bin/cake, vendor/bin/*, this script).
echo "Fixing permissions..."
sudo chmod -R u+rwX,g+rwX,o+rX "$APP_DIR"
sudo find "$APP_DIR" -path "$APP_DIR/.git" -prune -o -type d -exec chmod g+s {} +

# Clear CakePHP caches (models, routes, translations) so the new code is used.
echo "Clearing cache..."
php bin/cake.php cache clear_all

for service in $WEB_SERVICE; do
  sudo systemctl reload "$service"
done

echo "========================================="
echo "Deployment completed successfully!"
echo "Commit: $(git log -1 --format='%h %s')"
echo "Finished at $(date)"
echo "========================================="
}

main "$@"
exit
