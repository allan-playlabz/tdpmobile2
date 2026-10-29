#!/bin/bash


BRANCH=${1:-STAGING}
APP_DIR=$(dirname "$(readlink -f "$0")")


# Pull latest code from GitHub
echo "Pulling latest code from GitHub..."
git fetch origin $BRANCH
git reset --hard origin/$BRANCH

# Install Composer dependencies (production only, optimized)
echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

sudo chmod 2775 /var/www/$FOLDER && find /var/www/$FOLDER -type d -exec sudo chmod 2775 {} \;

find /var/www/$FOLDER -type f -exec sudo chmod 0664 {} \;

sudo systemctl reload httpd

echo "========================================="
echo "Deployment completed successfully!"
echo "Finished at $(date)"
echo "========================================="
