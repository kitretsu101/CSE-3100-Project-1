<?php
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

echo json_encode([
    'name'  => $_SESSION['user_name'],
    'email' => $_SESSION['user_email'],
    'role'  => $_SESSION['role']
]);