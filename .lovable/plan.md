## Change

Route the **new inquiry** email to `rakele@skygr.com` while keeping the **confirmation** email sent from `booking@rakelemenjivar.com` to the client (unchanged).

### Edits

**1. `.env.mail`**
- Change `GMAIL_TO_EMAIL=booking@rakelemenjivar.com` → `GMAIL_TO_EMAIL=rakele@skygr.com`

This makes the primary inquiry email (the one with the client's submission details) deliver to `rakele@skygr.com`. SMTP auth still uses `booking@rakelemenjivar.com` (GMAIL_USER), and the From header stays `booking@rakelemenjivar.com` (GMAIL_FROM_EMAIL), so Gmail's "send to self" deduplication no longer applies.

**2. `public/api/contact.php` (auto-reply to client)**
- No change to From / To — already sends from `booking@rakelemenjivar.com` to the client.
- Question: should the existing `addBCC('rakele@rakelemenjivar.com', ...)` on the auto-reply stay, or be removed? Since the new inquiry now lands in `rakele@skygr.com`, the BCC of the client confirmation to `rakele@rakelemenjivar.com` may be redundant. Default plan: **remove the BCC** to keep things clean. Confirm if you'd rather keep it.

### Deployment note
After deploy, the server copy at `/var/www/rakelemenjivar.com/.env.mail` must be updated to the new `GMAIL_TO_EMAIL` value (the deploy script copies `.env.mail` from the repo, so a normal deploy handles it).
