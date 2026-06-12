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
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = sanitize_input($_POST['full_name'] ?? '');
    $username   = sanitize_input($_POST['username'] ?? '');
    $email      = sanitize_input($_POST['email'] ?? '');
    $phone      = sanitize_input($_POST['phone'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $student_id = sanitize_input($_POST['student_id'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $confirm    = trim($_POST['confirm_password'] ?? '');

    // Validation
    if (!$full_name) $errors[] = 'Full Name is required.';
    if (!$username) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = 'Username can only contain letters, numbers, and underscores.';
    }
    if (!$email) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!$phone) $errors[] = 'Phone Number is required.';
    if (!$department) $errors[] = 'Department is required.';
    if (!$student_id) $errors[] = 'Student ID is required.';
    if (!$password) {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors) && email_exists($email)) {
        $errors[] = 'An account with that email already exists.';
    }
    if (empty($errors) && username_exists($username)) {
        $errors[] = 'That username is already taken.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        if (save_user($full_name, $username, $email, $phone, $department, $student_id, $passwordHash)) {
            $success = 'Registration successful! Redirecting to login...';
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bit2byte Coding Club</title>
    <meta name="description" content="Create your Bit2byte account to access exclusive coding club content and events.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="animations.css">
    <link rel="stylesheet" href="auth.css">
</head>
<body>
    <div class="auth-page">
        <a href="index.php" class="back-home-btn">
            <i class="fas fa-home"></i> Home
        </a>
        <div class="auth-card auth-card-wide">
            <div class="brand-header">
                <div class="logo-icon">
                    <i class="fas fa-code"></i>
                </div>
                <h1>Join Bit2byte</h1>
                <p>Create your account and become part of our coding community</p>
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

            <?php if ($success): ?>
                <div class="alert-custom alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <script>setTimeout(() => { window.location.href = 'user-login.php'; }, 2000);</script>
            <?php else: ?>

            <form method="POST" id="registerForm" novalidate>
                <div class="form-row-custom">
                    <div class="form-floating-custom">
                        <label for="full_name"><i class="fas fa-user"></i> Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                    <div class="form-floating-custom">
                        <label for="username"><i class="fas fa-at"></i> Username</label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES); ?>" required>
                        <div class="validation-msg" id="usernameMsg"></div>
                    </div>
                </div>

                <div class="form-row-custom">
                    <div class="form-floating-custom">
                        <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>" required>
                        <div class="validation-msg" id="emailMsg"></div>
                    </div>
                    <div class="form-floating-custom">
                        <label for="phone"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="+880 1XXXXXXXXX" value="<?php echo htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                </div>

                <div class="form-row-custom">
                    <div class="form-floating-custom">
                        <label for="department"><i class="fas fa-building"></i> Department</label>
                        <select class="form-control" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <?php
                            $departments = ['CSE','EEE','ECE','ME','CE','IPE','Textile','Leather','Architecture','URP','Chemistry','Mathematics','Physics','Humanities','BBA'];
                            foreach ($departments as $dept) {
                                $selected = (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : '';
                                echo "<option value=\"$dept\" $selected>$dept</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-floating-custom">
                        <label for="student_id"><i class="fas fa-id-card"></i> Student ID</label>
                        <input type="text" class="form-control" id="student_id" name="student_id" placeholder="e.g., 2103001" value="<?php echo htmlspecialchars($_POST['student_id'] ?? '', ENT_QUOTES); ?>" required>
                    </div>
                </div>

                <div class="form-row-custom">
                    <div class="form-floating-custom">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <div class="input-group-custom">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Min 8 characters" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            <div class="strength-text" id="strengthText"></div>
                        </div>
                    </div>
                    <div class="form-floating-custom">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <div class="input-group-custom">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="validation-msg" id="confirmMsg"></div>
                    </div>
                </div>

                <button type="submit" class="btn-register" id="submitBtn">
                    <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
                    <span class="spinner"><i class="fas fa-circle-notch fa-spin"></i> Creating Account...</span>
                </button>
            </form>

            <div class="form-footer-link">
                Already have an account? <a href="user-login.php">Login here</a>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const icon = btn.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Password strength meter
        document.getElementById('password')?.addEventListener('input', function() {
            const val = this.value;
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');
            let score = 0;
            if (val.length >= 8) score++;
            if (val.length >= 12) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            const levels = [
                { width: '0%', color: '#e2e8f0', label: '' },
                { width: '20%', color: '#fc8181', label: 'Very Weak' },
                { width: '40%', color: '#f6ad55', label: 'Weak' },
                { width: '60%', color: '#fbd38d', label: 'Fair' },
                { width: '80%', color: '#68d391', label: 'Strong' },
                { width: '100%', color: '#38a169', label: 'Very Strong' }
            ];
            const level = levels[Math.min(score, 5)];
            fill.style.width = level.width;
            fill.style.background = level.color;
            text.textContent = level.label;
            text.style.color = level.color;
        });

        // Confirm password matching
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const pw = document.getElementById('password').value;
            const msg = document.getElementById('confirmMsg');
            if (this.value && this.value === pw) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                msg.className = 'validation-msg show success';
                msg.innerHTML = '<i class="fas fa-check-circle"></i> Passwords match';
            } else if (this.value) {
                this.classList.remove('is-valid');
                this.classList.add('is-invalid');
                msg.className = 'validation-msg show error';
                msg.innerHTML = '<i class="fas fa-times-circle"></i> Passwords do not match';
            } else {
                this.classList.remove('is-valid', 'is-invalid');
                msg.className = 'validation-msg';
            }
        });

        // Real-time username check
        let usernameTimer;
        document.getElementById('username')?.addEventListener('input', function() {
            clearTimeout(usernameTimer);
            const val = this.value.trim();
            const msg = document.getElementById('usernameMsg');
            if (val.length < 3) {
                this.classList.remove('is-valid', 'is-invalid');
                msg.className = 'validation-msg';
                return;
            }
            usernameTimer = setTimeout(() => {
                fetch('check-availability.php?type=username&value=' + encodeURIComponent(val))
                    .then(r => r.json())
                    .then(data => {
                        if (data.available) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                            msg.className = 'validation-msg show success';
                            msg.innerHTML = '<i class="fas fa-check-circle"></i> Username is available';
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                            msg.className = 'validation-msg show error';
                            msg.innerHTML = '<i class="fas fa-times-circle"></i> Username already exists';
                        }
                    }).catch(() => {});
            }, 400);
        });

        // Real-time email check
        let emailTimer;
        document.getElementById('email')?.addEventListener('input', function() {
            clearTimeout(emailTimer);
            const val = this.value.trim();
            const msg = document.getElementById('emailMsg');
            if (!val || !val.includes('@')) {
                this.classList.remove('is-valid', 'is-invalid');
                msg.className = 'validation-msg';
                return;
            }
            emailTimer = setTimeout(() => {
                fetch('check-availability.php?type=email&value=' + encodeURIComponent(val))
                    .then(r => r.json())
                    .then(data => {
                        if (data.available) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                            msg.className = 'validation-msg show success';
                            msg.innerHTML = '<i class="fas fa-check-circle"></i> Email is available';
                        } else {
                            this.classList.remove('is-valid');
                            this.classList.add('is-invalid');
                            msg.className = 'validation-msg show error';
                            msg.innerHTML = '<i class="fas fa-times-circle"></i> Email already registered';
                        }
                    }).catch(() => {});
            }, 400);
        });

        // Loading button on submit
        document.getElementById('registerForm')?.addEventListener('submit', function() {
            document.getElementById('submitBtn').classList.add('loading');
        });
    </script>
</body>
</html>
