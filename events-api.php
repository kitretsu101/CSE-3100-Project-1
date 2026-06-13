<?php
session_start();
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Handle GET request - return all events with RSVP info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userEmail = '';
    if (is_logged_in()) {
        $userEmail = $_SESSION['email'] ?? '';
    }

    $query = "SELECT id, title, description, event_date, image FROM events 
              ORDER BY event_date ASC";
    
    $result = $conn->query($query);
    $events = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Get RSVP count for this event
            $rsvpQuery = "SELECT COUNT(*) as cnt FROM event_attendees WHERE event_id = ?";
            $rsvpStmt = $conn->prepare($rsvpQuery);
            $rsvpStmt->bind_param("i", $row['id']);
            $rsvpStmt->execute();
            $rsvpCount = $rsvpStmt->get_result()->fetch_assoc()['cnt'];
            $rsvpStmt->close();
            
            // Get list of RSVPed users
            $attendeesQuery = "SELECT u.email FROM event_attendees ea 
                              JOIN users u ON ea.user_id = u.id 
                              WHERE ea.event_id = ?";
            $attendeesStmt = $conn->prepare($attendeesQuery);
            $attendeesStmt->bind_param("i", $row['id']);
            $attendeesStmt->execute();
            $rsvps = [];
            $attendeesResult = $attendeesStmt->get_result();
            while ($attendeeRow = $attendeesResult->fetch_assoc()) {
                $rsvps[] = htmlspecialchars($attendeeRow['email'], ENT_QUOTES, 'UTF-8');
            }
            $attendeesStmt->close();
            
            $events[] = [
                'id' => $row['id'],
                'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
                'description' => htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8'),
                'date' => date('M d, Y', strtotime($row['event_date'])),
                'event_date' => $row['event_date'],
                'image' => $row['image'] ? htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8') : 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=300&fit=crop',
                'rsvps' => $rsvps
            ];
        }
    }
    
    echo json_encode($events);
    exit;
}

// Handle POST request - create new event (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_logged_in() || !is_admin()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['title']) || !isset($data['event_date']) || !isset($data['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $title = sanitize_input($data['title']);
    $description = sanitize_input($data['description']);
    $event_date = sanitize_input($data['event_date']);
    $image = isset($data['image']) ? sanitize_input($data['image']) : null;

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

// Handle DELETE request - delete event (admin only)
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

// Handle RSVP request (PATCH)
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['id']) || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $eventId = (int)$data['id'];
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $action = $data['action'];

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['error' => 'User not authenticated']);
        exit;
    }

    if ($action === 'rsvp') {
        // Check if already RSVPed
        $checkStmt = $conn->prepare("SELECT id FROM event_attendees WHERE event_id = ? AND user_id = ?");
        $checkStmt->bind_param("ii", $eventId, $userId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$existing) {
            $insertStmt = $conn->prepare("INSERT INTO event_attendees (event_id, user_id) VALUES (?, ?)");
            $insertStmt->bind_param("ii", $eventId, $userId);
            if ($insertStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'RSVP successful']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to RSVP']);
            }
            $insertStmt->close();
        } else {
            echo json_encode(['success' => true, 'message' => 'Already RSVPed']);
        }
    } elseif ($action === 'cancel') {
        $deleteStmt = $conn->prepare("DELETE FROM event_attendees WHERE event_id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $eventId, $userId);
        if ($deleteStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'RSVP cancelled']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to cancel RSVP']);
        }
        $deleteStmt->close();
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>