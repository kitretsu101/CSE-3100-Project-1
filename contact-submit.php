<?php
/**
 * Contact Form Submission Handler
 * Stores messages in MySQL contact_messages table
 */
require_once __DIR__ . '/db.php';

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

$name    = htmlspecialchars(trim($_POST['name'] ?? ''), ENT_QUOTES, 'UTF-8');
$email   = htmlspecialchars(trim($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8');
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

// Store in database using prepared statement
$stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $name, $email, $message);

if ($stmt->execute()) {
    $response['status'] = 'success';
    $response['message'] = 'Message sent successfully! We\'ll get back to you soon.';
} else {
    http_response_code(500);
    $response['message'] = 'Could not save your message. Please try again later.';
}

$stmt->close();
echo json_encode($response);
