<?php
require_once __DIR__ . '/auth.php';

// *** CRITICAL SECURITY FIX ***
// Only admins can access this page — normal users are redirected to home
require_admin();

$adminName = htmlspecialchars(current_member_name(), ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bit2byte</title>
    <meta name="description" content="Bit2byte admin dashboard for managing events and users.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        .admin-wrapper {
            min-height: calc(100vh - 80px);
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            padding: 2rem;
            color: #fff;
        }

        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .admin-header h1 {
            font-size: 2.2rem;
            background: linear-gradient(135deg, #00d4ff, #7b2ff7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .admin-header p {
            color: #a0aec0;
            margin-top: 0.5rem;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .admin-stat-card {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .admin-stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 212, 255, 0.15);
        }

        .admin-stat-card .stat-num {
            font-size: 2.5rem;
            font-weight: 800;
            font-family: 'JetBrains Mono', monospace;
            color: #00d4ff;
        }

        .admin-stat-card .stat-lbl {
            font-size: 0.9rem;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.3rem;
        }

        .admin-nav {
            background: rgba(255, 255, 255, 0.06);
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-nav a {
            color: #00d4ff;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
            font-weight: 600;
        }

        .admin-nav a:hover {
            background: rgba(0, 212, 255, 0.1);
        }

        .event-form {
            background: rgba(255, 255, 255, 0.05);
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 212, 255, 0.2);
        }

        .event-form h2 {
            color: #00d4ff;
            margin-bottom: 1.5rem;
        }

        .event-form .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #00d4ff;
            font-weight: 600;
        }

        .event-form .form-group input,
        .event-form .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(0, 212, 255, 0.3);
            border-radius: 5px;
            background: rgba(0, 0, 0, 0.3);
            color: white;
            font-family: 'Poppins', sans-serif;
        }

        .event-form .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00d4ff, #0099cc);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 212, 255, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #00d4ff;
            border: 1px solid rgba(0, 212, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(0, 212, 255, 0.1);
        }

        .events-list {
            display: grid;
            gap: 1rem;
        }

        .event-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(0, 212, 255, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .event-info h3 {
            margin: 0 0 0.5rem 0;
            color: #00d4ff;
        }

        .event-info p {
            margin: 0.25rem 0;
            color: #ccc;
        }

        .event-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-small {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: rgba(20, 20, 30, 0.95);
            padding: 2rem;
            border-radius: 15px;
            border: 1px solid rgba(0, 212, 255, 0.3);
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-header h2 {
            margin: 0;
            color: #00d4ff;
        }

        .close-btn {
            background: none;
            border: none;
            color: #ccc;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .rsvp-count {
            background: rgba(0, 212, 255, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            color: #00d4ff;
        }

        /* Toast styles */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 2000;
            animation: slideInUp 0.3s ease;
        }
        .toast-success { background: #00c853; }
        .toast-error { background: #ff5252; }

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 0.5rem;
            }
            .event-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <div class="admin-container">
            <div class="admin-header">
                <h1>🛡️ Admin Dashboard</h1>
                <p>Welcome, <?php echo $adminName; ?>! You are logged in as <strong>Admin</strong>.</p>
            </div>

            <div class="admin-nav">
                <a href="index.php">← Back to Website</a>
                <a href="logout.php">Logout</a>
            </div>

            <div class="admin-stats" id="adminStats">
                <!-- Stats loaded via JS -->
            </div>

            <div class="event-form">
                <h2>Add New Event</h2>
                <form id="addEventForm">
                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Event Date</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="image">Image URL (optional)</label>
                        <input type="url" id="image" name="image" placeholder="https://...">
                    </div>
                    <button type="submit" class="btn btn-primary">Add Event</button>
                </form>
            </div>

            <div class="events-list" id="eventsList">
                <!-- Events will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal" id="editModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Event</h2>
                <button class="close-btn" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editEventForm">
                <input type="hidden" id="editId" name="id">
                <div class="form-group">
                    <label for="editTitle">Event Title</label>
                    <input type="text" id="editTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="editDate">Event Date</label>
                    <input type="date" id="editDate" name="date" required>
                </div>
                <div class="form-group">
                    <label for="editDescription">Description</label>
                    <textarea id="editDescription" name="description" required></textarea>
                </div>
                <div class="form-group">
                    <label for="editImage">Image URL</label>
                    <input type="url" id="editImage" name="image">
                </div>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Update Event</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let events = [];

        // Load events
        async function loadEvents() {
            try {
                const response = await fetch('events-api.php');
                events = await response.json();
                displayEvents();
                updateStats();
            } catch (error) {
                console.error('Error loading events:', error);
                showToast('Error loading events', 'error');
            }
        }

        // Update admin stats
        function updateStats() {
            const statsContainer = document.getElementById('adminStats');
            const totalEvents = events.length;
            const totalRSVPs = events.reduce((sum, e) => sum + (e.rsvps ? e.rsvps.length : 0), 0);

            statsContainer.innerHTML = `
                <div class="admin-stat-card">
                    <div class="stat-num">${totalEvents}</div>
                    <div class="stat-lbl">Total Events</div>
                </div>
                <div class="admin-stat-card">
                    <div class="stat-num">${totalRSVPs}</div>
                    <div class="stat-lbl">Total RSVPs</div>
                </div>
            `;
        }

        // Display events
        function displayEvents() {
            const eventsList = document.getElementById('eventsList');
            eventsList.innerHTML = '';

            if (events.length === 0) {
                eventsList.innerHTML = '<p style="text-align:center;color:#a0aec0;padding:2rem;">No events yet. Add your first event above!</p>';
                return;
            }

            events.forEach(event => {
                const eventItem = document.createElement('div');
                eventItem.className = 'event-item';
                eventItem.innerHTML = `
                    <div class="event-info">
                        <h3>${event.title}</h3>
                        <p><strong>Date:</strong> ${event.date}</p>
                        <p>${event.description.substring(0, 100)}${event.description.length > 100 ? '...' : ''}</p>
                        <span class="rsvp-count">${event.rsvps ? event.rsvps.length : 0} RSVPs</span>
                    </div>
                    <div class="event-actions">
                        <button class="btn btn-secondary btn-small" onclick="editEvent(${event.id})">Edit</button>
                        <button class="btn btn-secondary btn-small" onclick="deleteEvent(${event.id})" style="background: rgba(255, 0, 0, 0.1); color: #ff6b6b;">Delete</button>
                    </div>
                `;
                eventsList.appendChild(eventItem);
            });
        }

        // Add event
        document.getElementById('addEventForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const eventData = {
                title: formData.get('title'),
                date: formData.get('date'),
                description: formData.get('description'),
                image: formData.get('image') || ''
            };

            try {
                const response = await fetch('events-api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(eventData)
                });

                if (response.ok) {
                    showToast('Event added successfully!', 'success');
                    e.target.reset();
                    loadEvents();
                } else {
                    const error = await response.json();
                    showToast(error.error || 'Error adding event', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error adding event', 'error');
            }
        });

        // Edit event
        function editEvent(id) {
            const event = events.find(e => e.id === id);
            if (!event) return;

            document.getElementById('editId').value = event.id;
            document.getElementById('editTitle').value = event.title;
            document.getElementById('editDate').value = event.date;
            document.getElementById('editDescription').value = event.description;
            document.getElementById('editImage').value = event.image;

            document.getElementById('editModal').classList.add('show');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('show');
        }

        // Update event
        document.getElementById('editEventForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);
            const eventData = {
                id: parseInt(formData.get('id')),
                title: formData.get('title'),
                date: formData.get('date'),
                description: formData.get('description'),
                image: formData.get('image')
            };

            try {
                const response = await fetch('events-api.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(eventData)
                });

                if (response.ok) {
                    showToast('Event updated successfully!', 'success');
                    closeEditModal();
                    loadEvents();
                } else {
                    const error = await response.json();
                    showToast(error.error || 'Error updating event', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error updating event', 'error');
            }
        });

        // Delete event
        async function deleteEvent(id) {
            if (!confirm('Are you sure you want to delete this event?')) return;

            try {
                const response = await fetch('events-api.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                if (response.ok) {
                    showToast('Event deleted successfully!', 'success');
                    loadEvents();
                } else {
                    const error = await response.json();
                    showToast(error.error || 'Error deleting event', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error deleting event', 'error');
            }
        }

        // Toast notification
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => { toast.remove(); }, 3000);
        }

        // Load events on page load
        loadEvents();
    </script>
</body>
</html>