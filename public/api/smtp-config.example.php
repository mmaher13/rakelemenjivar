<?php
/**
 * SMTP Configuration for PHPMailer
 *
 * Copy this file to `smtp-config.php` on the server and fill in real values.
 * `smtp-config.php` should NOT be committed to git (add to .gitignore).
 */

return [
    'host'        => 'smtp.example.com',   // e.g. smtp.gmail.com, smtp.zoho.com, mail.privateemail.com
    'port'        => 587,                  // 587 (STARTTLS) or 465 (SMTPS)
    'encryption'  => 'tls',                // 'tls' for 587, 'ssl' for 465
    'username'    => 'booking@rakelemenjivar.com',
    'password'    => 'YOUR_SMTP_PASSWORD_HERE',

    // Sender shown in the "From" header. Many providers require this to match the SMTP user/domain.
    'from_email'  => 'booking@rakelemenjivar.com',
    'from_name'   => 'Rakele Menjivar Website',

    // Where contact form submissions are delivered.
    'to_email'    => 'booking@rakelemenjivar.com',
    'to_name'     => 'Rakele Menjivar',
];
