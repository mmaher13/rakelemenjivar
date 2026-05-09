## Update auto-reply email to include submission details

In `public/api/contact.php`, modify the auto-reply email body (the `$reply->Body` block) to append the submitter's details below the thank-you message, so that the CC'd booking inbox sees the full submission.

### New auto-reply body

```
Hi {name},

Thank you for the message. Rakele will get back to you shortly.

Warm regards,
Rakele Menjivar
booking@rakelemenjivar.com

----------------------------------------
SUBMISSION DETAILS
----------------------------------------
NAME: {name}
EMAIL: {email}
COMPANY: {company or "Not provided"}

PROJECT DETAILS:
{message}
----------------------------------------
```

### Notes
- Only the `$reply->Body` string changes; SMTP setup, recipients (To: submitter, CC: booking), and logging stay the same.
- No frontend changes.
- After deploy, the file needs to be pushed to the server (`/var/www/rakelemenjivar.com/dist/api/contact.php`).
