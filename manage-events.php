<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_admin();

$adminName = htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8');

// Handle POST request for adding/updating events
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_event') {
        $title = sanitize_input($_POST['event_title'] ?? '');
        $desc  = sanitize_input($_POST['event_desc'] ?? '');
        $date  = sanitize_input($_POST['event_date'] ?? '');
        $image = null;

        // Handle image upload
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileName = $_FILES['event_image']['name'];
            $fileTmp = $_FILES['event_image']['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($fileExt, $allowed) && $_FILES['event_image']['size'] <= 5242880) { // 5MB max
                $uploadDir = __DIR__ . '/uploads/events/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'event_' . time() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    $image = 'uploads/events/' . $newFileName;
                }
            }
        }

        if ($title && $date) {
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, image) VALUES (?, ?, ?, ?)");
            if (!$stmt) {
                header("Location: manage-events.php?msg=error&details=" . urlencode("DB Error: " . $conn->error));
                exit;
            }
            $stmt->bind_param("ssss", $title, $desc, $date, $image);
            if ($stmt->execute()) {
                header("Location: manage-events.php?msg=added");
                exit;
            } else {
                header("Location: manage-events.php?msg=error&details=" . urlencode("Insert Error: " . $stmt->error));
                exit;
            }
            $stmt->close();
        } else {
            header("Location: manage-events.php?msg=error&details=" . urlencode("Missing: Title and Date required"));
            exit;
        }
    }

    if ($action === 'delete_event' && !empty($_POST['event_id'])) {
        $eid = (int)$_POST['event_id'];
        
        // Get image path to delete file
        $imgStmt = $conn->prepare("SELECT image FROM events WHERE id = ?");
        $imgStmt->bind_param("i", $eid);
        $imgStmt->execute();
        $imgResult = $imgStmt->get_result()->fetch_assoc();
        $imgStmt->close();

        if ($imgResult && $imgResult['image']) {
            $imagePath = __DIR__ . '/' . $imgResult['image'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        $stmt->close();
        
        header("Location: manage-events.php?msg=deleted");
        exit;
    }

    if ($action === 'edit_event' && !empty($_POST['event_id'])) {
        $eid = (int)$_POST['event_id'];
        $title = sanitize_input($_POST['event_title'] ?? '');
        $desc  = sanitize_input($_POST['event_desc'] ?? '');
        $date  = sanitize_input($_POST['event_date'] ?? '');
        $image = sanitize_input($_POST['existing_image'] ?? '');

        // Handle image upload for update
        if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $fileName = $_FILES['event_image']['name'];
            $fileTmp = $_FILES['event_image']['tmp_name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (in_array($fileExt, $allowed) && $_FILES['event_image']['size'] <= 5242880) {
                // Delete old image
                if ($image) {
                    $oldPath = __DIR__ . '/' . $image;
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                $uploadDir = __DIR__ . '/uploads/events/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $newFileName = 'event_' . time() . '.' . $fileExt;
                $uploadPath = $uploadDir . $newFileName;

                if (move_uploaded_file($fileTmp, $uploadPath)) {
                    $image = 'uploads/events/' . $newFileName;
                }
            }
        }

        if ($title && $date) {
            $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $title, $desc, $date, $image, $eid);
            if ($stmt->execute()) {
                header("Location: manage-events.php?msg=updated");
                exit;
            }
            $stmt->close();
        }
    }
}

// Fetch all events
$events = [];
$r = $conn->query("SELECT id, title, description, event_date, image, created_at FROM events ORDER BY event_date DESC");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $events[] = $row;
    }
}

// Get message
$msg = '';
$msgType = '';
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $msgType = in_array($msg, ['added', 'updated', 'deleted']) ? 'success' : 'error';
}

