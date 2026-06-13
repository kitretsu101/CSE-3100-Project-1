<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Members - Bit2byte</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <style>
        .members-page {
            min-height: 100vh;
            background: #0f1419;
            padding: 2rem 1rem;
        }

        .members-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .members-header {
            text-align: center;
            margin-bottom: 4rem;
            padding-top: 2rem;
        }

        .members-header h1 {
            font-size: 2.5rem;
            color: #00d4ff;
            margin-bottom: 1rem;
        }

        .members-header p {
            font-size: 1.1rem;
            color: #b0b0b0;
        }

        .members-stats {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }

        .stat-box {
            padding: 1rem 2rem;
            background: #1a1f2e;
            border: 1px solid #2a3142;
            border-radius: 12px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 2rem;
            color: #00d4ff;
            font-weight: 700;
        }

        .stat-box .label {
            color: #888;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .members-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 0.75rem 1.5rem;
            background: #1a1f2e;
            border: 1px solid #2a3142;
            color: #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #00d4ff;
            color: #0f1419;
            border-color: #00d4ff;
        }

        .search-box {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            justify-content: center;
        }

        .search-box input {
            padding: 0.75rem 1.5rem;
            background: #1a1f2e;
            border: 1px solid #2a3142;
            color: #e0e0e0;
            border-radius: 6px;
            width: 300px;
            max-width: 100%;
            font-family: 'Poppins', sans-serif;
        }

        .search-box input:focus {
            outline: none;
            border-color: #00d4ff;
            box-shadow: 0 0 8px rgba(0, 212, 255, 0.2);
        }

        .members-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .member-card {
            background: #1a1f2e;
            border: 1px solid #2a3142;
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            text-align: center;
        }

        .member-card:hover {
            border-color: #00d4ff;
            box-shadow: 0 8px 16px rgba(0, 212, 255, 0.1);
            transform: translateY(-5px);
        }

        .member-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #00d4ff, #0084b4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
        }

        .member-info {
            padding: 1.5rem;
        }

        .member-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #00d4ff;
            margin-bottom: 0.5rem;
        }

        .member-role {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .member-email {
            color: #b0b0b0;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            word-break: break-all;
        }

        .member-department {
            display: inline-block;
            padding: 0.4rem 0.8rem;
            background: rgba(0, 212, 255, 0.1);
            color: #00d4ff;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        .members-empty {
            text-align: center;
            padding: 2rem;
            color: #888;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 2rem;
            color: #00d4ff;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #00b8d4;
        }

        .loading {
            text-align: center;
            padding: 2rem;
            color: #888;
        }

        @media (max-width: 768px) {
            .members-header h1 {
                font-size: 1.8rem;
            }

            .members-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 1.5rem;
            }

            .search-box {
                flex-direction: column;
            }

            .search-box input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!--  NAVBAR  -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <span class="logo-text">Bit2byte</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.html#home" class="nav-link">Home</a></li>
                <li><a href="index.html#about" class="nav-link">About</a></li>
                <li><a href="index.html#stats" class="nav-link">Stats</a></li>
                <li><a href="index.html#events" class="nav-link">Events</a></li>
                <li><a href="all-members.php" class="nav-link">Members</a></li>
                <li><a href="index.html#contact" class="nav-link">Contact</a></li>
                <li id="adminLink" style="display: none;"><a href="admin.php" class="nav-link">Admin</a></li>
                <li id="loginLink"><a href="login.php" class="nav-link">Login</a></li>
                <li id="registerLink"><a href="register.php" class="nav-link">Register</a></li>
                <li id="logoutLink" style="display: none;"><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Members Page -->
    <div class="members-page">
        <div class="members-container">
            <a href="index.html#team" class="back-link">← Back to Homepage</a>

            <div class="members-header">
                <h1>👥 Club Members</h1>
                <p>Meet all the amazing members of Bit2byte</p>
            </div>

            <div class="members-stats">
                <div class="stat-box">
                    <div class="number" id="totalMembers">0</div>
                    <div class="label">Total Members</div>
                </div>
                <div class="stat-box">
                    <div class="number" id="totalDepartments">0</div>
                    <div class="label">Departments</div>
                </div>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search members by name, email...">
            </div>

            <div class="members-grid" id="membersGrid">
                <div class="loading">Loading members...</div>
            </div>
        </div>
    </div>

    <script>
        // Fetch and display all members
        async function loadMembers() {
            try {
                const response = await fetch('get-all-members.php');
                const data = await response.json();

                if (data.success) {
                    const members = data.members;
                    displayMembers(members);
                    updateStats(members);
                } else {
                    showError('Failed to load members');
                }
            } catch (error) {
                console.error('Error loading members:', error);
                showError('Error loading members');
            }
        }

        function displayMembers(members) {
            const grid = document.getElementById('membersGrid');
            grid.innerHTML = '';

            if (members.length === 0) {
                grid.innerHTML = '<div class="members-empty" style="grid-column: 1 / -1;">No members found</div>';
                return;
            }

            members.forEach(member => {
                const card = document.createElement('div');
                card.className = 'member-card';
                card.innerHTML = `
                    <div class="member-image">👤</div>
                    <div class="member-info">
                        <div class="member-name">${escapeHtml(member.full_name)}</div>
                        <div class="member-role">${member.student_id ? 'Student' : 'Member'}</div>
                        ${member.department ? `<div class="member-department">${escapeHtml(member.department)}</div>` : ''}
                        <div class="member-email">${escapeHtml(member.email)}</div>
                        ${member.phone ? `<div style="color: #888; font-size: 0.85rem;">📱 ${escapeHtml(member.phone)}</div>` : ''}
                    </div>
                `;
                grid.appendChild(card);
            });
        }

        function updateStats(members) {
            document.getElementById('totalMembers').textContent = members.length;
            
            // Count unique departments
            const departments = new Set(members.map(m => m.department).filter(Boolean));
            document.getElementById('totalDepartments').textContent = departments.size;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showError(message) {
            const grid = document.getElementById('membersGrid');
            grid.innerHTML = `<div class="members-empty" style="grid-column: 1 / -1; color: #ff6b6b;">${message}</div>`;
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', async function(e) {
            const query = e.target.value.toLowerCase();
            const response = await fetch('get-all-members.php');
            const data = await response.json();

            if (data.success) {
                const filtered = data.members.filter(member => 
                    member.full_name.toLowerCase().includes(query) ||
                    member.email.toLowerCase().includes(query) ||
                    (member.department && member.department.toLowerCase().includes(query))
                );
                displayMembers(filtered);
            }
        });

        // Load members on page load
        document.addEventListener('DOMContentLoaded', loadMembers);

        // Update navbar based on login status
        async function checkLoginStatus() {
            try {
                const response = await fetch('get-user-info.php');
                if (response.ok) {
                    const user = await response.json();
                    document.getElementById('loginLink').style.display = 'none';
                    document.getElementById('registerLink').style.display = 'none';
                    document.getElementById('logoutLink').style.display = 'block';
                    
                    if (user.role === 'admin') {
                        document.getElementById('adminLink').style.display = 'block';
                    }
                } else {
                    document.getElementById('loginLink').style.display = 'block';
                    document.getElementById('registerLink').style.display = 'block';
                    document.getElementById('logoutLink').style.display = 'none';
                    document.getElementById('adminLink').style.display = 'none';
                }
            } catch (error) {
                console.error('Error checking login status:', error);
            }
        }

        checkLoginStatus();
    </script>
</body>
</html>
