<?php
/**
 * AJAX endpoint for real-time username/email availability checking
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$type  = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');

if (!$value || !in_array($type, ['username', 'email'])) {
    echo json_encode(['available' => false, 'error' => 'Invalid request']);
    exit;
}

if ($type === 'username') {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
} else {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
}

$stmt->bind_param("s", $value);
$stmt->execute();
$stmt->store_result();

echo json_encode(['available' => $stmt->num_rows === 0]);
$stmt->close();
