<?php
/**
 * Contact endpoint — Rakele Menjivar
 * Standalone PHP + PHPMailer over Gmail SMTP. No database, no Node runtime.
 * Private files (.env.mail, contactos.csv, contact.log) live one level above dist/.
 */
$site_name      = 'Rakele Menjivar';
$subject_prefix = '[Rakele Menjivar] ';

function contact_storage_dir(): string {
    return in_array(basename(__DIR__), ['dist', 'public'], true) ? dirname(__DIR__) : __DIR__;
}
$contact_storage_dir = contact_storage_dir();

require_once __DIR__ . '/lib/append_csv.php';
require_once __DIR__ . '/lib/load_env.php';

@ini_set('log_errors', '1');
@ini_set('error_log', $contact_storage_dir . '/php-mail-errors.log');
error_reporting(E_ALL);

// ---- PHPMailer discovery: composer first, then a manual install ----
$phpmailer_loaded = false;
$autoload_candidates = [
    __DIR__ . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];
$manual_candidates = [
    __DIR__ . '/lib/PHPMailer/src',
    dirname(__DIR__) . '/lib/PHPMailer/src',
];
foreach ($autoload_candidates as $autoload) {
    if (is_readable($autoload)) { require_once $autoload; $phpmailer_loaded = true; break; }
}
if (!$phpmailer_loaded) {
    foreach ($manual_candidates as $manual) {
        if (is_readable($manual . '/PHPMailer.php')) {
            require_once $manual . '/Exception.php';
            require_once $manual . '/PHPMailer.php';
            require_once $manual . '/SMTP.php';
            $phpmailer_loaded = true;
            break;
        }
    }
}
if (!$phpmailer_loaded) {
    error_log('[contacto] PHPMailer NOT FOUND. Checked: '
        . implode(', ', array_merge($autoload_candidates, $manual_candidates)));
}

// ---- Credentials ----
$mail_env = [];
foreach ([__DIR__ . '/.env.mail', dirname(__DIR__) . '/.env.mail'] as $env_path) {
    $mail_env = load_env_file($env_path);
    if (!empty($mail_env)) break;
}
$GMAIL_USER         = $mail_env['GMAIL_USER']         ?? '';
$GMAIL_APP_PASSWORD = $mail_env['GMAIL_APP_PASSWORD'] ?? '';
$GMAIL_FROM_ADDRESS = $mail_env['GMAIL_FROM_ADDRESS'] ?? ($mail_env['GMAIL_FROM_EMAIL'] ?? $GMAIL_USER);
$GMAIL_FROM_NAME    = $mail_env['GMAIL_FROM_NAME']    ?? $site_name;
$recipient_email    = $mail_env['GMAIL_TO_EMAIL']     ?? 'rakele@skygr.com';

function send_smtp_mail(string $to, string $subject, string $body, array $opts = []): bool {
    global $phpmailer_loaded, $GMAIL_USER, $GMAIL_APP_PASSWORD,
           $GMAIL_FROM_ADDRESS, $GMAIL_FROM_NAME;

    if (!$phpmailer_loaded) { error_log('[contacto] PHPMailer not installed'); return false; }
    if ($GMAIL_USER === '' || $GMAIL_APP_PASSWORD === '') {
        error_log('[contacto] Gmail SMTP credentials missing in .env.mail');
        return false;
    }
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $GMAIL_USER;
        $mail->Password   = $GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom($opts['from_addr'] ?? $GMAIL_FROM_ADDRESS, $opts['from_name'] ?? $GMAIL_FROM_NAME);
        $mail->Sender     = $GMAIL_USER; // envelope sender = authenticated account
        if (!empty($opts['reply_to'])) {
            $mail->addReplyTo($opts['reply_to'], $opts['reply_to_name'] ?? '');
        }
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->isHTML(false);
        $mail->send();
        return true;
    } catch (\Throwable $e) {
        error_log('[contacto] SMTP send failed to ' . $to . ': '
            . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
        return false;
    }
}

function is_ajax_contact_request(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requested_with = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    return $requested_with === 'xmlhttprequest' || strpos($accept, 'application/json') !== false;
}

