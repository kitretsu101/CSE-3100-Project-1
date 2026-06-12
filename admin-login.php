<?php
require_once __DIR__ . '/auth.php';

// If already logged in as admin, go to admin dashboard
if (is_logged_in() && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard.php');
    exit;
}
// If logged in as user, go to user dashboard
if (is_logged_in()) {
    header('Location: user/dashboard.php');
    exit;
}

$errors = [];

// Hardcoded admin credentials
define('ADMIN_EMAIL', 'admin@gmail.com');
define('ADMIN_PASSWORD', 'admin123');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email) $errors[] = 'Email is required.';
    if (!$password) $errors[] = 'Password is required.';

    if (empty($errors)) {
        // Secure comparison using hash_equals for timing-attack resistance
        if (hash_equals(ADMIN_EMAIL, $email)) {
            // Verify against the hashed password in the database
            $admin = find_user_by_email(ADMIN_EMAIL);
            if ($admin && password_verify($password, $admin['password'])) {
                login_member($admin['id'], $admin['full_name'], $admin['username'], $admin['email'], 'admin');
                header('Location: admin/dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid admin credentials.';
            }
        } else {
            $errors[] = 'Invalid admin credentials.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bit2byte</title>
    <meta name="description" content="Admin login portal for Bit2byte Coding Club management.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-page auth-page-admin">
        <a href="index.php" class="back-home-btn back-home-btn-admin">
            <i class="fas fa-home"></i> Home
        </a>
        <div class="admin-card">
            <div class="brand-header">
                <div class="logo-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h1>Admin Portal</h1>
                <p>Bit2byte Club Management</p>
                <div class="badge-admin">Authorized Personnel Only</div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert-custom">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="adminLoginForm">
                <div class="form-group-custom">
                    <label for="email"><i class="fas fa-envelope"></i> Admin Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="admin@gmail.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required autofocus>
                </div>

                <div class="form-group-custom">
                    <label for="password"><i class="fas fa-key"></i> Admin Password</label>
                    <div class="input-group-custom">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter admin password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-admin-login" id="submitBtn">
                    <span class="btn-text"><i class="fas fa-unlock-alt"></i> Access Dashboard</span>
                    <span class="spinner"><i class="fas fa-circle-notch fa-spin"></i> Authenticating...</span>
                </button>
            </form>

            <a href="user-login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to User Login
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
        document.getElementById('adminLoginForm')?.addEventListener('submit', function() {
            document.getElementById('submitBtn').classList.add('loading');
        });
    </script>
</body>
</html>
