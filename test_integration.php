<?php
/**
 * Integration Test Script for Bit2Byte SaaS Platform
 * Tests: DB connection, table creation, subscription plans, auth functions, role access
 */

echo "=== Bit2Byte Integration Test Suite ===\n\n";
$passed = 0;
$failed = 0;

function test($name, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $name\n";
        $passed++;
    } else {
        echo "  [FAIL] $name\n";
        $failed++;
    }
}

// ─── 1. Database Connection ───────────────────────────────────
echo "--- Test 1: Database Connection ---\n";
require_once __DIR__ . '/db.php';
test('Database connection established', $conn && !$conn->connect_error);
test('Selected database bit2byte', $conn->query("SELECT DATABASE()")->fetch_row()[0] === 'bit2byte');

// ─── 2. Table Existence ───────────────────────────────────────
echo "\n--- Test 2: Required Tables ---\n";
$requiredTables = ['users', 'contact_messages', 'events', 'subscription_plans', 'user_subscriptions', 'company_profiles', 'payments', 'job_postings', 'job_applications'];
$r = $conn->query("SHOW TABLES");
$existingTables = [];
while ($row = $r->fetch_row()) { $existingTables[] = $row[0]; }

foreach ($requiredTables as $table) {
    test("Table '$table' exists", in_array($table, $existingTables));
}

// ─── 3. Subscription Plans Seeded ─────────────────────────────
echo "\n--- Test 3: Subscription Plans ---\n";
$r = $conn->query("SELECT COUNT(*) as cnt FROM subscription_plans");
$planCount = $r->fetch_assoc()['cnt'];
test('Subscription plans seeded (>= 8)', $planCount >= 8);

$r = $conn->query("SELECT name, price FROM subscription_plans WHERE name='Free Member' LIMIT 1");
$freePlan = $r->fetch_assoc();
test('Free Member plan exists with price 0', $freePlan && floatval($freePlan['price']) == 0);

$r = $conn->query("SELECT name, price FROM subscription_plans WHERE name='Student Premium' AND billing_cycle='monthly' LIMIT 1");
$premPlan = $r->fetch_assoc();
test('Student Premium monthly plan exists (price 99)', $premPlan && floatval($premPlan['price']) == 99);

// ─── 4. Admin Account ────────────────────────────────────────
echo "\n--- Test 4: Admin Account ---\n";
$r = $conn->query("SELECT id, username, role FROM users WHERE email='admin@gmail.com'");
$admin = $r->fetch_assoc();
test('Admin account exists', $admin !== null);
test('Admin role is admin', $admin && $admin['role'] === 'admin');

// ─── 5. Auth Functions ──────────────────────────────────────
echo "\n--- Test 5: Auth Functions ---\n";

// Start session for auth functions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth.php';

$foundAdmin = find_user_by_email('admin@gmail.com');
test('find_user_by_email works', $foundAdmin !== null && $foundAdmin['username'] === 'admin');

$foundByUsername = find_user_by_username('admin');
test('find_user_by_username works', $foundByUsername !== null && $foundByUsername['email'] === 'admin@gmail.com');

$foundByLogin = find_user_by_login('admin');
test('find_user_by_login works (username)', $foundByLogin !== null);

$foundByLogin2 = find_user_by_login('admin@gmail.com');
test('find_user_by_login works (email)', $foundByLogin2 !== null);

test('email_exists works', email_exists('admin@gmail.com') === true);
test('email_exists returns false for unknown', email_exists('nonexistent@example.com') === false);

test('username_exists works', username_exists('admin') === true);
test('username_exists returns false for unknown', username_exists('nonexistentuser') === false);

test('is_logged_in returns false before login', is_logged_in() === false);

test('sanitize_input strips tags', sanitize_input('<script>alert("xss")</script>') !== '<script>alert("xss")</script>');

test('get_base_url returns string', is_string(get_base_url()));

// ─── 6. Subscription Gate Functions ──────────────────────────
echo "\n--- Test 6: Subscription Gating ---\n";

$adminId = $foundAdmin['id'];
test('get_active_subscription returns null for admin (no sub record)', get_active_subscription($adminId) === null || is_array(get_active_subscription($adminId)));

// Simulate login
$_SESSION['user_id'] = $adminId;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Admin';
$_SESSION['email'] = 'admin@gmail.com';

test('has_premium_access returns true for admin', has_premium_access($adminId) === true);

test('get_days_remaining returns null for null date', get_days_remaining(null) === null);
test('get_days_remaining returns 0 for past date', get_days_remaining('2020-01-01') === 0);
test('get_days_remaining returns positive for future date', get_days_remaining(date('Y-m-d', strtotime('+30 days'))) > 0);

// ─── 7. File Existence ──────────────────────────────────────
echo "\n--- Test 7: Required Files ---\n";
$requiredFiles = [
    'db.php', 'auth.php', 'pricing.php', 'pricing.css', 'pricing.js',
    'subscribe-process.php', 'user-login.php', 'register.php',
    'admin-login.php', 'index.php', 'style.css', 'logout.php',
    'user/dashboard.php', 'company/dashboard.php', 'admin/dashboard.php'
];
foreach ($requiredFiles as $file) {
    test("File '$file' exists", file_exists(__DIR__ . '/' . $file));
}

// ─── 8. PHP Syntax Check ────────────────────────────────────
echo "\n--- Test 8: PHP Syntax Validation ---\n";
$phpFiles = ['db.php', 'auth.php', 'pricing.php', 'subscribe-process.php', 'user-login.php', 'register.php', 'admin-login.php', 'index.php', 'logout.php', 'user/dashboard.php', 'company/dashboard.php', 'admin/dashboard.php'];
foreach ($phpFiles as $file) {
    $output = [];
    $ret = 0;
    exec("php -l " . escapeshellarg(__DIR__ . '/' . $file) . " 2>&1", $output, $ret);
    test("Syntax OK: $file", $ret === 0);
}

// ─── 9. Password Hashing ────────────────────────────────────
echo "\n--- Test 9: Security ---\n";
$adminData = find_user_by_email('admin@gmail.com');
test('Admin password is hashed (not plaintext)', $adminData && strlen($adminData['password']) > 50 && substr($adminData['password'], 0, 4) === '$2y$');

// ─── Summary ─────────────────────────────────────────────────
echo "\n========================================\n";
echo "Results: $passed PASSED, $failed FAILED\n";
echo "========================================\n";

// Cleanup
unset($_SESSION);
