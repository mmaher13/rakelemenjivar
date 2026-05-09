## Change

In `public/api/contact.php`, the auto-reply currently CCs `$fromEmail` (booking@rakelemenjivar.com, from `.env.mail`).

Update it to CC a fixed address: `rakele@rakelemenjivar.com` instead.

### Edit
- File: `public/api/contact.php` (around line ~237)
- Replace: `$reply->addCC($fromEmail, $fromName);`
- With: `$reply->addCC('rakele@rakelemenjivar.com', $fromName);`

No other changes. `setFrom` and `addReplyTo` stay as booking@ so Gmail SMTP auth still works.