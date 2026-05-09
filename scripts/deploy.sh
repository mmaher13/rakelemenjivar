#!/usr/bin/env bash
# ============================================================
# Update deploy — pulls latest code from GitHub and rebuilds.
# Run this ON THE SERVER from the repo root:
#     cd /var/www/rakelemenjivar.com
#     bash scripts/deploy.sh
#
# Assumes one-time setup is already done (Apache vhost,
# .env.mail in place, PHPMailer installed in public/api/vendor).
# ============================================================
set -euo pipefail

REPO_DIR="/var/www/rakelemenjivar.com"
cd "${REPO_DIR}"

echo "==> 0/5  Ensuring persistent log dir exists"
# Created early so it survives even if a later step fails.
sudo mkdir -p "${REPO_DIR}/logs"
sudo touch "${REPO_DIR}/logs/contact.log"
sudo chown -R www-data:www-data "${REPO_DIR}/logs"
sudo chmod 755 "${REPO_DIR}/logs"
sudo chmod 644 "${REPO_DIR}/logs/contact.log"

echo "==> 1/5  Pulling latest from GitHub"
# .env.mail is tracked in the repo (source of truth). Discard any
# in-place edits on the server so the pull can fast-forward cleanly.
git checkout -- .env.mail 2>/dev/null || true
git pull --ff-only

echo "==> 2/5  Installing npm dependencies"
npm install

echo "==> 3/5  Building production bundle"
npm run build

echo "==> 4/5  Staging PHP API + .htaccess into dist/"
mkdir -p dist/api
# Copy contact.php (and any other API files) but DO NOT overwrite vendor/
rsync -a --exclude 'vendor/' --exclude 'composer.*' public/api/ dist/api/
# Symlink vendor/ so PHPMailer is available without copying it every build
if [ ! -e dist/api/vendor ]; then
  ln -s "${REPO_DIR}/public/api/vendor" dist/api/vendor
fi

# Create .htaccess if missing
if [ ! -f dist/.htaccess ]; then
  cat > dist/.htaccess <<'HTACCESS'
RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

RewriteRule ^api/ - [L]
RewriteRule ^ index.html [L]

<FilesMatch "^(\.env.*|composer\.(json|lock))$">
  Require all denied
</FilesMatch>
HTACCESS
fi

echo "==> 5/5  Fixing permissions and reloading Apache"
sudo chown -R www-data:www-data "${REPO_DIR}/dist"
sudo chown -R www-data:www-data "${REPO_DIR}/public/api/vendor" 2>/dev/null || true
sudo chown www-data:www-data "${REPO_DIR}/.env.mail" 2>/dev/null || true
sudo chmod 600 "${REPO_DIR}/.env.mail" 2>/dev/null || true
sudo systemctl reload apache2

echo
echo "✅ Deploy complete — https://rakelemenjivar.com"
echo "   Tail contact log:  sudo tail -f ${REPO_DIR}/logs/contact.log"
