<?php
require_once __DIR__ . '/auth.php';

if (is_logged_in()) {
    header('Location: members.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (!$password) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $user = find_user_by_email($email);
        if (!$user || !password_verify($password, $user['hash'])) {
            $errors[] = 'Invalid email or password.';
        } else {
            login_member($user['name'], $user['email']);
            header('Location: members.php');
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bit2byte</title>
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
                <li><a href="register.php" class="nav-link">Register</a></li>
            </ul>
        </div>
    </nav>

    <section class="membership-section">
        <div class="membership-container">
            <h1 class="membership-title">Login</h1>
            <p class="membership-subtitle">Access members only content</p>
            <div class="membership-form-wrapper">
                <?php if (!empty($errors)): ?>
                    <div class="form-group" style="background:#ffe6e6;border:1px solid #ff8f8f;color:#9d1b1b;">
                        <ul style="list-style:none;padding-left:0;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" class="membership-form">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" class="submit-button">Login</button>
                </form>
                <p style="margin-top:1rem; text-align:center;">Not a member yet? <a href="register.php">Register here</a>.</p>
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
