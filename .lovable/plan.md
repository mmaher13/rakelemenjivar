## Problem

The server's `.env.mail` was edited in place (probably when you set `GMAIL_TO_EMAIL` previously). Now `git pull --ff-only` refuses because that local change conflicts with the incoming version from GitHub.

## Fix

Update `scripts/deploy.sh` so it discards local changes to `.env.mail` before pulling. The repo is the source of truth for `.env.mail` (that's the explicit design — credentials are committed so they never get lost), so it's safe to overwrite the server copy.

### Edit to `scripts/deploy.sh` (around lines 24-25)

Replace:
```bash
echo "==> 1/5  Pulling latest from GitHub"
git pull --ff-only
```

With:
```bash
echo "==> 1/5  Pulling latest from GitHub"
# .env.mail is tracked in the repo (source of truth). Discard any
# in-place edits on the server so the pull can fast-forward cleanly.
git checkout -- .env.mail 2>/dev/null || true
git pull --ff-only
```

### One-time command to unblock the current deploy

Before re-running `./deploy.sh`, run on the server:
```bash
cd /var/www/rakelemenjivar.com
git checkout -- .env.mail
./scripts/deploy.sh
```

After that, future deploys will self-heal automatically.
