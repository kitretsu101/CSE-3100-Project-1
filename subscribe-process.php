<?php
/**
 * Subscription Checkout Processing Endpoint
 * Handles plan purchase logs, database overrides, and role updates (e.g. upgrades to Company Partner).
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'status' => 'error',
    'message' => 'Checkout failed. Please try again.'
];

// Check authorization
if (!is_logged_in()) {
    http_response_code(401);
    $response['message'] = 'Session expired. Please log in first.';
    echo json_encode($response);
    exit;
}

$userId = $_SESSION['user_id'];

// Extract parameters
$planId        = intval($_POST['plan_id'] ?? 0);
$billingCycle  = trim($_POST['billing_cycle'] ?? 'monthly');
$paymentMethod = trim($_POST['payment_method'] ?? '');

if (!$planId || !$paymentMethod) {
    http_response_code(400);
    $response['message'] = 'Missing required checkout inputs.';
    echo json_encode($response);
    exit;
}

// ─── 1. Verify Plan Specifications ───────────────────────────────────
$stmt = $conn->prepare("SELECT id, name, price, billing_cycle, status FROM subscription_plans WHERE id = ?");
$stmt->bind_param("i", $planId);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan || $plan['status'] !== 'active') {
    http_response_code(404);
    $response['message'] = 'The selected plan is inactive or does not exist.';
    echo json_encode($response);
    exit;
}

$planPrice = floatval($plan['price']);
$planName  = $plan['name'];

// Verify pricing and billing method match (prevent tampering)
if ($planPrice > 0 && $paymentMethod === 'free') {
    http_response_code(400);
    $response['message'] = 'Invalid payment method for paid plans.';
    echo json_encode($response);
    exit;
}

// ─── 2. Calculate subscription cycle dates ───────────────────────────
$startDate = date('Y-m-d');
$endDate   = null;

if ($planPrice > 0) {
    if ($billingCycle === 'yearly') {
        $endDate = date('Y-m-d', strtotime('+365 days'));
    } else {
        $endDate = date('Y-m-d', strtotime('+30 days'));
    }
}

// Begin database transaction for safety
$conn->begin_transaction();

try {
    // ─── 3. Invalidate previous active subscriptions ────────────────
    $stmt = $conn->prepare("UPDATE user_subscriptions SET status = 'canceled' WHERE user_id = ? AND status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    // ─── 4. Insert new active subscription ───────────────────────────
    $status = 'active';
    $paymentStatus = 'completed';
    $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $userId, $planId, $startDate, $endDate, $status, $paymentStatus);
    $stmt->execute();
    $subscriptionId = $conn->insert_id;
    $stmt->close();

    // ─── 5. Record transactions for paid subscription options ────────
    if ($planPrice > 0) {
        $transactionId = 'TXN-' . strtoupper(bin2hex(random_bytes(5)));
        
        $stmt = $conn->prepare("INSERT INTO payments (user_id, subscription_id, amount, payment_method, transaction_id, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iidsss", $userId, $subscriptionId, $planPrice, $paymentMethod, $transactionId, $paymentStatus);
        $stmt->execute();
        $stmt->close();
    }

    // ─── 6. Upgrade user Role details (Company Partner Integration) ─
    if ($planName === 'Company Partner') {
        // Upgrade role to 'company'
        $stmt = $conn->prepare("UPDATE users SET role = 'company' WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['role'] = 'company';

        // Initialize blank company profile if none exists
        $stmt = $conn->prepare("SELECT id FROM company_profiles WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$profile) {
            $companyName = $_SESSION['username'] . ' Corp';
            $contactEmail = $_SESSION['email'];
            $industry = 'Technology';
            $stmt = $conn->prepare("INSERT INTO company_profiles (user_id, company_name, industry, contact_email) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $userId, $companyName, $industry, $contactEmail);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Reset to standard role 'user' (demoted from company or standard member)
        // Except if user was an admin! Keep admin as admin.
        if ($_SESSION['role'] !== 'admin') {
            $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            $_SESSION['role'] = 'user';
        }
    }

    // Commit changes
    $conn->commit();

    $response['status'] = 'success';
    $response['message'] = "Payment Successful (Demo Mode)! Your membership has been updated to {$planName}.";
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    // Rollback transactions on connection failures
    $conn->rollback();
    http_response_code(500);
    $response['message'] = 'An error occurred during payment processing. Transaction rolled back.';
    echo json_encode($response);
    exit;
}
