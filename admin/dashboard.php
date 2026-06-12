<?php
require_once __DIR__ . '/../auth.php';
require_admin();

$adminName = htmlspecialchars($_SESSION['full_name'], ENT_QUOTES, 'UTF-8');
$base = get_base_url();

// Fetch stats
$totalUsers = 0;
$totalEvents = 0;
$totalMessages = 0;
$totalRevenue = 0.00;
$monthlyRevenue = 0.00;
$activeSubscribers = 0;

$r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role!='admin'");
if ($r) { $totalUsers = $r->fetch_assoc()['cnt']; }

$r = $conn->query("SELECT COUNT(*) as cnt FROM events");
if ($r) { $totalEvents = $r->fetch_assoc()['cnt']; }

$r = $conn->query("SELECT COUNT(*) as cnt FROM contact_messages");
if ($r) { $totalMessages = $r->fetch_assoc()['cnt']; }

$totalMembers = $totalUsers;

// Fetch SaaS billing stats
$r = $conn->query("SELECT SUM(amount) as rev FROM payments WHERE payment_status = 'completed'");
if ($r) { $totalRevenue = floatval($r->fetch_assoc()['rev'] ?? 0.00); }

$thisMonth = date('Y-m');
$r = $conn->query("SELECT SUM(amount) as rev FROM payments WHERE payment_status = 'completed' AND DATE_FORMAT(created_at, '%Y-%m') = '$thisMonth'");
if ($r) { $monthlyRevenue = floatval($r->fetch_assoc()['rev'] ?? 0.00); }

