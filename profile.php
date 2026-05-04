<?php
require_once __DIR__ . '/auth.php';

// Only logged-in users can view their profile
require_login();

// If an admin somehow lands here, redirect to admin panel
if ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
    exit;
}

$userName  = htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($_SESSION['user_email'], ENT_QUOTES, 'UTF-8');
$userRole  = htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Bit2byte</title>
    <meta name="description" content="Your Bit2byte member profile — view your account information.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
</head>
<body>
    <?php echo render_navbar(); ?>

    <section class="membership-section">
        <div class="membership-container">
            <h1 class="membership-title">My Profile</h1>
            <p class="membership-subtitle">Welcome back, <?php echo $userName; ?>!</p>
            <div class="membership-form-wrapper">
                <div class="profile-card">
                    <div class="profile-avatar" id="profileAvatar">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-details">
                        <div class="profile-row">
                            <span class="profile-label">Full Name</span>
                            <span class="profile-value"><?php echo $userName; ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">Email</span>
                            <span class="profile-value"><?php echo $userEmail; ?></span>
                        </div>
                        <div class="profile-row">
                            <span class="profile-label">Role</span>
                            <span class="profile-value role-badge role-<?php echo $userRole; ?>"><?php echo ucfirst($userRole); ?></span>
                        </div>
                    </div>
                </div>
                <div class="profile-actions">
                    <a href="index.php" class="submit-button" style="text-decoration:none;text-align:center;display:block;">Back to Home</a>
                    <a href="logout.php" class="cancel-link">Logout</a>
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

    <script>
        const navToggle = document.getElementById('navToggle');
        const navMenu = document.getElementById('navMenu');
        if (navToggle) {
            navToggle.addEventListener('click', () => {
                navMenu.classList.toggle('active');
                navToggle.classList.toggle('active');
            });
        }
    </script>
</body>
</html>
