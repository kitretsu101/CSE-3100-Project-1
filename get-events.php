<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// GET all events - ordered by date, newest first
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT id, title, description, event_date, image FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC";
    
    $result = $conn->query($query);
    $events = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $events[] = [
                'id' => $row['id'],
                'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                'date' => date('M d, Y', strtotime($row['event_date'])),
                'event_date' => $row['event_date'],
                'image' => $row['image'] ? htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8') : null
            ];
        }
    }
    
    echo json_encode($events);
    exit;
}

// POST - Create new event (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in() || !is_admin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['title']) || !isset($data['event_date'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $title = $data['title'];
    $description = $data['description'] ?? null;
    $event_date = $data['event_date'];
    $image = $data['image'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $title, $description, $event_date, $image);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create event']);
    }
    $stmt->close();
    exit;
}

// DELETE event (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!is_logged_in() || !is_admin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event ID']);
        exit;
    }
    
    $id = (int)$data['id'];
    
    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete event']);
    }
    $stmt->close();
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