$r = $conn->query("
    SELECT COUNT(DISTINCT us.user_id) as cnt 
    FROM user_subscriptions us 
    JOIN subscription_plans sp ON us.plan_id = sp.id
    WHERE us.status = 'active' AND sp.price > 0 AND (us.end_date IS NULL OR us.end_date >= CURRENT_DATE())
");
if ($r) { $activeSubscribers = intval($r->fetch_assoc()['cnt'] ?? 0); }

// Fetch all users
$users = [];
$r = $conn->query("SELECT id, full_name, username, email, phone, department, student_id, role, created_at FROM users ORDER BY created_at DESC");
if ($r) { while ($row = $r->fetch_assoc()) { $users[] = $row; } }

// Fetch all contact messages
$messages = [];
$r = $conn->query("SELECT id, name, email, message, created_at FROM contact_messages ORDER BY created_at DESC");
if ($r) { while ($row = $r->fetch_assoc()) { $messages[] = $row; } }

// Fetch all events
$events = [];
$r = $conn->query("SELECT id, title, description, event_date, image, created_at FROM events ORDER BY event_date DESC");
if ($r) { while ($row = $r->fetch_assoc()) { $events[] = $row; } }

// Fetch SaaS subscriptions log
$subscriptions = [];
$r = $conn->query("
    SELECT us.id as sub_id, us.start_date, us.end_date, us.status as sub_status,
           u.id as user_id, u.full_name, u.email, sp.name as plan_name, sp.price, sp.billing_cycle
    FROM user_subscriptions us
    JOIN users u ON us.user_id = u.id
    JOIN subscription_plans sp ON us.plan_id = sp.id
    ORDER BY us.created_at DESC
");
if ($r) { while ($row = $r->fetch_assoc()) { $subscriptions[] = $row; } }

// Fetch active subscription plans for forms
$plans = [];
$r = $conn->query("SELECT id, name, price, billing_cycle FROM subscription_plans WHERE status='active' ORDER BY price ASC");
if ($r) { while ($row = $r->fetch_assoc()) { $plans[] = $row; } }

// Monthly registration data for chart
$monthlyData = [];
$r = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt FROM users WHERE role!='admin' GROUP BY month ORDER BY month DESC LIMIT 12");
if ($r) { while ($row = $r->fetch_assoc()) { $monthlyData[$row['month']] = $row['cnt']; } }
$monthlyData = array_reverse($monthlyData, true);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete_user' && !empty($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?tab=members&msg=deleted");
        exit;
    }

    if ($action === 'delete_message' && !empty($_POST['msg_id'])) {
        $mid = (int)$_POST['msg_id'];
        $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->bind_param("i", $mid);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?tab=messages&msg=deleted");
        exit;
    }

    if ($action === 'add_event') {
        $title = sanitize_input($_POST['event_title'] ?? '');
        $desc  = sanitize_input($_POST['event_desc'] ?? '');
        $date  = sanitize_input($_POST['event_date'] ?? '');
        if ($title && $date) {
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $title, $desc, $date);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: dashboard.php?tab=events&msg=added");
        exit;
    }

    if ($action === 'delete_event' && !empty($_POST['event_id'])) {
        $eid = (int)$_POST['event_id'];
        $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $eid);
        $stmt->execute();
        $stmt->close();
        header("Location: dashboard.php?tab=events&msg=deleted");
        exit;
    }

    if ($action === 'manual_upgrade' && !empty($_POST['user_id']) && !empty($_POST['plan_id'])) {
        $uid = (int)$_POST['user_id'];
        $pid = (int)$_POST['plan_id'];
        $startDate = date('Y-m-d');
        
        $planStmt = $conn->prepare("SELECT name, price, billing_cycle FROM subscription_plans WHERE id = ?");
        $planStmt->bind_param("i", $pid);
        $planStmt->execute();
        $planDetail = $planStmt->get_result()->fetch_assoc();
        $planStmt->close();
        
        if ($planDetail) {
            $endDate = null;
            if ($planDetail['price'] > 0) {
                $endDate = ($planDetail['billing_cycle'] === 'yearly') ? date('Y-m-d', strtotime('+365 days')) : date('Y-m-d', strtotime('+30 days'));
            }
            
            $stmt = $conn->prepare("UPDATE user_subscriptions SET status = 'canceled' WHERE user_id = ? AND status = 'active'");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->bind_param("iiss", $uid, $pid, $startDate, $endDate);
            $stmt->execute();
            $stmt->close();
            
            if ($planDetail['name'] === 'Company Partner') {
                $stmt = $conn->prepare("UPDATE users SET role = 'company' WHERE id = ?");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $userRole = $stmt->get_result()->fetch_assoc()['role'] ?? 'user';
                $stmt->close();
                
                if ($userRole !== 'admin') {
                    $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                    $stmt->bind_param("i", $uid);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            header("Location: dashboard.php?tab=subscriptions&msg=updated");
            exit;
        }
    }

    if ($action === 'cancel_subscription' && !empty($_POST['sub_id'])) {
        $subId = (int)$_POST['sub_id'];
        
        $subStmt = $conn->prepare("SELECT user_id FROM user_subscriptions WHERE id = ?");
        $subStmt->bind_param("i", $subId);
        $subStmt->execute();
        $subRecord = $subStmt->get_result()->fetch_assoc();
        $subStmt->close();
        
        if ($subRecord) {
            $uid = $subRecord['user_id'];
            
            $stmt = $conn->prepare("UPDATE user_subscriptions SET status = 'canceled' WHERE id = ?");
            $stmt->bind_param("i", $subId);
            $stmt->execute();
            $stmt->close();
            
            $startDate = date('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO user_subscriptions (user_id, plan_id, start_date, status) VALUES (?, 1, ?, 'active')");
            $stmt->bind_param("is", $uid, $startDate);
            $stmt->execute();
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->bind_param("i", $uid);
            $stmt->execute();
            $userRole = $stmt->get_result()->fetch_assoc()['role'] ?? 'user';
            $stmt->close();
            
            if ($userRole !== 'admin') {
                $stmt = $conn->prepare("UPDATE users SET role = 'user' WHERE id = ?");
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $stmt->close();
            }
            header("Location: dashboard.php?tab=subscriptions&msg=deleted");
            exit;
        }
    }
}

$activeTab = $_GET['tab'] ?? 'dashboard';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bit2byte</title>
    <meta name="description" content="Bit2byte admin dashboard for managing members, events, and messages.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 260px;
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: rgba(32, 178, 170, 0.15);
            --accent: #20b2aa;
            --accent2: #17a2b8;
            --purple: #7b2ff7;
            --body-bg: #f1f5f9;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--body-bg); color: var(--text-dark); }
        body.dark-mode {
            --body-bg: #0f172a;
            --card-bg: #1e293b;
            --text-dark: #e2e8f0;
            --text-muted: #94a3b8;
        }
        body.dark-mode { background: var(--body-bg); color: var(--text-dark); }

        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0; left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            padding: 1.5rem 0;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s;
        }
        .sidebar-brand {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 1rem;
        }
        .sidebar-brand h2 {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-family: 'JetBrains Mono', monospace;
        }
        .sidebar-brand small { color: #64748b; font-size: 0.75rem; }
        .sidebar-nav { list-style: none; padding: 0; }
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1.5rem;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.92rem;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        .sidebar-nav li a:hover {
            background: var(--sidebar-hover);
            color: #e2e8f0;
        }
        .sidebar-nav li a.active {
            background: var(--sidebar-active);
            color: var(--accent);
            border-left-color: var(--accent);
            font-weight: 600;
        }
        .sidebar-nav li a i { width: 20px; text-align: center; }
        .sidebar-nav .nav-divider {
            height: 1px;
            background: rgba(255,255,255,0.06);
            margin: 0.8rem 1.5rem;
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        /* Top Bar */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .topbar h1 { font-size: 1.6rem; font-weight: 700; }
        .topbar-actions { display: flex; align-items: center; gap: 1rem; }
        .theme-toggle {
            background: var(--card-bg);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.5rem 0.8rem;
            cursor: pointer;
            color: var(--text-dark);
            font-size: 1.1rem;
            transition: all 0.2s;
        }
        body.dark-mode .theme-toggle { border-color: #334155; }
        .theme-toggle:hover { background: var(--accent); color: #fff; border-color: var(--accent); }

        /* Stat Cards */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 1.2rem;
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            color: #fff;
        }
        .stat-icon.teal { background: linear-gradient(135deg, #20b2aa, #17a2b8); }
        .stat-icon.purple { background: linear-gradient(135deg, #7b2ff7, #a855f7); }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #f97316); }
        .stat-icon.pink { background: linear-gradient(135deg, #ec4899, #f43f5e); }
        .stat-info h3 { font-size: 1.8rem; font-weight: 800; font-family: 'JetBrains Mono', monospace; color: var(--text-dark); }
        .stat-info p { font-size: 0.85rem; color: var(--text-muted); font-weight: 500; }

        /* Content Cards */
        .content-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            margin-bottom: 1.5rem;
        }
        .content-card h2 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .content-card h2 i { color: var(--accent); }

        /* Table */
        .table-responsive { overflow-x: auto; }
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        .table-custom th {
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        body.dark-mode .table-custom th { border-bottom-color: #334155; }
        .table-custom td {
            padding: 0.8rem;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-dark);
        }
        body.dark-mode .table-custom td { border-bottom-color: #1e293b; }
        .table-custom tr:hover td { background: rgba(32, 178, 170, 0.04); }

        .badge-role {
            padding: 0.2rem 0.7rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-role.admin { background: rgba(123, 47, 247, 0.12); color: #7b2ff7; }
        .badge-role.user { background: rgba(32, 178, 170, 0.12); color: #20b2aa; }

        .btn-delete {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            border: none;
            padding: 0.35rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-delete:hover { background: #ef4444; color: #fff; }

        /* Message Card */
        .msg-card {
            background: var(--body-bg);
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent);
        }
        body.dark-mode .msg-card { background: #0f172a; }
        .msg-card .msg-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .msg-card .msg-header h4 { font-size: 0.95rem; font-weight: 600; }
        .msg-card .msg-header small { color: var(--text-muted); font-size: 0.8rem; }
        .msg-card .msg-email { color: var(--accent); font-size: 0.85rem; margin-bottom: 0.5rem; }
        .msg-card .msg-body { color: var(--text-muted); font-size: 0.9rem; line-height: 1.6; }

        /* Event Form */
        .event-form { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; }
        .event-form .full-width { grid-column: 1 / -1; }
        .event-form input, .event-form textarea {
            padding: 0.7rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            background: var(--body-bg);
            color: var(--text-dark);
            transition: border-color 0.2s;
        }
        body.dark-mode .event-form input,
        body.dark-mode .event-form textarea { border-color: #334155; background: #0f172a; }
        .event-form input:focus, .event-form textarea:focus { border-color: var(--accent); outline: none; }
        .event-form textarea { resize: vertical; min-height: 80px; }
        .btn-add-event {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-add-event:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(32, 178, 170, 0.3); }

        /* Chart Placeholder */
        .chart-container { padding: 1rem; }
        .chart-bar-container { display: flex; align-items: flex-end; gap: 0.5rem; height: 200px; padding-top: 1rem; }
        .chart-bar-wrapper { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 0.3rem; }
        .chart-bar {
            width: 100%;
            max-width: 50px;
            background: linear-gradient(180deg, var(--accent), var(--accent2));
            border-radius: 6px 6px 0 0;
            min-height: 8px;
            transition: height 0.5s ease;
        }
        .chart-label { font-size: 0.7rem; color: var(--text-muted); font-weight: 600; }
        .chart-value { font-size: 0.75rem; color: var(--accent); font-weight: 700; }

        /* Success message */
        .toast-msg {
            position: fixed;
            top: 20px; right: 20px;
            background: #10b981;
            color: #fff;
            padding: 0.8rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            z-index: 9999;
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.3);
            animation: fadeInUp 0.3s ease-out;
        }

        /* Mobile Toggle */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 1rem; left: 1rem;
            z-index: 1100;
            background: var(--accent);
            color: #fff;
            border: none;
            width: 44px; height: 44px;
            border-radius: 10px;
            font-size: 1.2rem;
            cursor: pointer;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-toggle { display: block; }
            .main-content { margin-left: 0; }
            .event-form { grid-template-columns: 1fr; }
            .stat-cards { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .stat-cards { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>Bit2byte</h2>
            <small>Admin Dashboard</small>
        </div>
        <ul class="sidebar-nav">
            <li><a href="?tab=dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="?tab=members" class="<?php echo $activeTab === 'members' ? 'active' : ''; ?>"><i class="fas fa-users"></i> Members</a></li>
            <li><a href="?tab=events" class="<?php echo $activeTab === 'events' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Events</a></li>
            <li><a href="?tab=messages" class="<?php echo $activeTab === 'messages' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Messages</a></li>
            <li><a href="?tab=subscriptions" class="<?php echo $activeTab === 'subscriptions' ? 'active' : ''; ?>"><i class="fas fa-credit-card"></i> Subscriptions</a></li>
            <li><a href="?tab=statistics" class="<?php echo $activeTab === 'statistics' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Statistics</a></li>
            <div class="nav-divider"></div>
            <li><a href="<?php echo $base; ?>/index.php"><i class="fas fa-globe"></i> Visit Website</a></li>
            <li><a href="<?php echo $base; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>
                    <?php
                    switch ($activeTab) {
                        case 'members': echo '<i class="fas fa-users" style="color:var(--accent)"></i> Members'; break;
                        case 'events': echo '<i class="fas fa-calendar-alt" style="color:var(--accent)"></i> Events'; break;
                        case 'messages': echo '<i class="fas fa-envelope" style="color:var(--accent)"></i> Messages'; break;
                        case 'subscriptions': echo '<i class="fas fa-credit-card" style="color:var(--accent)"></i> Subscriptions'; break;
                        case 'statistics': echo '<i class="fas fa-chart-bar" style="color:var(--accent)"></i> Statistics'; break;
                        default: echo '👋 Welcome, ' . $adminName;
                    }
                    ?>
                </h1>
            </div>
            <div class="topbar-actions">
                <button class="theme-toggle" id="themeToggle" title="Toggle Dark/Light mode"><i class="fas fa-moon"></i></button>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="toast-msg" id="toastMsg">
                <i class="fas fa-check-circle"></i>
                <?php echo $_GET['msg'] === 'deleted' ? 'Item deleted successfully!' : 'Action completed!'; ?>
            </div>
            <script>setTimeout(() => document.getElementById('toastMsg')?.remove(), 3000);</script>
        <?php endif; ?>

        <?php if ($activeTab === 'dashboard'): ?>
        <!-- Dashboard Overview -->
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon teal"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalMembers; ?></h3>
                    <p>Total Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalEvents; ?></h3>
                    <p>Total Events</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><i class="fas fa-user-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalUsers; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pink"><i class="fas fa-envelope"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalMessages; ?></h3>
                    <p>Total Messages</p>
                </div>
            </div>
        </div>

        <!-- Recent Members -->
        <div class="content-card">
            <h2><i class="fas fa-user-plus"></i> Recent Members</h2>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr><th>Name</th><th>Email</th><th>Department</th><th>Role</th><th>Joined</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($users, 0, 5) as $u): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['department'] ?? '-'); ?></td>
                            <td><span class="badge-role <?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Messages -->
        <div class="content-card">
            <h2><i class="fas fa-comment-dots"></i> Recent Messages</h2>
            <?php if (empty($messages)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:2rem;">No messages yet.</p>
            <?php else: ?>
                <?php foreach (array_slice($messages, 0, 3) as $m): ?>
                <div class="msg-card">
                    <div class="msg-header">
                        <h4><?php echo htmlspecialchars($m['name']); ?></h4>
                        <small><?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?></small>
                    </div>
                    <div class="msg-email"><?php echo htmlspecialchars($m['email']); ?></div>
                    <div class="msg-body"><?php echo htmlspecialchars($m['message']); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($activeTab === 'members'): ?>
        <!-- Members Tab -->
        <div class="content-card">
            <h2><i class="fas fa-users"></i> All Members (<?php echo count($users); ?>)</h2>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Dept</th><th>Student ID</th><th>Role</th><th>Joined</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($u['department'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($u['student_id'] ?? '-'); ?></td>
                            <td><span class="badge-role <?php echo $u['role']; ?>"><?php echo $u['role']; ?></span></td>
                            <td><?php echo date('M d', strtotime($u['created_at'])); ?></td>
                            <td>
                                <?php if ($u['role'] !== 'admin'): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user?')">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.8rem;">Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($activeTab === 'events'): ?>
        <!-- Events Tab -->
        <div class="content-card">
            <h2><i class="fas fa-plus-circle"></i> Add New Event</h2>
            <form method="POST" class="event-form">
                <input type="hidden" name="action" value="add_event">
                <input type="text" name="event_title" placeholder="Event Title" required>
                <input type="date" name="event_date" required>
                <textarea name="event_desc" placeholder="Event Description" class="full-width"></textarea>
                <div><button type="submit" class="btn-add-event"><i class="fas fa-plus"></i> Add Event</button></div>
            </form>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-calendar-alt"></i> All Events (<?php echo count($events); ?>)</h2>
            <?php if (empty($events)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:2rem;">No events yet. Add your first event above!</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead><tr><th>Title</th><th>Date</th><th>Description</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($events as $e): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($e['title']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($e['event_date'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($e['description'] ?? '', 0, 80)); ?><?php echo strlen($e['description'] ?? '') > 80 ? '...' : ''; ?></td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event?')">
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                                    <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($activeTab === 'messages'): ?>
        <!-- Messages Tab -->
        <div class="content-card">
            <h2><i class="fas fa-envelope"></i> Contact Messages (<?php echo count($messages); ?>)</h2>
            <?php if (empty($messages)): ?>
                <p style="color:var(--text-muted);text-align:center;padding:2rem;">No messages yet.</p>
            <?php else: ?>
                <?php foreach ($messages as $m): ?>
                <div class="msg-card">
                    <div class="msg-header">
                        <h4><?php echo htmlspecialchars($m['name']); ?></h4>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <small><?php echo date('M d, Y H:i', strtotime($m['created_at'])); ?></small>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?')">
                                <input type="hidden" name="action" value="delete_message">
                                <input type="hidden" name="msg_id" value="<?php echo $m['id']; ?>">
                                <button type="submit" class="btn-delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <div class="msg-email"><i class="fas fa-envelope" style="font-size:0.8rem;margin-right:0.3rem;"></i> <?php echo htmlspecialchars($m['email']); ?></div>
                    <div class="msg-body"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php elseif ($activeTab === 'subscriptions'): ?>
        <!-- Subscriptions & SaaS Billing Tab -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-money-bill-wave"></i></div>
                    <div class="stat-info">
                        <h3>৳<?php echo number_format($totalRevenue); ?></h3>
                        <p>Total SaaS Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3>৳<?php echo number_format($monthlyRevenue); ?></h3>
                        <p>Current Month Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon orange"><i class="fas fa-user-shield"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $activeSubscribers; ?></h3>
                        <p>Active Paid Members</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-user-edit"></i> Manual Subscription Override</h2>
            <form method="POST" class="row g-3">
                <input type="hidden" name="action" value="manual_upgrade">
                <div class="col-md-5">
                    <label class="form-label" style="font-weight:600; font-size:0.85rem; color:var(--text-muted);">Select Member</label>
                    <select name="user_id" class="form-select" required>
                        <option value="" disabled selected>-- Choose User --</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?php echo $u['id']; ?>">
                                <?php echo htmlspecialchars($u['full_name']); ?> (<?php echo htmlspecialchars($u['email']); ?>) - Role: <?php echo htmlspecialchars($u['role']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-5">
                    <label class="form-label" style="font-weight:600; font-size:0.85rem; color:var(--text-muted);">Select Plan</label>
                    <select name="plan_id" class="form-select" required>
                        <option value="" disabled selected>-- Choose Plan --</option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?php echo $p['id']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (৳<?php echo number_format($p['price']); ?>/<?php echo $p['billing_cycle']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn-add-event" style="width: 100%; padding:0.6rem;"><i class="fas fa-user-cog"></i> Apply</button>
                </div>
            </form>
        </div>

        <div class="content-card">
            <h2><i class="fas fa-receipt"></i> SaaS Subscriptions Log (<?php echo count($subscriptions); ?>)</h2>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Active Plan</th>
                            <th>Price / Cycle</th>
                            <th>Start Date</th>
                            <th>Expiration Date</th>
                            <th>Status</th>
                            <th>Override Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subscriptions)): ?>
                            <tr><td colspan="8" style="text-align:center; color:var(--text-muted); padding:2rem;">No subscriptions found in registry.</td></tr>
                        <?php else: ?>
                            <?php foreach ($subscriptions as $sub): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sub['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                <td>
                                    <span class="badge-role <?php 
                                        if ($sub['plan_name'] === 'Free Member') echo 'user';
                                        elseif ($sub['plan_name'] === 'Student Premium') echo 'admin';
                                        else echo 'purple';
                                    ?>">
                                        <?php echo htmlspecialchars($sub['plan_name']); ?>
                                    </span>
                                </td>
                                <td>৳<?php echo number_format($sub['price']); ?> / <?php echo $sub['billing_cycle']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($sub['start_date'])); ?></td>
                                <td><?php echo $sub['end_date'] ? date('M d, Y', strtotime($sub['end_date'])) : 'Lifetime'; ?></td>
                                <td>
                                    <span class="badge-role <?php echo $sub['sub_status'] === 'active' ? 'user' : 'admin'; ?>" style="font-size:0.7rem;">
                                        <?php echo htmlspecialchars($sub['sub_status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($sub['sub_status'] === 'active' && $sub['plan_name'] !== 'Free Member'): ?>
                                    <form method="POST" onsubmit="return confirm('Cancel membership subscription and revert to Free?')">
                                        <input type="hidden" name="action" value="cancel_subscription">
                                        <input type="hidden" name="sub_id" value="<?php echo $sub['sub_id']; ?>">
                                        <button type="submit" class="btn-delete" style="font-size:0.75rem; padding: 0.2rem 0.5rem;"><i class="fas fa-ban"></i> Cancel</button>
                                    </form>
                                    <?php else: ?>
                                    <span style="color:var(--text-muted); font-size:0.75rem;">None</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php elseif ($activeTab === 'statistics'): ?>
        <!-- Statistics Tab -->
        <div class="content-card">
            <h2><i class="fas fa-chart-bar"></i> Monthly Registrations</h2>
            <div class="chart-container">
                <?php if (empty($monthlyData)): ?>
                    <p style="color:var(--text-muted);text-align:center;padding:2rem;">No registration data yet.</p>
                <?php else: ?>
                    <?php $maxVal = max(array_values($monthlyData)) ?: 1; ?>
                    <div class="chart-bar-container">
                        <?php foreach ($monthlyData as $month => $count): ?>
                        <div class="chart-bar-wrapper">
                            <div class="chart-value"><?php echo $count; ?></div>
                            <div class="chart-bar" style="height: <?php echo max(8, ($count / $maxVal) * 180); ?>px;"></div>
                            <div class="chart-label"><?php echo date('M', strtotime($month . '-01')); ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-icon teal"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalMembers; ?></h3>
                    <p>Total Members</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalEvents; ?></h3>
                    <p>Total Events</p>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </main>

    <script>
        // Sidebar toggle
        document.getElementById('sidebarToggle')?.addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });

        // Dark mode toggle
        const themeToggle = document.getElementById('themeToggle');
        const savedTheme = localStorage.getItem('admin-theme');
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        themeToggle?.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const isDark = document.body.classList.contains('dark-mode');
            localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
            themeToggle.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    </script>
</body>
</html>
