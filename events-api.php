<?php
session_start();
require_once 'auth.php';

const EVENTS_FILE = __DIR__ . '/events.json';

function load_events(): array {
    if (!file_exists(EVENTS_FILE)) {
        return [];
    }
    $content = file_get_contents(EVENTS_FILE);
    return json_decode($content, true) ?? [];
}

function save_events(array $events): bool {
    $json = json_encode($events, JSON_PRETTY_PRINT);
    return file_put_contents(EVENTS_FILE, $json, LOCK_EX) !== false;
}

function generate_event_id(): int {
    $events = load_events();
    $maxId = 0;
    foreach ($events as $event) {
        if ($event['id'] > $maxId) {
            $maxId = $event['id'];
        }
    }
    return $maxId + 1;
}

// Handle GET request - return all events
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(load_events());
    exit;
}

// Handle POST request - create new event (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if admin (for now, any logged in user can be admin)
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['title']) || !isset($data['date']) || !isset($data['description'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $events = load_events();
    $newEvent = [
        'id' => generate_event_id(),
        'title' => sanitize_input($data['title']),
        'date' => sanitize_input($data['date']),
        'description' => sanitize_input($data['description']),
        'image' => sanitize_input($data['image'] ?? 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=400&h=300&fit=crop'),
        'rsvps' => []
    ];

    $events[] = $newEvent;

    if (save_events($events)) {
        header('Content-Type: application/json');
        echo json_encode($newEvent);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save event']);
    }
    exit;
}

// Handle PUT request - update event (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event ID']);
        exit;
    }

    $events = load_events();
    $found = false;

    foreach ($events as &$event) {
        if ($event['id'] == $data['id']) {
            if (isset($data['title'])) $event['title'] = sanitize_input($data['title']);
            if (isset($data['date'])) $event['date'] = sanitize_input($data['date']);
            if (isset($data['description'])) $event['description'] = sanitize_input($data['description']);
            if (isset($data['image'])) $event['image'] = sanitize_input($data['image']);
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    if (save_events($events)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update event']);
    }
    exit;
}

// Handle DELETE request - delete event (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing event ID']);
        exit;
    }

    $events = load_events();
    $filtered = array_filter($events, function($event) use ($data) {
        return $event['id'] != $data['id'];
    });

    if (count($filtered) === count($events)) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    if (save_events(array_values($filtered))) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete event']);
    }
    exit;
}

// Handle RSVP request
if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['id']) || !isset($data['action'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }

    $events = load_events();
    $found = false;
    $memberEmail = $_SESSION['member_email'];

    foreach ($events as &$event) {
        if ($event['id'] == $data['id']) {
            if ($data['action'] === 'rsvp') {
                if (!in_array($memberEmail, $event['rsvps'])) {
                    $event['rsvps'][] = $memberEmail;
                }
            } elseif ($data['action'] === 'cancel') {
                $event['rsvps'] = array_filter($event['rsvps'], function($email) use ($memberEmail) {
                    return $email !== $memberEmail;
                });
                $event['rsvps'] = array_values($event['rsvps']);
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['error' => 'Event not found']);
        exit;
    }

    if (save_events($events)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update RSVP']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>