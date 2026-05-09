#!/usr/bin/env bash
# ============================================================
# Deploy script — contact form (PHPMailer + Gmail SMTP)
# Run this ON THE SERVER as a sudoer, from the project root
# (where this repo is checked out / uploaded).
# ============================================================
set -euo pipefail

WEBROOT="/var/www/rakelemenjivar.com"
API_DIR="${WEBROOT}/api"
ENV_SRC="$(dirname "$(readlink -f "$0")")/../.env.mail"
ENV_DST="${WEBROOT}/.env.mail"

echo "==> 1/5  Ensuring directories exist"
sudo mkdir -p "${API_DIR}"

echo "==> 2/5  Installing .env.mail at ${ENV_DST}"
sudo cp "${ENV_SRC}" "${ENV_DST}"
sudo chown www-data:www-data "${ENV_DST}"
sudo chmod 600 "${ENV_DST}"

echo "==> 3/5  Installing Composer + PHPMailer in ${API_DIR}"
if ! command -v composer >/dev/null 2>&1; then
  sudo apt update
  sudo apt install -y composer
fi
cd "${API_DIR}"
sudo -u www-data composer require phpmailer/phpmailer --no-interaction

echo "==> 4/5  Deploying contact.php"
# Adjust the source path if you rsync the repo somewhere else
PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
sudo cp "${PROJECT_ROOT}/public/api/contact.php" "${API_DIR}/contact.php"
sudo chown www-data:www-data "${API_DIR}/contact.php"
sudo chmod 644 "${API_DIR}/contact.php"

echo "==> 5/5  Reloading Apache"
sudo systemctl reload apache2

echo
echo "✅ Done. Test the contact form on https://rakelemenjivar.com/contact"