function finish_contact_response(bool $ok, string $message = '', array $extra = []): void {
    if (!is_ajax_contact_request()) return; // fall through to the HTML page
    http_response_code($ok ? 200 : 500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function contact_debug_log(string $stage, array $data = []): bool {
    $log_path = contact_storage_dir() . '/contact.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $stage;
    if (!empty($data)) {
        $line .= ' ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    if (@file_put_contents($log_path, $line . "\n", FILE_APPEND | LOCK_EX) === false) {
        error_log('[contacto] contact.log write failed: ' . $log_path);
        return false;
    }
    @chmod($log_path, 0660);
    return true;
}

$error    = '';
$success  = false;
$formData = ['name' => '', 'email' => '', 'company' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Log the raw request FIRST, before any validation can reject it.
    $raw_body = file_get_contents('php://input');
    contact_debug_log('POST received', [
        'ip'           => $_SERVER['REMOTE_ADDR'] ?? '',
        'ua'           => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
        'is_ajax'      => is_ajax_contact_request(),
        'post'         => $_POST,
        'raw_len'      => strlen($raw_body),
        'raw_preview'  => substr($raw_body, 0, 500),
    ]);

    // 2. Sanitize.
    $formData['name']    = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $formData['email']   = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
    $formData['company'] = filter_input(INPUT_POST, 'company', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $formData['message'] = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $honeypot            = trim((string) (filter_input(INPUT_POST, 'website', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''));

    $request_id = substr(bin2hex(random_bytes(6)), 0, 12);
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // 3. Validate — keep limits generous, over-strict rules silently reject real users.
    if ($honeypot !== '') {
        contact_debug_log('honeypot_triggered', ['ip' => $ip]);
        finish_contact_response(true, 'ok'); // silently accept bots
        $success = true;
    } elseif (empty($formData['name'])) {
        $error = 'Please enter your name.';
    } elseif (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (empty($formData['message'])) {
        $error = 'Please tell us about your project.';
    } elseif (strlen($formData['message']) > 5000) {
        $error = 'Your message is too long (5000 characters maximum).';
    } else {
        // 4. Persist BEFORE sending, so a mail failure never loses the lead.
        $csv_headers = ['date', 'name', 'email', 'company', 'message', 'ip', 'user_agent', 'request_id'];
        $csv_row = [date('Y-m-d H:i:s'), $formData['name'], $formData['email'], $formData['company'],
                    $formData['message'], $ip, $ua, $request_id];
        $saved_csv = append_csv_row($contact_storage_dir . '/contactos.csv', $csv_headers, $csv_row);
        contact_debug_log('csv_append', ['ok' => $saved_csv, 'path' => $contact_storage_dir . '/contactos.csv']);

        // Shared details block, appended to both emails.
        $detailsBlock = "\n\n-----------------------------\nSUBMISSION DETAILS\n-----------------------------\n"
            . "Name:    {$formData['name']}\n"
            . "Email:   {$formData['email']}\n"
            . "Company: " . ($formData['company'] !== '' ? $formData['company'] : '-') . "\n\n"
            . "Project details:\n{$formData['message']}\n\n"
            . "-----------------------------\nMETADATA\n-----------------------------\n"
            . "Date (UTC):  " . gmdate('Y-m-d H:i:s') . "\n"
            . "IP address:  {$ip}\n"
            . "User agent:  {$ua}\n"
            . "Request ID:  {$request_id}\n";

        // 5. Admin notification — Reply-To is the visitor so you can just hit Reply.
        $adminBody = "New inquiry from the rakelemenjivar.com contact form." . $detailsBlock;
        $sent_admin = send_smtp_mail($recipient_email, $subject_prefix . 'New inquiry from ' . $formData['name'], $adminBody, [
            'from_name'     => $site_name,
            'reply_to'      => $formData['email'],
            'reply_to_name' => $formData['name'],
        ]);
        contact_debug_log('admin_email_sent', ['ok' => $sent_admin, 'to' => $recipient_email]);

        // 6. Confirmation to the visitor.
        $confSubject = 'Thank you for your message - ' . $site_name;
        $confBody = "Hello {$formData['name']},\n\n"
            . "Thank you for your message. We will get back to you shortly.\n\n"
            . "Warm regards,\n{$site_name}"
            . $detailsBlock;
        $sent_conf = send_smtp_mail($formData['email'], $confSubject, $confBody, [
            'from_name' => $site_name,
            'reply_to'  => $GMAIL_FROM_ADDRESS,
        ]);
        contact_debug_log('confirmation_email_sent', ['ok' => $sent_conf, 'to' => $formData['email']]);

        // 7. Reply to the SPA.
        $success = true;
        finish_contact_response(true, 'ok', [
            'saved_csv'    => $saved_csv,
            'admin_email'  => $sent_admin,
            'confirmation' => $sent_conf,
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== '') {
    contact_debug_log('validation_error', ['error' => $error]);
    finish_contact_response(false, $error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex" />
<title>Contact - Rakele Menjivar</title>
<style>
  body { background:#0f0f10; color:#f2f2f2; font-family:Georgia, serif; margin:0; padding:48px 20px; }
  .wrap { max-width:520px; margin:0 auto; }
  h1 { font-weight:400; letter-spacing:.04em; }
  label { display:block; font-size:12px; letter-spacing:.14em; text-transform:uppercase; color:#a9a9a9; margin:20px 0 6px; }
  input, textarea { width:100%; background:#17171a; border:1px solid #33333a; color:#f2f2f2; padding:12px; font-size:15px; box-sizing:border-box; }
  button { margin-top:24px; background:#c9a227; color:#0f0f10; border:0; padding:14px 24px; letter-spacing:.12em; text-transform:uppercase; cursor:pointer; }
  .msg { padding:12px; border:1px solid #33333a; margin-bottom:16px; }
  a { color:#c9a227; }
</style>
</head>
<body>
<div class="wrap">
  <h1>Let's Work Together</h1>
  <?php if ($success): ?>
    <p class="msg">Thank you for your message. We will get back to you shortly.</p>
  <?php elseif ($error !== ''): ?>
    <p class="msg"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php endif; ?>
  <form method="post" action="/contacto.php">
    <div style="position:absolute;left:-9999px;" aria-hidden="true">
      <label for="website">Website</label>
      <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" />
    </div>
    <label for="name">Your name *</label>
    <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($formData['name'], ENT_QUOTES, 'UTF-8'); ?>" />
    <label for="email">Email address *</label>
    <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($formData['email'], ENT_QUOTES, 'UTF-8'); ?>" />
    <label for="company">Company / agency</label>
    <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($formData['company'], ENT_QUOTES, 'UTF-8'); ?>" />
    <label for="message">Project details *</label>
    <textarea id="message" name="message" rows="6" required><?php echo htmlspecialchars($formData['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
    <button type="submit">Send message</button>
  </form>
  <p style="margin-top:32px"><a href="/">&larr; Back to rakelemenjivar.com</a></p>
</div>
</body>
</html>