// Debug: Check database connection
if (!$conn) {
    $msg = 'Database connection failed';
    $msgType = 'error';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #0f1419; color: #e0e0e0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; }
        .header h1 { color: #00d4ff; font-size: 2rem; }
        .btn-back { padding: 0.75rem 1.5rem; background: #1a1f2e; border: 1px solid #00d4ff; color: #00d4ff; border-radius: 8px; cursor: pointer; text-decoration: none; transition: 0.3s; }
        .btn-back:hover { background: #00d4ff; color: #0f1419; }
        
        .message { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 2rem; }
        .message.success { background: #1a4d3e; border-left: 4px solid #00ff88; color: #00ff88; }
        .message.error { background: #4d1a1a; border-left: 4px solid #ff4444; color: #ff4444; }

        .form-section { background: #1a1f2e; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; border: 1px solid #2a3142; }
        .form-section h2 { color: #00d4ff; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #00d4ff; font-weight: 600; }
        .form-group input,
        .form-group textarea,
        .form-group select { width: 100%; padding: 0.75rem; background: #0f1419; border: 1px solid #2a3142; color: #e0e0e0; border-radius: 6px; font-family: 'Poppins', sans-serif; }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus { outline: none; border-color: #00d4ff; box-shadow: 0 0 8px rgba(0, 212, 255, 0.2); }
        .form-group textarea { resize: vertical; min-height: 120px; }

        .file-input-wrapper { position: relative; }
        .file-input-label { display: inline-block; padding: 0.75rem 1.5rem; background: #00d4ff; color: #0f1419; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .file-input-label:hover { background: #00b8d4; }
        .file-input-wrapper input[type="file"] { display: none; }
        .file-preview { margin-top: 1rem; max-width: 200px; }
        .file-preview img { max-width: 100%; border-radius: 6px; }

        .btn-submit { padding: 0.75rem 2rem; background: #00d4ff; color: #0f1419; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-submit:hover { background: #00b8d4; }

        .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 2rem; }
        .event-card { background: #1a1f2e; border: 1px solid #2a3142; border-radius: 12px; overflow: hidden; transition: 0.3s; }
        .event-card:hover { border-color: #00d4ff; box-shadow: 0 8px 16px rgba(0, 212, 255, 0.1); }
        .event-image { width: 100%; height: 200px; object-fit: cover; background: #0f1419; }
        .event-info { padding: 1.5rem; }
        .event-title { color: #00d4ff; font-size: 1.25rem; margin-bottom: 0.5rem; }
        .event-date { color: #888; font-size: 0.9rem; margin-bottom: 1rem; }
        .event-desc { color: #b0b0b0; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .event-actions { display: flex; gap: 1rem; }
        .btn-edit, .btn-delete { flex: 1; padding: 0.6rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: 0.3s; }
        .btn-edit { background: #2a5a4f; color: #00ff88; }
        .btn-edit:hover { background: #3a7a6f; }
        .btn-delete { background: #5a2a2a; color: #ff4444; }
        .btn-delete:hover { background: #7a3a3a; }

        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.7); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: #1a1f2e; padding: 2rem; border-radius: 12px; max-width: 600px; width: 90%; border: 1px solid #2a3142; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { color: #00d4ff; }
        .modal-close { background: none; border: none; color: #00d4ff; font-size: 1.5rem; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Manage Events</h1>
            <a href="dashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <?php if ($msg): ?>
            <div class="message <?php echo $msgType; ?>">
                <?php 
                if ($msg === 'added') {
                    echo '✓ Event added successfully!';
                } elseif ($msg === 'updated') {
                    echo '✓ Event updated successfully!';
                } elseif ($msg === 'deleted') {
                    echo '✓ Event deleted successfully!';
                } else {
                    echo '❌ Error: ' . (isset($_GET['details']) ? htmlspecialchars($_GET['details'], ENT_QUOTES, 'UTF-8') : 'Operation failed');
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Add/Edit Event Form -->
        <div class="form-section">
            <h2>Add New Event</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_event">
                
                <div class="form-group">
                    <label for="event_title">Event Title</label>
                    <input type="text" id="event_title" name="event_title" required placeholder="e.g., Annual Hackathon">
                </div>

                <div class="form-group">
                    <label for="event_date">Event Date</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>

                <div class="form-group">
                    <label for="event_desc">Description</label>
                    <textarea id="event_desc" name="event_desc" placeholder="Event description..."></textarea>
                </div>

                <div class="form-group">
                    <label>Event Image</label>
                    <div class="file-input-wrapper">
                        <label class="file-input-label">Choose Image</label>
                        <input type="file" id="event_image" name="event_image" accept="image/*">
                    </div>
                    <div class="file-preview" id="previewContainer"></div>
                </div>

                <button type="submit" class="btn-submit">+ Publish Event</button>
            </form>
        </div>

        <!-- Events List -->
        <div class="form-section">
            <h2>All Events (<?php echo count($events); ?>)</h2>
            <?php if (empty($events)): ?>
                <p style="color: #888; text-align: center; padding: 2rem;">No events published yet. Create your first event above!</p>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($events as $event): ?>
                        <div class="event-card">
                            <?php if ($event['image']): ?>
                                <img src="<?php echo htmlspecialchars($event['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?>" class="event-image">
                            <?php else: ?>
                                <div class="event-image" style="display: flex; align-items: center; justify-content: center; background: #2a3142; color: #666; font-size: 3rem;">📅</div>
                            <?php endif; ?>
                            <div class="event-info">
                                <div class="event-title"><?php echo htmlspecialchars($event['title'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="event-date"><?php echo date('M d, Y', strtotime($event['event_date'])); ?></div>
                                <div class="event-desc"><?php echo htmlspecialchars($event['description'] ?? 'No description', ENT_QUOTES, 'UTF-8'); ?></div>
                                <div class="event-actions">
                                    <button class="btn-edit" onclick="editEvent(<?php echo $event['id']; ?>)">Edit</button>
                                    <form method="POST" style="flex: 1;">
                                        <input type="hidden" name="action" value="delete_event">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Delete this event?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Image preview
        document.getElementById('event_image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('previewContainer');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    preview.innerHTML = '<img src="' + event.target.result + '" alt="Preview">';
                };
                reader.readAsDataURL(file);
            }
        });

        function editEvent(eventId) {
            alert('Edit functionality coming soon! For now, delete and recreate the event.');
        }
    </script>
</body>
</html>
