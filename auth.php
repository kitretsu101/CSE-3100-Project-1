<?php
/**
 * Authentication Helper Functions
 * Provides session management and role-based access control
 */

session_start();

require_once __DIR__ . '/db.php';

/**
 * Sanitize user input to prevent XSS
 */
function sanitize_input(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Find a user by email using prepared statements
 */
function find_user_by_email(string $email): ?array
{
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

/**
 * Check if an email already exists in the database
 */
function email_exists(string $email): bool
{
    return find_user_by_email($email) !== null;
}

/**
 * Save a new user to the database (role always defaults to 'user')
 */
function save_user(string $name, string $email, string $passwordHash): bool
{
    global $conn;
    $role = 'user'; // ALWAYS 'user' on registration — admin is set manually in DB
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $passwordHash, $role);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Check if the current visitor is logged in
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_name']) && !empty($_SESSION['role']);
}

/**
 * Log a user in by storing their data in the session
 */
function login_member(int $id, string $name, string $email, string $role): void
{
    $_SESSION['user_id']    = $id;
    $_SESSION['user_name']  = $name;
    $_SESSION['user_email'] = $email;
    $_SESSION['role']       = $role;

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
}

/**
 * Destroy the session and log the user out
 */
function logout_member(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Redirect to login page if the user is not logged in
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Redirect to index if the user is not an admin
 */
function require_admin(): void
{
    require_login();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
}

/**
 * Get the current user's display name
 */
function current_member_name(): string
{
    return $_SESSION['user_name'] ?? '';
}

/**
 * Get the current user's role
 */
function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

/**
 * Generate a dynamic navbar based on login state and role
 */
function render_navbar(): string
{
    $loggedIn = is_logged_in();
    $role     = current_role();

    $html = '<nav class="navbar">
        <div class="nav-container">
            <div class="logo"><span class="logo-text">Bit2byte</span></div>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="index.php" class="nav-link">Home</a></li>
                <li><a href="index.php#about" class="nav-link">About</a></li>';

    if (!$loggedIn) {
        // Guest: show Login & Register
        $html .= '
                <li><a href="login.php" class="nav-link">Login</a></li>
                <li><a href="register.php" class="nav-link nav-cta">Register</a></li>';
    } elseif ($role === 'admin') {
        // Admin: show Admin Panel & Logout
        $html .= '
                <li><a href="admin.php" class="nav-link nav-admin">Admin Panel</a></li>
                <li><a href="logout.php" class="nav-link nav-cta-danger">Logout</a></li>';
    } else {
        // Normal user: show Profile & Logout
        $html .= '
                <li><a href="profile.php" class="nav-link">Profile</a></li>
                <li><a href="logout.php" class="nav-link nav-cta-danger">Logout</a></li>';
    }

    $html .= '
            </ul>
        </div>
    </nav>';

    return $html;
}
