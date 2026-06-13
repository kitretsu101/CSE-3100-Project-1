<?php
require_once 'db.php';

header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Debug</title>
    <style>
        body { font-family: monospace; padding: 2rem; background: #f5f5f5; }
        .section { background: white; padding: 1.5rem; margin-bottom: 1rem; border-radius: 8px; border-left: 4px solid #20b2aa; }
        h2 { color: #333; margin-top: 0; }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9f9f9; font-weight: bold; }
        pre { background: #f0f0f0; padding: 0.5rem; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Event System Diagnostic</h1>

    <div class="section">
        <h2>1. Database Connection</h2>
        <?php if ($conn): ?>
            <p class="success">✓ Connected to database</p>
            <p>Database: <?php echo $conn->get_server_info(); ?></p>
        <?php else: ?>
            <p class="error">✗ Connection failed: <?php echo mysqli_connect_error(); ?></p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>2. Events Table</h2>
        <?php
        $result = $conn->query("SELECT COUNT(*) as cnt FROM events");
        if ($result) {
            $row = $result->fetch_assoc();
            $count = $row['cnt'];
            echo "<p>Total events in database: <strong>$count</strong></p>";
            
            if ($count > 0) {
                echo "<h3>Events:</h3>";
                echo "<table>";
                echo "<tr><th>ID</th><th>Title</th><th>Date</th><th>Image</th><th>Created</th></tr>";
                $events = $conn->query("SELECT id, title, event_date, image, created_at FROM events ORDER BY created_at DESC");
                while ($e = $events->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($e['id']) . "</td>";
                    echo "<td>" . htmlspecialchars($e['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($e['event_date']) . "</td>";
                    echo "<td>" . (htmlspecialchars($e['image']) ?: 'NULL') . "</td>";
                    echo "<td>" . htmlspecialchars($e['created_at']) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p class='error'>Error: " . $conn->error . "</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>3. API Response Test</h2>
        <p>Calling events-api.php...</p>
        <?php
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://localhost/CSE-3100-Project-1/events-api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>HTTP Status: <strong>$httpCode</strong></p>";
        if ($response) {
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                echo "<p>Events returned: <strong>" . count($decoded) . "</strong></p>";
                if (count($decoded) > 0) {
                    echo "<h3>First event:</h3>";
                    echo "<pre>" . json_encode($decoded[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
                }
            } else {
                echo "<p class='error'>Invalid JSON response</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } else {
            echo "<p class='error'>Failed to fetch API</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>4. Image Directory Check</h2>
        <?php
        $uploadDir = __DIR__ . '/uploads/events/';
        if (is_dir($uploadDir)) {
            echo "<p class='success'>✓ Directory exists: $uploadDir</p>";
            $files = array_diff(scandir($uploadDir), ['.', '..']);
            echo "<p>Files: " . count($files) . "</p>";
            if (!empty($files)) {
                foreach ($files as $file) {
                    echo "<br>- $file";
                }
            }
        } else {
            echo "<p class='error'>✗ Directory does not exist: $uploadDir</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>5. Quick Links</h2>
        <ul>
            <li><a href="/CSE-3100-Project-1/">Homepage</a></li>
            <li><a href="/CSE-3100-Project-1/manage-events.php">Manage Events</a></li>
            <li><a href="/CSE-3100-Project-1/admin/dashboard.php">Admin Dashboard</a></li>
            <li><a href="/CSE-3100-Project-1/events-api.php">Events API</a></li>
        </ul>
    </div>
</body>
</html>
