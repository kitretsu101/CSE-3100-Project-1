<?php
/**
 * Pricing Package Inquiry Submission Handler
 * Stores package inquiries in the MySQL database and triggers club email notifications.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'error',
    'message' => 'Unable to submit your package inquiry. Please try again.'
];

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed.';
    echo json_encode($response);
    exit;
}

// Extract and sanitize inputs
$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$package = htmlspecialchars(trim($_POST['package'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

// Basic validations
if (!$name || !$email || !$package || !$message) {
    http_response_code(422);
    $response['message'] = 'Please fill out all required fields.';
    echo json_encode($response);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

// Validate package choices
$allowed_packages = ['Entry', 'Standard', 'Enterprise'];
if (!in_array($package, $allowed_packages)) {
    http_response_code(422);
    $response['message'] = 'Invalid package selected.';
    echo json_encode($response);
    exit;
}

// Format database message content
$full_message = "Package Selected: [ " . $package . " ]\n\n";
$full_message .= "Message:\n" . $message;

// ─── DB Storage (Data-driven Feature Integration) ───────────────────
$stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $full_message);
$db_saved = $stmt->execute();
$stmt->close();

if (!$db_saved) {
    http_response_code(500);
    $response['message'] = 'Could not save your inquiry to our database. Please try again later.';
    echo json_encode($response);
    exit;
}

// ─── Email Dispatch (SMTP Setup Suppressed for XAMPP Local Dev) ─────
$to      = 'info@bit2byte.com';
$subject = "Bit2Byte Project Inquiry - Package: " . $package . " (from " . $name . ")";

// HTML Email Template
$email_content = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eeeeee; border-radius: 8px; }
        .header { background-color: #20b2aa; color: #ffffff; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
        .header h2 { margin: 0; }
        .detail-row { padding: 10px 0; border-bottom: 1px solid #f2f2f2; }
        .label { font-weight: bold; color: #555555; }
        .content { margin-top: 15px; padding: 15px; background-color: #f9f9f9; border-radius: 4px; }
        .footer { margin-top: 20px; font-size: 0.8em; color: #777777; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>New Package Inquiry</h2>
        </div>
        <div class='detail-row'>
            <span class='label'>Client Name:</span> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "
        </div>
        <div class='detail-row'>
            <span class='label'>Client Email:</span> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "
        </div>
        <div class='detail-row'>
            <span class='label'>Selected Package:</span> <span style='background-color:#e0f2f1; color:#00796b; padding:2px 8px; border-radius:12px; font-weight:bold; font-size:0.9em;'>" . htmlspecialchars($package, ENT_QUOTES, 'UTF-8') . "</span>
        </div>
        <div class='content'>
            <span class='label'>Project Requirements:</span><br/>
            " . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "
        </div>
        <div class='footer'>
            This inquiry was sent automatically from the Bit2Byte Pricing Page portal.
        </div>
    </div>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
$headers .= "From: Bit2Byte Web Portal <no-reply@bit2byte.com>" . "\r\n";
$headers .= "Reply-To: " . $email . "\r\n";

// Suppress errors using @ to handle cases where SMTP is not configured in local environment
$mail_sent = @mail($to, $subject, $email_content, $headers);

// Respond successfully (since database storage succeeded)
$response['status'] = 'success';
$response['message'] = 'Thank you for your inquiry! Our Bit2Byte development team will contact you shortly.';

echo json_encode($response);
exit;
