<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

// GET all members
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT id, full_name, username, email, phone, department, student_id, role, created_at 
              FROM users 
              WHERE role != 'admin' 
              ORDER BY created_at DESC";
    
    $result = $conn->query($query);
    $members = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $members[] = [
                'id' => $row['id'],
                'full_name' => htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'),
                'username' => htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'),
                'email' => htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'),
                'phone' => $row['phone'] ? htmlspecialchars($row['phone'], ENT_QUOTES, 'UTF-8') : null,
                'department' => $row['department'] ? htmlspecialchars($row['department'], ENT_QUOTES, 'UTF-8') : null,
                'student_id' => $row['student_id'] ? htmlspecialchars($row['student_id'], ENT_QUOTES, 'UTF-8') : null,
                'role' => $row['role'],
                'joined_date' => date('M d, Y', strtotime($row['created_at']))
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($members),
        'members' => $members
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Method not allowed']);
