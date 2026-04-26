<?php
require_once 'auth.php';

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

echo json_encode([
    'name' => $_SESSION['member_name'],
    'email' => $_SESSION['member_email']
]);
?>