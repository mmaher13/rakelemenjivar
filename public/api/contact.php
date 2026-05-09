<?php
/**
 * Contact Form Email Handler (PHPMailer + SMTP)
 *
 * Server requirements:
 *   1. PHP 7.4+ with openssl extension
 *   2. Composer installed:  sudo apt install composer
 *   3. From this /api directory on the server, run:
 *        composer require phpmailer/phpmailer
 *      (this creates /api/vendor/ and /api/composer.json)
 *   4. Copy smtp-config.example.php to smtp-config.php and fill in real SMTP credentials.
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

// ---------- Load PHPMailer + config ----------
$autoload   = __DIR__ . '/vendor/autoload.php';
$configPath = __DIR__ . '/smtp-config.php';

if (!file_exists($autoload)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Mail library not installed on server.']);
    exit();
}
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SMTP configuration missing on server.']);
    exit();
}

require $autoload;
$config = require $configPath;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // SMTP transport
    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->SMTPSecure = ($config['encryption'] === 'ssl')
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = (int) $config['port'];
    $mail->CharSet    = 'UTF-8';

    // From / To / Reply-To
    $mail->setFrom($config['from_email'], $config['from_name']);
    $mail->addAddress($config['to_email'], $config['to_name']);
    $mail->addReplyTo($email, $name);

    // Subject + body
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
        $reply->Host       = $config['host'];
        $reply->SMTPAuth   = true;
        $reply->Username   = $config['username'];
        $reply->Password   = $config['password'];
        $reply->SMTPSecure = ($config['encryption'] === 'ssl')
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;
        $reply->Port       = (int) $config['port'];
        $reply->CharSet    = 'UTF-8';

        // From booking@, To submitter, CC booking@
        $reply->setFrom('booking@rakelemenjivar.com', 'Rakele Menjivar');
        $reply->addAddress($email, $name);
        $reply->addCC('booking@rakelemenjivar.com', 'Rakele Menjivar');
        $reply->addReplyTo('booking@rakelemenjivar.com', 'Rakele Menjivar');

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
        // Auto-reply failure should not break the main submission flow.
        error_log('Auto-reply failed: ' . $e->getMessage());
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send message. Please try again later.',
        // Uncomment for debugging on the server:
        // 'debug' => $mail->ErrorInfo,
    ]);
}
