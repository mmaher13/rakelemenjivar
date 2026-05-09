#!/usr/bin/env bash
# ============================================================
# Full-site deploy — rakelemenjivar.com
# Builds the Vite app locally and deploys everything to the
# Ubuntu Apache server, including the PHP contact API,
# PHPMailer, and the Gmail SMTP credentials.
#
# Run this script from the project root:
#     bash scripts/deploy.sh
#
# Requirements on your local machine:
#   - node + npm (or bun)
#   - rsync, ssh
#
# Requirements on the server:
#   - Apache with mod_rewrite
#   - PHP 7.4+ with openssl
#   - composer (the script will install it if missing)
#   - sudo access for the SSH user
# ============================================================
set -euo pipefail

# ---------- Configuration (edit these) ----------
SSH_HOST="${SSH_HOST:-rakelemenjivar.com}"     # server hostname or IP
SSH_USER="${SSH_USER:-ubuntu}"                  # SSH user (must have sudo)
WEBROOT="${WEBROOT:-/var/www/rakelemenjivar.com}"
# ------------------------------------------------

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "${PROJECT_ROOT}"

echo "==> 1/6  Installing dependencies"
if [ -f bun.lockb ] && command -v bun >/dev/null 2>&1; then
  bun install
else
  npm install
fi

echo "==> 2/6  Building production bundle (vite build)"
if [ -f bun.lockb ] && command -v bun >/dev/null 2>&1; then
  bun run build
else
  npm run build
fi

if [ ! -d dist ]; then
  echo "❌ dist/ not found after build" >&2
  exit 1
fi

# Make sure the PHP API and .htaccess end up in dist/
echo "==> 3/6  Staging PHP API + .htaccess into dist/"
mkdir -p dist/api
cp -r public/api/* dist/api/ 2>/dev/null || true

# .htaccess for SPA routing (created if missing)
if [ ! -f dist/.htaccess ]; then
  cat > dist/.htaccess <<'HTACCESS'
# SPA routing — let Apache serve real files, fall back to index.html
RewriteEngine On
RewriteBase /

# Don't rewrite real files or directories
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Don't rewrite the PHP API
RewriteRule ^api/ - [L]

# Everything else -> index.html (React Router handles it)
RewriteRule ^ index.html [L]

# Basic caching for static assets
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/svg+xml "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
</IfModule>

# Don't cache the entry HTML
<FilesMatch "\.(html)$">
  Header set Cache-Control "no-cache, must-revalidate"
</FilesMatch>

# Block access to sensitive files
<FilesMatch "^(\.env|composer\.(json|lock)|.*\.md)$">
  Require all denied
</FilesMatch>
HTACCESS
fi

echo "==> 4/6  Rsyncing site to ${SSH_USER}@${SSH_HOST}:${WEBROOT}"
# --delete removes stale files on the server but we EXCLUDE things we
# never want to wipe: api/vendor (PHPMailer) and the live .env.mail.
ssh "${SSH_USER}@${SSH_HOST}" "sudo mkdir -p ${WEBROOT} && sudo chown -R ${SSH_USER}:${SSH_USER} ${WEBROOT}"

rsync -avz --delete \
  --exclude 'api/vendor/' \
  --exclude 'api/composer.json' \
  --exclude 'api/composer.lock' \
  --exclude '.env.mail' \
  dist/ "${SSH_USER}@${SSH_HOST}:${WEBROOT}/"

# Ship the .env.mail separately to a path outside webroot, locked down
echo "==> 5/6  Installing .env.mail + PHPMailer on server"
scp .env.mail "${SSH_USER}@${SSH_HOST}:/tmp/.env.mail.upload"

ssh "${SSH_USER}@${SSH_HOST}" "bash -s" <<EOF
set -euo pipefail

# Move env file into place, lock it down
sudo mv /tmp/.env.mail.upload ${WEBROOT}/.env.mail
sudo chown www-data:www-data ${WEBROOT}/.env.mail
sudo chmod 600 ${WEBROOT}/.env.mail

# Install composer if needed
if ! command -v composer >/dev/null 2>&1; then
  sudo apt update
  sudo apt install -y composer
fi

# Install PHPMailer in the api/ directory if vendor/ is missing
if [ ! -d ${WEBROOT}/api/vendor ]; then
  cd ${WEBROOT}/api
  sudo -u www-data composer require phpmailer/phpmailer --no-interaction
fi

# Set proper ownership on the whole site
sudo chown -R www-data:www-data ${WEBROOT}
sudo find ${WEBROOT} -type d -exec chmod 755 {} \;
sudo find ${WEBROOT} -type f -exec chmod 644 {} \;
sudo chmod 600 ${WEBROOT}/.env.mail

# Reload Apache
sudo systemctl reload apache2
EOF

echo "==> 6/6  Done ✅"
echo
echo "Site:    https://rakelemenjivar.com"
echo "Test the contact form to verify SMTP works."
