#!/usr/bin/env bash
# ============================================================
# Deploy — pulls latest code from GitHub, rebuilds, restores the
# PHP mail endpoint + PHPMailer into dist/, fixes permissions.
#
# Run ON THE SERVER:
#     sudo bash /var/www/rakelemenjivar.com/scripts/deploy.sh
# ============================================================
set -euo pipefail

PROJECT_DIR="/var/www/rakelemenjivar.com"
DIST_DIR="$PROJECT_DIR/dist"
WEB_USER="www-data"

[ "$EUID" -eq 0 ] || { echo "Run with sudo."; exit 1; }
cd "$PROJECT_DIR"

# .env.mail is tracked in the repo (source of truth). Discard local edits so the pull is clean.
git checkout -- .env.mail 2>/dev/null || true
git fetch origin main
git reset --hard origin/main

[ -f package-lock.json ] && npm ci || npm install
npm run build
[ -d "$DIST_DIR" ] || { echo "Build produced no dist/."; exit 1; }

# Serve the PHP endpoint from both possible DocumentRoots.
cp "$PROJECT_DIR/public/contacto.php" "$DIST_DIR/contacto.php"
cp "$PROJECT_DIR/public/contacto.php" "$PROJECT_DIR/contacto.php"

# Guard: abort if GitHub didn't actually have the latest code.
for target in "$DIST_DIR/contacto.php" "$PROJECT_DIR/contacto.php"; do
  grep -q "contact_debug_log"   "$target" || { echo "$target is stale."; exit 1; }
  grep -q "contact_storage_dir" "$target" || { echo "$target is stale."; exit 1; }
done
echo "Deployed: $(git rev-parse --short HEAD) — $(git log -1 --pretty=%s)"

# PHPMailer + helpers into dist/lib/
mkdir -p "$DIST_DIR/lib"
if [ -d "$PROJECT_DIR/lib/PHPMailer/src" ]; then
  mkdir -p "$DIST_DIR/lib/PHPMailer"
  cp -r "$PROJECT_DIR/lib/PHPMailer/src" "$DIST_DIR/lib/PHPMailer/"
else
  echo "[!] PHPMailer missing — emails disabled."
fi
cp "$PROJECT_DIR/public/lib/load_env.php"   "$DIST_DIR/lib/load_env.php"
cp "$PROJECT_DIR/public/lib/append_csv.php" "$DIST_DIR/lib/append_csv.php"

# Permissions
chown -R "$WEB_USER:$WEB_USER" "$DIST_DIR"
find "$DIST_DIR" -type d -exec chmod 755 {} \;
find "$DIST_DIR" -type f -exec chmod 644 {} \;
[ -f "$PROJECT_DIR/.env.mail" ] && chown "$WEB_USER:$WEB_USER" "$PROJECT_DIR/.env.mail" && chmod 600 "$PROJECT_DIR/.env.mail"
[ -d "$PROJECT_DIR/lib" ] && chown -R "$WEB_USER:$WEB_USER" "$PROJECT_DIR/lib"

# Data files must exist and be writable by Apache.
touch "$PROJECT_DIR/contactos.csv" "$PROJECT_DIR/contact.log"
chown "$WEB_USER:$WEB_USER" "$PROJECT_DIR/contactos.csv" "$PROJECT_DIR/contact.log"
chmod 640 "$PROJECT_DIR/contactos.csv"
chmod 666 "$PROJECT_DIR/contact.log"

# Clear OPcache — otherwise Apache keeps serving the OLD contacto.php.
while read -r unit _; do
  [ -n "$unit" ] && systemctl restart "$unit"
done < <(systemctl list-units --type=service --state=active 'php*-fpm.service' --no-legend --no-pager 2>/dev/null || true)
systemctl is-active --quiet apache2 && systemctl restart apache2

echo "✅ Deploy complete — https://rakelemenjivar.com"
