<?php
require_once __DIR__ . '/auth.php';

$role = $_SESSION['role'] ?? 'user';
logout_member();

// Redirect based on previous role
if ($role === 'admin') {
    header('Location: admin-login.php');
} else {
    header('Location: user-login.php');
}
exit;
