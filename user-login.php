<?php
require_once __DIR__ . '/auth.php';

// If already logged in, redirect based on role
if (is_logged_in()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

$errors = [];
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $loginValue = htmlspecialchars($login, ENT_QUOTES, 'UTF-8');

    if (!$login) {
        $errors[] = 'Email or Username is required.';
    }
    if (!$password) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $user = find_user_by_login($login);
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid credentials. Please check your email/username and password.';
        } elseif ($user['role'] === 'admin') {
            $errors[] = 'Admin accounts must use the admin login page.';
        } else {
            login_member($user['id'], $user['full_name'], $user['username'], $user['email'], $user['role']);
            header('Location: user/dashboard.php');
            exit;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login - Bit2byte Coding Club</title>
    <meta name="description" content="Log in to your Bit2byte account to access your dashboard and events.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-page">
        <a href="index.php" class="back-home-btn">
            <i class="fas fa-home"></i> Home
        </a>
        <div class="auth-card">
            <div class="brand-header">
                <div class="logo-icon">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Log in to access your Bit2byte dashboard</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert-custom alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group-custom">
                    <label for="login"><i class="fas fa-user"></i> Email or Username</label>
                    <input type="text" class="form-control" id="login" name="login" placeholder="Enter your email or username" value="<?php echo $loginValue; ?>" required autofocus>
                </div>

                <div class="form-group-custom">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="input-group-custom">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-extras">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <a href="#">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login" id="submitBtn">
                    <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Login</span>
                    <span class="spinner"><i class="fas fa-circle-notch fa-spin"></i> Logging in...</span>
                </button>
            </form>

            <div class="form-footer-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>

            <div class="divider"><span>or</span></div>

            <a href="admin-login.php" class="admin-link-btn">
                <i class="fas fa-shield-alt"></i> Admin Login
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const field = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        document.getElementById('loginForm')?.addEventListener('submit', function() {
            document.getElementById('submitBtn').classList.add('loading');
        });
    </script>
</body>
</html>
