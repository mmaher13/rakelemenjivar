<?php
/**
 * Contact Form Email Handler (PHPMailer + Gmail SMTP)
 *
 * Server requirements:
 *   1. PHP 7.4+ with openssl extension
 *   2. Composer installed:  sudo apt install composer
 *   3. From this /api directory on the server, run:
 *        composer require phpmailer/phpmailer
 *      (this creates /api/vendor/ and /api/composer.json)
 *   4. Create /var/www/rakelemenjivar.com/.env.mail with Gmail credentials.
 *
 * Logging:
 *   All activity is appended to /var/www/rakelemenjivar.com/logs/contact.log
 *   (outside dist/ so it survives `npm run build`). The web server user
 *   (www-data) must be able to write to it. scripts/deploy.sh creates this
 *   automatically. To set up manually:
 *     sudo mkdir -p /var/www/rakelemenjivar.com/logs
 *     sudo touch /var/www/rakelemenjivar.com/logs/contact.log
 *     sudo chown -R www-data:www-data /var/www/rakelemenjivar.com/logs
 *     sudo chmod 644 /var/www/rakelemenjivar.com/logs/contact.log
 *   Tail it with:
 *     sudo tail -f /var/www/rakelemenjivar.com/logs/contact.log
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// ---------- Logger ----------
// Log file lives OUTSIDE dist/ so it survives `npm run build` (which wipes dist/).
// Falls back to /tmp if the preferred path is not writable.
(function () {
    $candidates = [
        '/var/www/rakelemenjivar.com/logs/contact.log',
        '/var/log/rakelemenjivar/contact.log',
        __DIR__ . '/contact.log', // legacy fallback
        '/tmp/rakelemenjivar-contact.log',
    ];
    foreach ($candidates as $path) {
        $dir = dirname($path);
        if (!is_dir($dir)) continue;
        if (is_writable($dir) || (file_exists($path) && is_writable($path))) {
            define('CONTACT_LOG_FILE', $path);
            return;
        }
    }
    define('CONTACT_LOG_FILE', '/tmp/rakelemenjivar-contact.log');
})();
define('CONTACT_REQUEST_ID', substr(bin2hex(random_bytes(4)), 0, 8));

function clog(string $level, string $msg, array $ctx = []): void {
    $line = sprintf(
        "[%s] [%s] [%s] %s%s\n",
        date('Y-m-d H:i:s'),
        CONTACT_REQUEST_ID,
        strtoupper($level),
        $msg,
        $ctx ? ' ' . json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : ''
    );
    @file_put_contents(CONTACT_LOG_FILE, $line, FILE_APPEND | LOCK_EX);
    // Also send to Apache error log as a fallback
    error_log('contact.php ' . trim($line));
}

clog('info', '--- request received', [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '?',
    'ip'     => $_SERVER['REMOTE_ADDR']     ?? '?',
    'ua'     => $_SERVER['HTTP_USER_AGENT'] ?? '?',
]);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    clog('info', 'preflight OPTIONS, returning 200');
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    clog('warn', 'method not allowed');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// ---------- Parse + validate input ----------
$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?: [];
clog('info', 'payload parsed', [
    'bytes'    => strlen($input),
    'has_name' => !empty($data['name']),
    'has_email'=> !empty($data['email']),
    'has_msg'  => !empty($data['message']),
]);

// Honeypot: bots fill the hidden "website" field
$honeypot = isset($data['website']) ? trim($data['website']) : '';
if (!empty($honeypot)) {
    clog('warn', 'honeypot triggered, silently dropping', ['honeypot' => $honeypot]);
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    exit();
}

$name    = isset($data['name'])    ? trim($data['name'])    : '';
$email   = isset($data['email'])   ? trim($data['email'])   : '';
$company = isset($data['company']) ? trim($data['company']) : '';
$message = isset($data['message']) ? trim($data['message']) : '';

$errors = [];
if ($name === '')                                        { $errors[] = 'Name is required'; }
elseif (strlen($name) > 100)                             { $errors[] = 'Name must be less than 100 characters'; }

if ($email === '')                                       { $errors[] = 'Email is required'; }
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))      { $errors[] = 'Invalid email address'; }
elseif (strlen($email) > 255)                            { $errors[] = 'Email must be less than 255 characters'; }

if (strlen($company) > 200)                              { $errors[] = 'Company name must be less than 200 characters'; }

if ($message === '')                                     { $errors[] = 'Message is required'; }
elseif (strlen($message) > 5000)                         { $errors[] = 'Message must be less than 5000 characters'; }

if (!empty($errors)) {
    clog('warn', 'validation failed', ['errors' => $errors]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}
clog('info', 'validation OK', ['from' => $email, 'name' => $name]);

// ---------- Load PHPMailer ----------
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    clog('error', 'vendor/autoload.php missing', ['expected' => $autoload]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail library not installed on server.']);
    exit();
}
require $autoload;
clog('info', 'PHPMailer autoload loaded');

// ---------- Load .env.mail ----------
function load_env_mail(array $candidates): array {
    foreach ($candidates as $path) {
        if (is_file($path) && is_readable($path)) {
            clog('info', '.env.mail found', ['path' => $path]);
            $env = [];
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#') continue;
                if (strpos($line, '=') === false) continue;
                [$k, $v] = explode('=', $line, 2);
                $env[trim($k)] = trim($v, " \t\"'");
            }
            return $env;
        }
    }
    clog('error', '.env.mail not found in any candidate path', ['candidates' => $candidates]);
    return [];
}

$envCandidates = [
    '/var/www/rakelemenjivar/.env.mail',
    '/var/www/rakelemenjivar.com/.env.mail',
    dirname(__DIR__, 2) . '/.env.mail',
    dirname(__DIR__) . '/.env.mail',
];
$env = load_env_mail($envCandidates);

$gmailUser = $env['GMAIL_USER']         ?? '';
$gmailPass = $env['GMAIL_APP_PASSWORD'] ?? '';
$fromName  = $env['GMAIL_FROM_NAME']    ?? 'Rakele Menjivar';
$fromEmail = $env['GMAIL_FROM_EMAIL']   ?? 'booking@rakelemenjivar.com';
$toEmail   = $env['GMAIL_TO_EMAIL']     ?? 'booking@rakelemenjivar.com';

clog('info', 'env loaded', [
    'gmail_user_set' => $gmailUser !== '',
    'gmail_pass_set' => $gmailPass !== '',
    'from_email'     => $fromEmail,
    'to_email'       => $toEmail,
]);

if ($gmailUser === '' || $gmailPass === '') {
    clog('error', 'GMAIL_USER or GMAIL_APP_PASSWORD missing');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail credentials not configured on server.']);
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

// Capture PHPMailer SMTP debug output into our log
$mail->SMTPDebug   = SMTP::DEBUG_SERVER;     // 0=off, 2=client+server, 3=connection
$mail->Debugoutput = function ($str, $level) {
    clog('smtp', trim($str), ['lvl' => $level]);
};

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $gmailUser;
    $mail->Password   = $gmailPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 20;

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail, $fromName);
    $mail->addReplyTo($email, $name);

    $subject = "New Inquiry from {$name}" . ($company !== '' ? " - {$company}" : '');
    $mail->Subject = $subject;

    $plain  = "New contact form submission from rakelemenjivar.com\n";
    $plain .= "----------------------------------------\n";
    $plain .= "FROM: {$name}\n";
    $plain .= "EMAIL: {$email}\n";
    $plain .= "COMPANY: " . ($company !== '' ? $company : 'Not provided') . "\n";
    $plain .= "----------------------------------------\n\n";
    $plain .= "MESSAGE:\n{$message}\n\n";
    $plain .= "----------------------------------------\n";
    $plain .= "Sent from the website contact form.";

    $mail->isHTML(false);
    $mail->Body = $plain;

    clog('info', 'attempting primary SMTP send', ['to' => $toEmail, 'subject' => $subject]);
    $mail->send();
    clog('info', 'primary SMTP send OK');

    // ---------- Auto-reply to the form submitter ----------
    try {
        $reply = new PHPMailer(true);
        $reply->SMTPDebug   = SMTP::DEBUG_SERVER;
        $reply->Debugoutput = function ($str, $level) {
            clog('smtp-reply', trim($str), ['lvl' => $level]);
        };
        $reply->isSMTP();
        $reply->Host       = 'smtp.gmail.com';
        $reply->SMTPAuth   = true;
        $reply->Username   = $gmailUser;
        $reply->Password   = $gmailPass;
        $reply->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $reply->Port       = 587;
        $reply->CharSet    = 'UTF-8';
        $reply->Timeout    = 20;

        $reply->setFrom($fromEmail, $fromName);
        $reply->addAddress($email, $name);
        $reply->addCC($fromEmail, $fromName);
        $reply->addReplyTo($fromEmail, $fromName);

        $reply->Subject = 'Thank you for your message';
        $reply->isHTML(false);
        // Honor X-Forwarded-For when behind Apache/proxy
        $ipRaw = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $clientIp = trim(explode(',', $ipRaw)[0]);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
        $submittedAt = date('Y-m-d H:i:s T');

        $reply->Body =
            "Hi {$name},\n\n" .
            "Thank you for the message. Rakele will get back to you shortly.\n\n" .
            "Warm regards,\n" .
            "Rakele Menjivar\n" .
            "booking@rakelemenjivar.com\n\n" .
            "----------------------------------------\n" .
            "SUBMISSION DETAILS\n" .
            "----------------------------------------\n" .
            "NAME: {$name}\n" .
            "EMAIL: {$email}\n" .
            "COMPANY: " . ($company !== '' ? $company : 'Not provided') . "\n\n" .
            "PROJECT DETAILS:\n" .
            "{$message}\n\n" .
            "----------------------------------------\n" .
            "METADATA\n" .
            "----------------------------------------\n" .
            "SUBMITTED: {$submittedAt}\n" .
            "IP ADDRESS: {$clientIp}\n" .
            "USER AGENT: {$userAgent}\n" .
            "REFERRER: {$referer}\n" .
            "REQUEST ID: " . CONTACT_REQUEST_ID . "\n" .
            "----------------------------------------";

        clog('info', 'attempting auto-reply', ['to' => $email]);
        $reply->send();
        clog('info', 'auto-reply OK');
    } catch (Exception $e) {
        clog('error', 'auto-reply failed', [
            'message'   => $e->getMessage(),
            'errorInfo' => $reply->ErrorInfo ?? null,
        ]);
    }

    clog('info', '=== success, returning 200');
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} catch (Exception $e) {
    clog('error', 'primary SMTP send FAILED', [
        'message'   => $e->getMessage(),
        'errorInfo' => $mail->ErrorInfo ?? null,
    ]);
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message. Please try again later.',
    ]);
}
