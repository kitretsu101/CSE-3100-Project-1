<?php
/**
 * Database Connection File
 * Uses MySQLi with prepared statements for security
 * Auto-creates database and tables if they don't exist
 */

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'bit2byte';

// Suppress default error display – we handle errors ourselves
mysqli_report(MYSQLI_REPORT_OFF);

// Create connection (resilient connection probing for different setups)
$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS);

if ($conn->connect_error) {
    // Try connection on port 4306 (custom XAMPP setup)
    $conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, null, 4306);
}

if ($conn->connect_error) {
    // Try connection on 127.0.0.1:4306
    $conn = @new mysqli('127.0.0.1', $DB_USER, $DB_PASS, null, 4306);
}

// Check connection with friendly message
if ($conn->connect_error) {
    $errorMsg = '
    <div style="max-width:600px;margin:80px auto;padding:2rem;font-family:Poppins,sans-serif;background:#fff5f5;border:1px solid #feb2b2;border-radius:12px;color:#9b2c2c;text-align:center;">
        <h2 style="margin-bottom:1rem;">⚠️ Database Connection Failed</h2>
        <p>Could not connect to MySQL server.</p>
        <p style="margin-top:0.5rem;font-size:0.9rem;color:#c53030;">Please make sure XAMPP MySQL is running.</p>
        <p style="margin-top:1rem;font-size:0.8rem;color:#999;">Error: ' . htmlspecialchars($conn->connect_error, ENT_QUOTES, 'UTF-8') . '</p>
    </div>';
    die($errorMsg);
}

// Create database if it doesn't exist
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    die('<div style="max-width:600px;margin:80px auto;padding:2rem;font-family:Poppins,sans-serif;background:#fff5f5;border:1px solid #feb2b2;border-radius:12px;color:#9b2c2c;text-align:center;">
        <h2>⚠️ Database Creation Failed</h2>
        <p>' . htmlspecialchars($conn->error, ENT_QUOTES, 'UTF-8') . '</p>
    </div>');
}

$conn->select_db($DB_NAME);

// ─── Create users table ─────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(100) NOT NULL,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `student_id` VARCHAR(50) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create contact_messages table ───────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `contact_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create events table ─────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `event_date` DATE NOT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create event_attendees table ────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `event_attendees` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `event_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (`event_id`),
    INDEX (`user_id`),
    CONSTRAINT `fk_event_attendees_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_event_attendees_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create subscription_plans table ─────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `billing_cycle` VARCHAR(20) NOT NULL,
    `description` TEXT,
    `features` TEXT,
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create user_subscriptions table ────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `plan_id` INT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'active',
    `payment_status` VARCHAR(20) DEFAULT 'completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create company_profiles table ─────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `company_profiles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `company_name` VARCHAR(100) NOT NULL,
    `industry` VARCHAR(100) DEFAULT NULL,
    `website` VARCHAR(255) DEFAULT NULL,
    `logo` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `contact_email` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create payments table ──────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `subscription_id` INT DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
    `payment_status` VARCHAR(20) DEFAULT 'completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create job_postings table ─────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `job_postings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `description` TEXT,
    `requirements` TEXT,
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Create job_applications table ─────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `job_applications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `job_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `resume_url` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(50) DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// ─── Seed subscription plans ─────────────────────────────────────
$checkPlans = $conn->query("SELECT COUNT(*) as cnt FROM subscription_plans");
if ($checkPlans && $checkPlans->fetch_assoc()['cnt'] == 0) {
    $plans = [
        ['Free Member', 0.00, 'monthly', 'Access to general club content and announcements', '["View public events", "View announcements", "Basic profile", "Join limited events", "Access community discussions", "Receive newsletters"]'],
        ['Student Premium', 99.00, 'monthly', 'Access to premium workshops, interview preparation, and mentorship', '["Everything in Free", "Unlimited event registration", "Premium workshops", "Access to learning resources", "Coding interview preparation materials", "Competitive Programming resources", "Resume review", "Internship opportunities", "Priority event registration", "Premium member badge"]'],
        ['Student Premium', 999.00, 'yearly', 'Annual premium membership with discount', '["Everything in Free", "Unlimited event registration", "Premium workshops", "Access to learning resources", "Coding interview preparation materials", "Competitive Programming resources", "Resume review", "Internship opportunities", "Priority event registration", "Premium member badge"]'],
        ['Professional Member', 299.00, 'monthly', 'Tailored for graduated alumni and industry professionals', '["Everything in Student Premium", "Industry networking", "Exclusive seminars", "Career mentorship", "Company recruitment opportunities", "Advanced certifications", "Professional profile verification", "Access to premium webinars"]'],
        ['Professional Member', 2999.00, 'yearly', 'Annual professional membership with discount', '["Everything in Student Premium", "Industry networking", "Exclusive seminars", "Career mentorship", "Company recruitment opportunities", "Advanced certifications", "Professional profile verification", "Access to premium webinars"]'],
        ['Company Partner', 999.00, 'monthly', 'Designed for companies, tech firms, and recruiters', '["Company profile page", "Post job opportunities", "Post internships", "Sponsor events", "Access student talent database", "Recruitment campaigns", "Company branding", "Analytics dashboard"]'],
        ['Company Partner', 9999.00, 'yearly', 'Annual company partnership package', '["Company profile page", "Post job opportunities", "Post internships", "Sponsor events", "Access student talent database", "Recruitment campaigns", "Company branding", "Analytics dashboard"]'],
        ['Platinum Sponsor', 5000.00, 'monthly', 'Dedicated corporate sponsorship package', '["Featured homepage placement", "Premium branding", "Event sponsorship priority", "Dedicated account manager", "Advanced analytics", "Direct access to top talent", "Premium recruitment campaigns"]']
    ];

    $stmt = $conn->prepare("INSERT INTO subscription_plans (name, price, billing_cycle, description, features) VALUES (?, ?, ?, ?, ?)");
    foreach ($plans as $p) {
        $stmt->bind_param("sdsss", $p[0], $p[1], $p[2], $p[3], $p[4]);
        $stmt->execute();
    }
    $stmt->close();
}

// ─── Seed admin account ──────────────────────────────────────────
$adminEmail = 'admin@gmail.com';
$checkAdmin = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkAdmin->bind_param("s", $adminEmail);
$checkAdmin->execute();
$checkAdmin->store_result();

if ($checkAdmin->num_rows === 0) {
    $adminName     = 'Admin';
    $adminUsername  = 'admin';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $adminRole     = 'admin';
    $adminPhone    = '';
    $adminDept     = 'Administration';
    $adminSID      = 'ADMIN-001';

    $insertAdmin = $conn->prepare("INSERT INTO users (full_name, username, email, phone, department, student_id, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insertAdmin->bind_param("ssssssss", $adminName, $adminUsername, $adminEmail, $adminPhone, $adminDept, $adminSID, $adminPassword, $adminRole);
    $insertAdmin->execute();
    $insertAdmin->close();
}

$checkAdmin->close();
