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
 *   4. Create /var/www/rakelemenjivar/.env.mail with Gmail credentials (see deploy notes).
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// ---------- Parse + validate input ----------
$input = file_get_contents('php://input');
$data  = json_decode($input, true) ?: [];

// Honeypot: bots fill the hidden "website" field
$honeypot = isset($data['website']) ? trim($data['website']) : '';
if (!empty($honeypot)) {
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
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// ---------- Load PHPMailer ----------
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail library not installed on server.']);
    exit();
}
require $autoload;

// ---------- Load .env.mail ----------
// Look in a few common locations so this works regardless of webroot layout.
function load_env_mail(array $candidates): array {
    foreach ($candidates as $path) {
        if (is_file($path) && is_readable($path)) {
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
    return [];
}

$env = load_env_mail([
    '/var/www/rakelemenjivar/.env.mail',
    '/var/www/rakelemenjivar.com/.env.mail',
    dirname(__DIR__, 2) . '/.env.mail',
    dirname(__DIR__) . '/.env.mail',
]);

$gmailUser = $env['GMAIL_USER']         ?? '';
$gmailPass = $env['GMAIL_APP_PASSWORD'] ?? '';
$fromName  = $env['GMAIL_FROM_NAME']    ?? 'Rakele Menjivar';
$fromEmail = $env['GMAIL_FROM_EMAIL']   ?? 'booking@rakelemenjivar.com';
$toEmail   = $env['GMAIL_TO_EMAIL']     ?? 'booking@rakelemenjivar.com';

if ($gmailUser === '' || $gmailPass === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail credentials not configured on server.']);
    exit();
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Gmail SMTP
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $gmailUser;          // e.g. youraccount@gmail.com (the authenticated Gmail)
    $mail->Password   = $gmailPass;          // Gmail App Password (no spaces)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // From = booking@rakelemenjivar.com (must be configured as a Gmail "Send mail as" alias)
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

    $mail->send();

    // ---------- Auto-reply to the form submitter ----------
    try {
        $reply = new PHPMailer(true);
        $reply->isSMTP();
        $reply->Host       = 'smtp.gmail.com';
        $reply->SMTPAuth   = true;
        $reply->Username   = $gmailUser;
        $reply->Password   = $gmailPass;
        $reply->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $reply->Port       = 587;
        $reply->CharSet    = 'UTF-8';

        $reply->setFrom($fromEmail, $fromName);
        $reply->addAddress($email, $name);
        $reply->addCC($fromEmail, $fromName);
        $reply->addReplyTo($fromEmail, $fromName);

        $reply->Subject = 'Thank you for your message';
        $reply->isHTML(false);
        $reply->Body =
            "Hi {$name},\n\n" .
            "Thank you for the message. Rakele will get back to you shortly.\n\n" .
            "Warm regards,\n" .
            "Rakele Menjivar\n" .
            "booking@rakelemenjivar.com";

        $reply->send();
    } catch (Exception $e) {
        error_log('Auto-reply failed: ' . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message. Please try again later.',
        // 'debug' => $mail->ErrorInfo,
    ]);
}
