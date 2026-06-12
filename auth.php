<?php
/**
 * Authentication Helper Functions
 * Provides session management and role-based access control
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    $stmt = $conn->prepare("SELECT id, full_name, username, email, phone, department, student_id, password, role, created_at FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

/**
 * Find a user by username using prepared statements
 */
function find_user_by_username(string $username): ?array
{
    global $conn;
    $stmt = $conn->prepare("SELECT id, full_name, username, email, phone, department, student_id, password, role, created_at FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

/**
 * Find a user by email OR username
 */
function find_user_by_login(string $login): ?array
{
    $user = find_user_by_email($login);
    if (!$user) {
        $user = find_user_by_username($login);
    }
    return $user;
}

/**
 * Find a user by ID
 */
function find_user_by_id(int $id): ?array
{
    global $conn;
    $stmt = $conn->prepare("SELECT id, full_name, username, email, phone, department, student_id, password, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
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
 * Check if a username already exists in the database
 */
function username_exists(string $username): bool
{
    return find_user_by_username($username) !== null;
}

/**
 * Save a new user to the database (role always defaults to 'user')
 */
function save_user(string $full_name, string $username, string $email, string $phone, string $department, string $student_id, string $passwordHash): bool
{
    global $conn;
    $role = 'user';
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, phone, department, student_id, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $full_name, $username, $email, $phone, $department, $student_id, $passwordHash, $role);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Check if the current visitor is logged in
 */
function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['username']) && !empty($_SESSION['role']);
}

/**
 * Log a user in by storing their data in the session
 */
function login_member(int $id, string $full_name, string $username, string $email, string $role): void
{
    $_SESSION['user_id']   = $id;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['username']  = $username;
    $_SESSION['email']     = $email;
    $_SESSION['role']      = $role;

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
}

/**
 * Destroy the session and log the user out
 */
function logout_member(): void
{
    $role = $_SESSION['role'] ?? 'user';
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
 * Redirect to user login page if the user is not logged in
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . get_base_url() . '/user-login.php');
        exit;
    }
}

/**
 * Redirect to admin login if the user is not an admin
 */
function require_admin(): void
{
    if (!is_logged_in() || $_SESSION['role'] !== 'admin') {
        header('Location: ' . get_base_url() . '/admin-login.php');
        exit;
    }
}

/**
 * Get base URL of the project
 */
function get_base_url(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // If we're in a subdirectory like /admin or /user, go up one level
    if (preg_match('#/(admin|user)$#', $scriptDir)) {
        $scriptDir = dirname($scriptDir);
    }
    return $scriptDir;
}

/**
 * Get the current user's display name
 */
function current_member_name(): string
{
    return $_SESSION['full_name'] ?? '';
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
    $base     = get_base_url();

    $html = '<nav class="navbar">
        <div class="nav-container">
            <div class="logo"><span class="logo-text">Bit2byte</span></div>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
            <ul class="nav-menu" id="navMenu">
                <li><a href="' . $base . '/index.php" class="nav-link">Home</a></li>
                <li><a href="' . $base . '/index.php#about" class="nav-link">About</a></li>
                <li><a href="' . $base . '/pricing.php" class="nav-link">Pricing</a></li>';

    if (!$loggedIn) {
        $html .= '
                <li><a href="' . $base . '/user-login.php" class="nav-link">Login</a></li>
                <li><a href="' . $base . '/register.php" class="nav-link nav-cta">Register</a></li>';
    } elseif ($role === 'admin') {
        $html .= '
                <li><a href="' . $base . '/admin/dashboard.php" class="nav-link nav-admin">Admin Panel</a></li>
                <li><a href="' . $base . '/logout.php" class="nav-link nav-cta-danger">Logout</a></li>';
    } elseif ($role === 'company') {
        $html .= '
                <li><a href="' . $base . '/company/dashboard.php" class="nav-link nav-admin">Company Panel</a></li>
                <li><a href="' . $base . '/logout.php" class="nav-link nav-cta-danger">Logout</a></li>';
    } else {
        $html .= '
                <li><a href="' . $base . '/user/dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="' . $base . '/logout.php" class="nav-link nav-cta-danger">Logout</a></li>';
    }

    $html .= '
            </ul>
        </div>
    </nav>';

    return $html;
}

/**
 * Fetch active subscription details for a user
 */
function get_active_subscription(int $userId): ?array
{
    global $conn;
    $today = date('Y-m-d');
    $stmt = $conn->prepare("
        SELECT us.id as subscription_id, us.plan_id, us.start_date, us.end_date, us.status, us.payment_status,
               sp.name as plan_name, sp.price, sp.billing_cycle, sp.features, sp.description
        FROM user_subscriptions us
        JOIN subscription_plans sp ON us.plan_id = sp.id
        WHERE us.user_id = ? AND us.status = 'active' AND (us.end_date IS NULL OR us.end_date >= ?)
        ORDER BY us.id DESC LIMIT 1
    ");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $sub = $result->fetch_assoc();
    $stmt->close();
    return $sub ?: null;
}

/**
 * Check if the user is a Premium Student member
 */
function is_premium_student(int $userId): bool
{
    $sub = get_active_subscription($userId);
    return $sub && $sub['plan_name'] === 'Student Premium';
}

/**
 * Check if the user is a Professional Member
 */
function is_professional_member(int $userId): bool
{
    $sub = get_active_subscription($userId);
    return $sub && $sub['plan_name'] === 'Professional Member';
}

/**
 * Check if the user is a Company Partner
 */
function is_company_partner(int $userId): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
        return ($_SESSION['role'] ?? '') === 'company';
    }
    global $conn;
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return ($res['role'] ?? '') === 'company';
}

/**
 * Check if the user has premium resource access (Premium Student, Professional, Admin, or Sponsor)
 */
function has_premium_access(int $userId): bool
{
    // Admins always have access
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId && ($_SESSION['role'] ?? '') === 'admin') {
        return true;
    }
    
    $sub = get_active_subscription($userId);
    if (!$sub) {
        // Fallback check role
        global $conn;
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (($res['role'] ?? '') === 'admin') {
            return true;
        }
        return false;
    }
    
    $premium_plans = ['Student Premium', 'Professional Member', 'Company Partner', 'Platinum Sponsor'];
    return in_array($sub['plan_name'], $premium_plans);
}

/**
 * Calculate remaining days for a subscription
 */
function get_days_remaining(?string $endDate): ?int
{
    if (!$endDate) {
        return null; // Unlimited/lifetime
    }
    $end = new DateTime($endDate);
    $today = new DateTime(date('Y-m-d'));
    if ($today > $end) {
        return 0;
    }
    $diff = $today->diff($end);
    return (int)$diff->format('%r%a');
}
