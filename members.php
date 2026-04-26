<?php
require_once __DIR__ . '/auth.php';
require_login();
$memberName = current_member_name();
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members Only - Bit2byte</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo"><span class="logo-text">Bit2byte</span></div>
            <ul class="nav-menu">
                <li><a href="index.html" class="nav-link">Home</a></li>
                <li><a href="logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <section class="membership-section">
        <div class="membership-container">
            <h1 class="membership-title">Members Only</h1>
            <p class="membership-subtitle">Welcome back, <?php echo htmlspecialchars($memberName, ENT_QUOTES); ?>.</p>
            <div class="membership-form-wrapper">
                <p style="font-size:1rem; color: var(--text-light);">
                    This area is reserved for Bit2byte members. Here you can access exclusive content, club resources, and member-only updates.
                </p>
                <div style="margin-top: 2rem; display: grid; gap: 1rem;">
                    <div style="padding:1.5rem; background:#f8f9fa; border-radius:12px; border:1px solid rgba(32,178,170,0.2);">
                        <h3>Exclusive Resources</h3>
                        <p>Download club materials, code templates, and project guides available only to logged-in members.</p>
                    </div>
                    <div style="padding:1.5rem; background:#f8f9fa; border-radius:12px; border:1px solid rgba(32,178,170,0.2);">
                        <h3>Member News</h3>
                        <p>Get the latest announcements on workshops, coding challenges, and community events.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Bit2byte</h4>
                    <p>Where Code Meets Creativity</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2026 Bit2byte Coding Club. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
