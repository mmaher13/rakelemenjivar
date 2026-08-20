<?php
/**
 * Mail diagnostics — run ON THE SERVER:
 *
 *   sudo -u www-data php /var/www/rakelemenjivar.com/scripts/mail-test.php
 *   sudo -u www-data php /var/www/rakelemenjivar.com/scripts/mail-test.php you@example.com
 *
 * Checks: .env.mail readable, PHPMailer present, log files writable,
 * then opens a full SMTP conversation with Gmail and sends a test email.
 * Prints the raw SMTP dialogue so auth/relay errors are visible.
 */

$root = dirname(__DIR__);
$line = str_repeat('-', 60);

echo "$line\nRakele Menjivar — mail diagnostics\n$line\n";
echo 'Running as: ' . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n";
echo 'Project root: ' . $root . "\n\n";

// 1. .env.mail
require_once $root . '/public/lib/load_env.php';
$envPath = null;
foreach ([$root . '/.env.mail', $root . '/dist/.env.mail', $root . '/public/.env.mail'] as $p) {
    if (is_readable($p)) { $envPath = $p; break; }
}
if (!$envPath) {
    echo "[FAIL] .env.mail not readable. Expected at: $root/.env.mail\n";
    echo "       Fix: sudo chown www-data:www-data $root/.env.mail && sudo chmod 600 $root/.env.mail\n";
    exit(1);
}
$env = load_env_file($envPath);
echo "[ OK ] .env.mail read from $envPath\n";
$user = $env['GMAIL_USER'] ?? '';
$pass = $env['GMAIL_APP_PASSWORD'] ?? '';
$from = $env['GMAIL_FROM_ADDRESS'] ?? ($env['GMAIL_FROM_EMAIL'] ?? $user);
$to   = $argv[1] ?? ($env['GMAIL_TO_EMAIL'] ?? '');
echo "       GMAIL_USER      = " . ($user ?: '(empty!)') . "\n";
echo "       APP_PASSWORD    = " . ($pass ? strlen($pass) . ' chars' : '(empty!)') . "\n";
echo "       FROM            = $from\n";
echo "       TEST RECIPIENT  = " . ($to ?: '(none — pass one as an argument)') . "\n\n";
if ($user === '' || $pass === '' || $to === '') { echo "[FAIL] Missing credentials or recipient.\n"; exit(1); }
if (strlen($pass) !== 16) { echo "[WARN] Gmail app passwords are 16 chars with no spaces.\n\n"; }

// 2. PHPMailer
$src = null;
foreach ([$root . '/lib/PHPMailer/src', $root . '/dist/lib/PHPMailer/src', $root . '/public/lib/PHPMailer/src'] as $p) {
    if (is_readable($p . '/PHPMailer.php')) { $src = $p; break; }
}
if (!$src && is_readable($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
    echo "[ OK ] PHPMailer loaded via composer autoload\n";
} elseif ($src) {
    require_once $src . '/Exception.php';
    require_once $src . '/PHPMailer.php';
    require_once $src . '/SMTP.php';
    echo "[ OK ] PHPMailer found at $src\n";
} else {
    echo "[FAIL] PHPMailer not installed. Expected $root/lib/PHPMailer/src\n";
    exit(1);
}

// 3. Writable log/CSV
foreach (['contact.log', 'contactos.csv'] as $f) {
    $p = "$root/$f";
    $w = file_exists($p) ? is_writable($p) : is_writable($root);
    echo ($w ? '[ OK ] ' : '[FAIL] ') . "$f " . ($w ? 'writable' : "NOT writable by this user ($p)") . "\n";
}

// 4. Outbound port check
echo "\n[ .. ] TCP connect to smtp.gmail.com:587 ...\n";
$sock = @fsockopen('smtp.gmail.com', 587, $errno, $errstr, 10);
if (!$sock) { echo "[FAIL] Port 587 blocked ($errno $errstr) — the host firewall/provider is blocking SMTP.\n"; exit(1); }
fclose($sock);
echo "[ OK ] Port 587 reachable\n\n";

// 5. Real send with full SMTP transcript
echo "$line\nSMTP transcript\n$line\n";
try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->SMTPDebug  = 2;
    $mail->Debugoutput = 'echo';
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $user;
    $mail->Password   = $pass;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom($from, $env['GMAIL_FROM_NAME'] ?? 'Rakele Menjivar');
    $mail->Sender     = $user;
    $mail->addAddress($to);
    $mail->Subject    = '[Rakele Menjivar] SMTP test ' . date('Y-m-d H:i:s');
    $mail->Body       = "This is a diagnostic test from the rakelemenjivar.com server.\nIf you received this, SMTP delivery works.\n";
    $mail->isHTML(false);
    $mail->send();
    echo "\n$line\n[ OK ] Test email accepted by Gmail for $to\n";
    echo "       If it never arrives: check Spam, and check Gmail 'Send mail as' alias for $from.\n";
} catch (\Throwable $e) {
    echo "\n$line\n[FAIL] " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()) . "\n";
    echo "       535 => wrong app password / wrong GMAIL_USER\n";
    echo "       5.7.0 From address not allowed => add $from as a 'Send mail as' alias in Gmail\n";
    exit(1);
}
