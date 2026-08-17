#!/usr/bin/env bash
# ============================================================
# Local push + remote deploy
#
# Run this ON YOUR MACHINE from the repo root:
#     bash scripts/push-deploy.sh "optional commit message"
#
# It will:
#   1. Commit any local changes (if there are any)
#   2. Push them to GitHub
#   3. SSH into the server and run scripts/deploy.sh there
#
# Configure the SSH target below (or export the vars before running):
#     SSH_USER=ubuntu SSH_HOST=1.2.3.4 bash scripts/push-deploy.sh
# ============================================================
set -euo pipefail

SSH_USER="${SSH_USER:-ubuntu}"
SSH_HOST="${SSH_HOST:-rakelemenjivar.com}"
SSH_PORT="${SSH_PORT:-22}"
REMOTE_DIR="${REMOTE_DIR:-/var/www/rakelemenjivar.com}"
BRANCH="${BRANCH:-$(git rev-parse --abbrev-ref HEAD)}"
COMMIT_MSG="${1:-chore: deploy $(date -u '+%Y-%m-%d %H:%M UTC')}"

echo "==> 1/3  Committing local changes (if any)"
if [ -n "$(git status --porcelain)" ]; then
  git add -A
  git commit -m "${COMMIT_MSG}"
else
  echo "    nothing to commit — working tree clean"
fi

echo "==> 2/3  Pushing '${BRANCH}' to GitHub"
git push origin "${BRANCH}"

echo "==> 3/3  Running deploy on ${SSH_USER}@${SSH_HOST}:${REMOTE_DIR}"
ssh -p "${SSH_PORT}" "${SSH_USER}@${SSH_HOST}" \
  "set -euo pipefail; cd '${REMOTE_DIR}' && bash scripts/deploy.sh"

echo
echo "✅ Push + deploy complete — https://rakelemenjivar.com"
