<?php
header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'error',
    'message' => 'Unable to submit your message. Please try again.'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $response['message'] = 'Method not allowed.';
    echo json_encode($response);
    exit;
}

$name = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars(trim($_POST['message'] ?? ''), ENT_QUOTES, 'UTF-8');

if (!$name || !$email || !$message) {
    http_response_code(422);
    $response['message'] = 'Please fill out all required fields.';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    $response['message'] = 'Please enter a valid email address.';
    echo json_encode($response);
    exit;
}

$timestamp = date('Y-m-d H:i:s');
$logLine = sprintf("[%s] Name: %s | Email: %s | Message: %s\n", $timestamp, $name, $email, $message);
$messagesFile = __DIR__ . '/messages.txt';

if (file_put_contents($messagesFile, $logLine, FILE_APPEND | LOCK_EX) === false) {
    http_response_code(500);
    $response['message'] = 'Could not save message to file.';
    echo json_encode($response);
    exit;
}

$clubEmail = 'club@example.com';
$mailFrom = 'no-reply@yourdomain.com';
$emailSubject = 'New Bit2byte Contact Form Submission';
$emailBody = "You have received a new message from the website contact form:\n\n";
$emailBody .= "Name: {$name}\n";
$emailBody .= "Email: {$email}\n\n";
$emailBody .= "Message:\n{$message}\n";

$headers = "From: Bit2byte <{$mailFrom}>\r\n";
$headers .= "Reply-To: {$email}\r\n";

$mailSent = mail($clubEmail, $emailSubject, $emailBody, $headers);
if (!$mailSent) {
    http_response_code(500);
    $response['message'] = 'Message saved, but email notification failed.';
    echo json_encode($response);
    exit;
}

$response['status'] = 'success';
$response['message'] = 'Message sent successfully!';

echo json_encode($response);
