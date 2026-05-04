<?php
/**
 * Database Connection File
 * Uses MySQLi with prepared statements for security
 */

$DB_HOST = 'localhost';
$DB_USER = 'root';        // Change to your MySQL username
$DB_PASS = '';             // Change to your MySQL password
$DB_NAME = 'bit2byte';

// Create connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME`");
$conn->select_db($DB_NAME);

// Create users table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($createTable)) {
    die("Error creating table: " . $conn->error);
}

// Seed the admin account if it doesn't exist yet
$adminEmail = 'dhruboplabon987@gmail.com';
$checkAdmin = $conn->prepare("SELECT id FROM users WHERE email = ?");
$checkAdmin->bind_param("s", $adminEmail);
$checkAdmin->execute();
$checkAdmin->store_result();

if ($checkAdmin->num_rows === 0) {
    $adminName = 'Admin';
    $adminPassword = password_hash('alliswell123', PASSWORD_DEFAULT);
    $adminRole = 'admin';
    $insertAdmin = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $insertAdmin->bind_param("ssss", $adminName, $adminEmail, $adminPassword, $adminRole);
    $insertAdmin->execute();
    $insertAdmin->close();
}

$checkAdmin->close();
