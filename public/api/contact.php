<?php
/**
 * Contact Form Email Handler
 * Receives POST data and sends email to booking@rakelemenjivar.com
 */

// CORS headers for the React frontend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Honeypot check - if filled, it's likely a bot
$honeypot = isset($data['website']) ? trim($data['website']) : '';
if (!empty($honeypot)) {
    // Silently reject but return success to not tip off bots
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    exit();
}

// Validate required fields
$name = isset($data['name']) ? trim($data['name']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$company = isset($data['company']) ? trim($data['company']) : '';
$message = isset($data['message']) ? trim($data['message']) : '';

// Validation
$errors = [];

if (empty($name)) {
    $errors[] = 'Name is required';
} elseif (strlen($name) > 100) {
    $errors[] = 'Name must be less than 100 characters';
}

if (empty($email)) {
    $errors[] = 'Email is required';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
} elseif (strlen($email) > 255) {
    $errors[] = 'Email must be less than 255 characters';
}

if (strlen($company) > 200) {
    $errors[] = 'Company name must be less than 200 characters';
}

if (empty($message)) {
    $errors[] = 'Message is required';
} elseif (strlen($message) > 5000) {
    $errors[] = 'Message must be less than 5000 characters';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit();
}

// Sanitize inputs for email
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$company = htmlspecialchars($company, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Email configuration
$to = 'booking@rakelemenjivar.com';
$subject = "New Inquiry from {$name}" . ($company ? " - {$company}" : "");

// Build email body
$emailBody = "
New contact form submission from rakelemenjivar.com

----------------------------------------
FROM: {$name}
EMAIL: {$email}
COMPANY: " . ($company ?: 'Not provided') . "
----------------------------------------

MESSAGE:
{$message}

----------------------------------------
Sent from the website contact form.
";

// Email headers
$headers = [
    'From' => "noreply@rakelemenjivar.com",
    'Reply-To' => $email,
    'X-Mailer' => 'PHP/' . phpversion(),
    'Content-Type' => 'text/plain; charset=UTF-8'
];

$headerString = '';
foreach ($headers as $key => $value) {
    $headerString .= "{$key}: {$value}\r\n";
}

// Send email
$mailSent = mail($to, $subject, $emailBody, $headerString);

if ($mailSent) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again later.']);
}
